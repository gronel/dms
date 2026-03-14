<?php

use App\Http\Controllers\AuditLogs\AuditLogController;
use App\Http\Controllers\Documents\CommentController;
use App\Http\Controllers\Documents\DocumentController;
use App\Http\Controllers\Documents\DocumentPermissionController;
use App\Http\Controllers\Documents\DocumentVersionController;
use App\Http\Controllers\Documents\LockController;
use App\Http\Controllers\Documents\ShareLinkController;
use App\Http\Controllers\Folders\FolderController;
use App\Http\Controllers\Search\SearchController;
use App\Http\Controllers\Tags\TagController;
use Illuminate\Support\Facades\Route;

// Public share link landing (no auth required — validated by token)
Route::get('share/{token}', [ShareLinkController::class, 'show'])->name('share.show');

Route::middleware(['auth', 'verified'])->group(function () {

    // Search
    Route::get('search', SearchController::class)->name('search');

    // Audit logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('documents/{document:ulid}/audit', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // Documents
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document:ulid}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('documents/{document:ulid}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::patch('documents/{document:ulid}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('documents/{document:ulid}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Document lock / unlock
    Route::post('documents/{document:ulid}/lock', [LockController::class, 'lock'])->name('documents.lock');
    Route::delete('documents/{document:ulid}/lock', [LockController::class, 'unlock'])->name('documents.unlock');

    // Document Versions
    Route::get('documents/{document:ulid}/versions/{version}/download', [DocumentVersionController::class, 'download'])
        ->name('document-versions.download');
    Route::post('documents/{document:ulid}/versions/{version}/restore', [DocumentVersionController::class, 'restore'])
        ->name('document-versions.restore');

    // Per-document permission grants (owner-only ACL management)
    Route::get('documents/{document:ulid}/permissions', [DocumentPermissionController::class, 'index'])
        ->name('document-permissions.index');
    Route::post('documents/{document:ulid}/permissions', [DocumentPermissionController::class, 'store'])
        ->name('document-permissions.store');
    Route::delete('documents/{document:ulid}/permissions/{permission}', [DocumentPermissionController::class, 'destroy'])
        ->name('document-permissions.destroy');

    // Share links (tokenised, anonymous download)
    Route::post('documents/{document:ulid}/share-link', [ShareLinkController::class, 'store'])
        ->name('share-link.store');
    Route::delete('documents/{document:ulid}/share-link', [ShareLinkController::class, 'destroy'])
        ->name('share-link.destroy');

    // Comments
    Route::post('documents/{document:ulid}/comments', [CommentController::class, 'store'])
        ->name('document-comments.store');
    Route::delete('documents/{document:ulid}/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('document-comments.destroy');

    // Folders
    Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    // Tags
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});
