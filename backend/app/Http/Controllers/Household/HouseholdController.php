<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\UpdateHouseholdRequest;
use App\Http\Resources\Household\HouseholdResource;
use App\Services\Household\HouseholdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 世帯（詳細の取得・世帯名の変更）
 *
 * 対象のIDは受け取らず、常にログインユーザーが属する世帯を対象にする。
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
}
