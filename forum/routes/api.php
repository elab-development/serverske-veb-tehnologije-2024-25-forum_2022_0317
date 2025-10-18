<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExternalFeedController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/themes', [ThemeController::class, 'index']);
Route::get('/themes/{theme}', [ThemeController::class, 'show']);
Route::get('/themes/{theme}/posts', [ThemeController::class, 'posts']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/replies', [PostController::class, 'replies']);
Route::get('/posts/{post}/likes', [PostController::class, 'likes']);

Route::get('/ext/hn/top', [ExternalFeedController::class, 'hackerNewsTop']);
Route::get('/ext/discourse/latest', [ExternalFeedController::class, 'discourseLatest']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::resource('themes', ThemeController::class)
        ->only(['store', 'update', 'destroy']);

    Route::get('/likes', [LikeController::class, 'index']);
    Route::post('/posts/{post}/like', [LikeController::class, 'like']);
    Route::delete('/posts/{post}/like', [LikeController::class, 'unlike']);

    Route::resource('posts', PostController::class)
        ->only(['store', 'destroy']);

    Route::post('/users/{user}/can-publish/toggle', [UserController::class, 'toggleCanPublish']);
});