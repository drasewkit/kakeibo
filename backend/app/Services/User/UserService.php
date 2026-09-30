<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * ユーザーのプロフィール画像に関するビジネスロジック
 *
 * 登録・削除はログインユーザー自身の画像だけを対象にする（他人の画像は変更できない）。
 */
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * プロフィール画像を登録する（登録済みなら差し替える）
     */
    public function uploadAvatar(User $user, UploadedFile $image): User
    {
        // 古い画像を削除してから新しい画像を保存する
        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        $path = $image->store("avatars/{$user->id}", 'local');

        return $this->userRepository->update($user, ['avatar_path' => $path]);
    }

    /**
     * 閲覧できるユーザーのプロフィール画像のパスを返す
     */
    public function getAvatarPath(int $viewerId, int $userId): string
    {
        // 閲覧できないユーザー・画像が未登録の場合は、区別せず404にする
        $user = $this->userRepository->findVisibleTo($viewerId, $userId);

        if (! $user?->avatar_path) {
            throw new ModelNotFoundException('Avatar not found.');
        }

        return $user->avatar_path;
    }

    /**
     * プロフィール画像を削除する
     */
    public function deleteAvatar(User $user): User
    {
        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        return $this->userRepository->update($user, ['avatar_path' => null]);
    }
}
