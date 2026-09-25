<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * APIの例外を { error: { code, message, fields } } の形に統一して返す。
 *
 * Laravel既定の { message, errors } をそのまま使わないのは、Phase 3で
 * NestJSのExceptionFilterが再現すべき仕様を明示しておくため。
 */
class ApiExceptionRenderer
{
    public static function render(Throwable $e): ?JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::make(
                ApiErrorCode::ValidationFailed,
                422,
                '入力内容を確認してください。',
                $e->errors(),
            );
        }

        if ($e instanceof AuthenticationException) {
            return self::make(ApiErrorCode::Unauthenticated, 401, 'ログインが必要です。');
        }

        // 所有権が無い場合もここに来る（403は使わず存在しない場合と区別しない方針）
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return self::make(ApiErrorCode::NotFound, 404, '対象が見つかりません。');
        }

        // デバッグ時はLaravel既定の詳細な画面・レスポンスを残す（原因調査のため）
        if (config('app.debug')) {
            return null;
        }

        return self::make(ApiErrorCode::InternalError, 500, 'サーバーでエラーが発生しました。');
    }

    /**
     * @param  array<string, array<int, string>>|null  $fields
     */
    private static function make(ApiErrorCode $code, int $status, string $message, ?array $fields = null): JsonResponse
    {
        $error = [
            'code' => $code->value,
            'message' => $message,
        ];

        // fieldsはバリデーション失敗のときだけ含める
        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return response()->json(['error' => $error], $status);
    }
}
