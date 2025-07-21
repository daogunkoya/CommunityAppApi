<?php

namespace App\Http\Controllers;

use App\Services\DiscussionService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(protected DiscussionService $service) {}

    public function __invoke(Request $request) {
        return response()->json([
            'data' => $this->service->fetchDashboardContent()
        ]);
    }
}
