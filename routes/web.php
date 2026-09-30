<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\KeywordController as AdminKeywordController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/privacy', fn () => view('public.privacy'))->name('privacy');
Route::get('/about', fn () => view('public.about'))->name('about');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

// Admin — single-operator content review & publish
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/articles');

    Route::get('articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::get('articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::post('articles/{article}/publish', [AdminArticleController::class, 'publish'])->name('articles.publish');
    Route::post('articles/{article}/unpublish', [AdminArticleController::class, 'unpublish'])->name('articles.unpublish');
    Route::delete('articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('keywords', [AdminKeywordController::class, 'index'])->name('keywords.index');
    Route::post('keywords', [AdminKeywordController::class, 'store'])->name('keywords.store');
    Route::put('keywords/{keyword}', [AdminKeywordController::class, 'update'])->name('keywords.update');
    Route::delete('keywords/{keyword}', [AdminKeywordController::class, 'destroy'])->name('keywords.destroy');
});

// `dashboard` name kept so Breeze's post-login redirect still resolves.
Route::redirect('/dashboard', '/admin/articles')->middleware('auth')->name('dashboard');

// Account (kept from Breeze scaffold)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
