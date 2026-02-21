<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Http\Request;

$user = User::first();
if (!$user) {
    echo "No users found\n";
    exit;
}

// Mock request
$request = Request::create('/api/conversations', 'GET', ['page' => 1, 'per_page' => 2]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app(\App\Http\Controllers\ConversationController::class);
$response = $controller->index($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Data: " . json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
