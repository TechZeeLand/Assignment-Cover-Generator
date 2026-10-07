<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Csrf;
use App\Http;
use App\ProfileStore;
use App\Settings;

try {
    $user = Auth::user();
    if ($user === null || !Settings::bool('profiles_enabled')) {
        Http::json(['ok' => false, 'error' => 'Please sign in to save your details.'], 401);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Http::json(['ok' => true, 'profile' => ProfileStore::load($user['id'])]);
    }

    if (!Csrf::valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        Http::json(['ok' => false, 'error' => 'Your session expired. Please reload the page.'], 403);
    }
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        Http::json(['ok' => false, 'error' => 'Invalid request.'], 400);
    }

    if (($body['action'] ?? '') === 'delete') {
        ProfileStore::delete($user['id']);
        Http::json(['ok' => true]);
    }
    if (($body['action'] ?? '') === 'save' && is_array($body['data'] ?? null)) {
        ProfileStore::save($user['id'], $body['data']);
        Http::json(['ok' => true]);
    }
    Http::json(['ok' => false, 'error' => 'Unknown action.'], 400);
} catch (\RuntimeException $e) {
    Http::json(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] profile api: ' . $e->getMessage());
    Http::json(['ok' => false, 'error' => 'Could not save right now.'], 500);
}
