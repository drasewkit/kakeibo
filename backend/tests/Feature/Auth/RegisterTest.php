<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/auth/register のFeatureテスト
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系のリクエストボディ
     *
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '高久 大祐',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ], $overrides);
    }

    public function test_ユーザーを登録できる(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('name', '高久 大祐')
            ->assertJsonPath('email', 'taro@example.com');

        $this->assertDatabaseHas('users', ['email' => 'taro@example.com']);
    }

    public function test_登録すると1人世帯が作られる(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload())->assertStatus(201);

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertSame('高久 大祐の家計簿', $user->household->name);
        $this->assertSame(1, $user->household->users()->count());
    }

    public function test_既存の世帯には入らず新しい世帯が作られる(): void
    {
        $existing = User::factory()->create();

        $this->postJson('/api/auth/register', $this->validPayload())->assertStatus(201);

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertNotSame($existing->household_id, $user->household_id);
    }

    public function test_名前が長くても世帯名は255文字に収まる(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload([
            'name' => str_repeat('あ', 255),
        ]))->assertStatus(201);

        $name = User::where('email', 'taro@example.com')->firstOrFail()->household->name;
        $this->assertSame(255, mb_strlen($name));
        $this->assertStringEndsWith('の家計簿', $name);
    }

    public function test_登録後はログイン状態になる(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload());

        $this->assertAuthenticated();
    }

    public function test_パスワードはレスポンスに含まれない(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload());

        $response->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token');
    }

    public function test_パスワードはハッシュ化して保存される(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload());

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password);
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['name', 'email', 'password']]]);
    }

    public function test_登録済みのメールアドレスは422になる(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->postJson('/api/auth/register', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['email']]]);
    }

    public function test_確認用パスワードが一致しないと422になる(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload([
            'passwordConfirmation' => 'different-password',
        ]));

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['password']]]);
    }

    public function test_パスワードが8文字未満だと422になる(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload([
            'password' => 'short7c',
            'passwordConfirmation' => 'short7c',
        ]));

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['password']]]);
    }
}
