<?php

namespace App\Enums;

/**
 * APIのエラーレスポンスで返すエラーコード。
 *
 * フロントエンドが種類で分岐するための機械可読な識別子であり、
 * Phase 3のNestJS移行でもこの4種をそのまま再現する。
 */
enum ApiErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case Unauthenticated = 'UNAUTHENTICATED';
    case NotFound = 'NOT_FOUND';
    case InternalError = 'INTERNAL_ERROR';
}
