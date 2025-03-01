<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\StatusPrivacy;
use App\Models\StatusViewer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StatusController extends Controller
{
    /**
     * Get all visible statuses for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $visibleStatuses = Status::whereHas('privacy', function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('privacy_type', 'all')
                    ->orWhere(function ($q) use ($user) {
                        $q->where('privacy_type', 'selected')
                            ->whereJsonContains('selected_users', $user->id);
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('privacy_type', 'except')
                            ->whereJsonDoesntContain('selected_users', $user->id);
                    });
            });
        })
        ->where('expires_at', '>', now())
        ->with(['user', 'viewers'])
        ->latest()
        ->get();

        return response()->json($visibleStatuses);
    }

    /**
     * Create a new status.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:text,image,video',
            'content' => 'required_without:media|string|max:500',
            'media' => 'required_if:type,image,video|file|max:20480',
            'privacy_type' => 'required|in:all,selected,except',
            'selected_users' => 'required_if:privacy_type,selected,except|array',
            'selected_users.*' => 'exists:users,id'
        ]);

        $status = new Status([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'expires_at' => now()->addHours(24)
        ]);

        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('status-media', 'public');
            $status->media_url = $path;
        }

        $status->save();

        // Create or update privacy settings
        StatusPrivacy::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'privacy_type' => $validated['privacy_type'],
                'selected_users' => $validated['selected_users'] ?? []
            ]
        );

        return response()->json($status->load('user'), 201);
    }

    /**
     * View a specific status.
     */
    public function show(Request $request, Status $status)
    {
        $user = $request->user();

        if (!$status->privacy->canUserViewStatus($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($status->hasExpired()) {
            return response()->json(['message' => 'Status has expired'], 410);
        }

        // Record the view if not already viewed
        StatusViewer::firstOrCreate([
            'status_id' => $status->id,
            'user_id' => $user->id,
            'viewed_at' => now()
        ]);

        return response()->json($status->load(['user', 'viewers.user']));
    }

    /**
     * Delete a status.
     */
    public function destroy(Request $request, Status $status)
    {
        if ($status->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($status->hasMedia()) {
            Storage::disk('public')->delete($status->media_url);
        }

        $status->delete();

        return response()->json(['message' => 'Status deleted successfully']);
    }

    /**
     * Get all statuses for the authenticated user.
     */
    public function myStatuses(Request $request)
    {
        $statuses = $request->user()->statuses()
            ->with(['viewers.user'])
            ->latest()
            ->get();

        return response()->json($statuses);
    }

    /**
     * Update status privacy settings.
     */
    public function updatePrivacy(Request $request)
    {
        $validated = $request->validate([
            'privacy_type' => 'required|in:all,selected,except',
            'selected_users' => 'required_if:privacy_type,selected,except|array',
            'selected_users.*' => 'exists:users,id'
        ]);

        StatusPrivacy::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'privacy_type' => $validated['privacy_type'],
                'selected_users' => $validated['selected_users'] ?? []
            ]
        );

        return response()->json(['message' => 'Privacy settings updated successfully']);
    }
}