<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\GetInvitationRequest;
use App\Http\Requests\Household\JoinHouseholdRequest;
use App\Http\Requests\Household\UpdateHouseholdRequest;
use App\Http\Resources\Household\HouseholdResource;
use App\Http\Resources\Household\InvitationLinkResource;
use App\Http\Resources\Household\InvitationResource;
use App\Services\Household\HouseholdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 世帯（詳細の取得・世帯名の変更・招待）
 *
 * 対象のIDは受け取らず、常にログインユーザーが属する世帯を対象にする。
 * 招待内容の取得だけは、招待リンクから新規登録する人のためにログインなしで呼べる。
 */
class HouseholdController extends Controller
{
    public function __construct(
        private readonly HouseholdService $householdService,
    ) {}

    public function getDetail(Request $request): JsonResponse
    {
        $household = $this->householdService->getDetail($request->user());

        return HouseholdResource::make($household)->response();
    }

    public function update(UpdateHouseholdRequest $request): JsonResponse
    {
        $household = $this->householdService->update($request->user(), $request->validated());

        return HouseholdResource::make($household)->response();
    }

    public function createInvitation(Request $request): JsonResponse
    {
        // 未使用の古い招待は無効になる
        ['invitation' => $invitation, 'token' => $token] = $this->householdService->createInvitation($request->user());

        return (new InvitationLinkResource($invitation, $token))->response()->setStatusCode(201);
    }

    public function getInvitation(GetInvitationRequest $request): JsonResponse
    {
        // 存在しない・期限切れ・使用済みの招待は区別せず404になる
        $invitation = $this->householdService->getInvitation($request->validated('token'));

        return InvitationResource::make($invitation)->response();
    }

    public function join(JoinHouseholdRequest $request): JsonResponse
    {
        // 招待先の世帯に移り、参加後の世帯を返す
        $household = $this->householdService->join($request->user(), $request->validated('token'));

        return HouseholdResource::make($household)->response();
    }
}
