<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

// URIは /{リソース複数形}/{操作名} 。操作名にリソース名は繰り返さない（backend/CLAUDE.md参照）
// GET/POSTのみを使用し、更新・削除対象のIDはURLではなくバリデーション対象として受け取る

// 未ログインでも呼べる認証系エンドポイント
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Sanctumのセッション認証が必要なエンドポイント
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/get-user', [AuthController::class, 'getUser']);

    Route::get('/categories/get-list', [CategoryController::class, 'getList']);

    Route::get('/transactions/get-list', [TransactionController::class, 'getList']);
    Route::get('/transactions/get-detail', [TransactionController::class, 'getDetail']);
    Route::post('/transactions/create', [TransactionController::class, 'create']);
    Route::post('/transactions/update', [TransactionController::class, 'update']);
    Route::post('/transactions/delete', [TransactionController::class, 'delete']);
    Route::get('/transactions/get-image', [TransactionController::class, 'getImage']);
    Route::post('/transactions/upload-image', [TransactionController::class, 'uploadImage']);
    Route::post('/transactions/delete-image', [TransactionController::class, 'deleteImage']);
});
