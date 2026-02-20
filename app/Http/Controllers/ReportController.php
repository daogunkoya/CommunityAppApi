<?php

namespace App\Http\Controllers;

use App\Models\Validation\ReportValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_id' => 'required',
            'reportable_type' => 'required|string|in:discussion,comment,user,game_event,message',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $map = [
            'discussion' => \App\Models\Discussion::class,
            'comment' => \App\Models\Comment::class,
            'user' => \App\Models\User::class,
            'game_event' => \App\Models\GameEvent::class,
            'message' => \App\Models\Message::class,
        ];

        try {
            $report = \App\Models\Report::create([
                'reporter_id' => $request->user()->id,
                'reportable_id' => $validated['reportable_id'],
                'reportable_type' => $map[$validated['reportable_type']],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully. We will review this shortly.',
                'data' => $report
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of reports for administrative review.
     */
    public function index(Request $request): JsonResponse
    {
        // Enforce basic admin check (assuming role string exists, or general access for now)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Admins only.'
            ], 403);
        }

        try {
            // Eager load the reporter user and the polymorphic reported content
            $reports = \App\Models\Report::with(['reporter:id,name,first_name,last_name,email,profile_picture', 'reportable'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve a report and optionally delete the offensive content.
     */
    public function resolve(Request $request, $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Admins only.'
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:dismiss,delete_content',
        ]);

        try {
            $report = \App\Models\Report::findOrFail($id);

            if ($validated['action'] === 'delete_content' && $report->reportable) {
                // Delete the exact content type dynamically using Polymorphism
                $report->reportable->delete();
            }

            // Mark report as resolved by updating its status
            $report->update([
                'status' => 'resolved',
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report resolved successfully.',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
