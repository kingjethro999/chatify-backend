<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get all chats for the authenticated user.
     */
    public function index(Request $request)
    {
        $chats = $request->user()->chats()
            ->with(['users', 'messages' => function ($query) {
                $query->latest()->first();
            }])
            ->get();

        return response()->json($chats);
    }

    /**
     * Create a new chat.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:private,group',
            'name' => 'required_if:type,group|string|max:255',
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id'
        ]);

        $chat = Chat::create([
            'type' => $validated['type'],
            'name' => $validated['name'] ?? null,
        ]);

        // Add authenticated user as admin for group chats
        $isAdmin = $validated['type'] === 'group';
        $chat->users()->attach($request->user()->id, ['is_admin' => $isAdmin]);

        // Add other users
        foreach ($validated['users'] as $userId) {
            if ($userId !== $request->user()->id) {
                $chat->users()->attach($userId, ['is_admin' => false]);
            }
        }

        return response()->json($chat->load('users'), 201);
    }

    /**
     * Get messages for a specific chat.
     */
    public function messages(Request $request, Chat $chat)
    {
        if (!$chat->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $chat->messages()
            ->with('user')
            ->latest()
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * Send a message in a chat.
     */
    public function sendMessage(Request $request, Chat $chat)
    {
        if (!$chat->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required_without:file|string|max:1000',
            'type' => 'required|in:text,image,video,audio,document',
            'file' => 'required_if:type,image,video,audio,document|file|max:10240'
        ]);

        $message = new Message([
            'content' => $validated['content'] ?? null,
            'type' => $validated['type'],
            'user_id' => $request->user()->id
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat-files', 'public');
            $message->file_url = $path;
        }

        $chat->messages()->save($message);

        // Update last_read_at for the sender
        $chat->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json($message->load('user'), 201);
    }

    /**
     * Add users to a group chat.
     */
    public function addUsers(Request $request, Chat $chat)
    {
        if (!$chat->isGroupChat() || 
            !$chat->admins()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id'
        ]);

        $existingUsers = $chat->users()->pluck('user_id')->toArray();
        $newUsers = array_diff($validated['users'], $existingUsers);

        foreach ($newUsers as $userId) {
            $chat->users()->attach($userId, ['is_admin' => false]);
        }

        return response()->json($chat->load('users'));
    }

    /**
     * Remove users from a group chat.
     */
    public function removeUsers(Request $request, Chat $chat)
    {
        if (!$chat->isGroupChat() || 
            !$chat->admins()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id'
        ]);

        $chat->users()->detach($validated['users']);

        return response()->json($chat->load('users'));
    }

    /**
     * Mark all messages in a chat as read.
     */
    public function markAsRead(Request $request, Chat $chat)
    {
        if (!$chat->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $chat->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}