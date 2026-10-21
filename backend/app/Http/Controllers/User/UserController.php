<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\GetAvatarRequest;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ユーザー（プロフィール画像の取得・登録・削除）
 *
 * 登録・削除は対象のIDを受け取らず、常にログインユーザー自身を対象にする。
 * 取得は同じ世帯のメンバーの画像までできる。
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function getAvatar(GetAvatarRequest $request): StreamedResponse
    {
        // 別の世帯のユーザー・画像が未登録の場合も、区別せず404になる
        $path = $this->userService->getAvatarPath(
            $request->user(),
            $request->validated('userId'),
        );

        return Storage::disk('local')->response($path);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $this->userService->uploadAvatar($request->user(), $request->file('image'));

        return UserResource::make($user)->response();
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $this->userService->deleteAvatar($request->user());

        return UserResource::make($user)->response();
    }
}
