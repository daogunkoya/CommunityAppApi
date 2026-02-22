<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

use Illuminate\Support\Facades\DB;

$notification = DB::table('notifications')->first();
if ($notification) {
    echo "ID: " . $notification->id . "\n";
    echo "Type: " . $notification->type . "\n";
    echo "Read at: " . ($notification->read_at ?? 'null') . "\n";
    echo "Notifiable ID: " . $notification->notifiable_id . "\n";

    // Check if the user exists
    $user = DB::table('users')->where('id', $notification->notifiable_id)->first();
    echo "User exists: " . ($user ? 'Yes' : 'No') . "\n";
} else {
    echo "No notifications found in the database.\n";
}
