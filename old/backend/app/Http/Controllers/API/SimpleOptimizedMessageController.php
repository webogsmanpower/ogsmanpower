<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * SimpleOptimizedMessageController
 * 
 * Simple optimization with caching for the existing MessageController logic
 */
class SimpleOptimizedMessageController extends Controller
{
    /**
     * Get unread message count - SIMPLE OPTIMIZED VERSION
     * 
     * Uses the same logic as original but with caching
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "unread_count_{$user->id}";
        
        // Cache for 30 seconds to reduce database load
        $count = Cache::remember($cacheKey, 30, function () use ($user) {
            // Use the original logic but with better indexing
            return Message::whereHas('conversation', function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();
        });

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Get conversations - SIMPLE OPTIMIZED VERSION
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        $cacheKey = "conversations_{$user->id}_{$perPage}";

        // Cache for 60 seconds for conversations
        $conversations = Cache::remember($cacheKey, 60, function () use ($user, $perPage) {
            return Conversation::query()
                ->where(function ($query) use ($user) {
                    $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                          ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
                })
                ->with(['latestMessage', 'jobApplication.job'])
                ->orderByDesc('last_message_at')
                ->paginate($perPage);
        });

        // Transform conversations with additional data
        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            return $this->transformConversation($conversation, $user);
        });

        return response()->json($conversations);
    }

    /**
     * Mark conversation as read - SIMPLE OPTIMIZED VERSION
     */
    public function markAsRead(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        
        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->firstOrFail();

        // Mark all unread messages as read
        $this->markConversationAsRead($conversation, $user);

        // Clear cache
        Cache::forget("unread_count_{$user->id}");
        Cache::forget("conversations_{$user->id}_20");

        return response()->json(['success' => true]);
    }

    /**
     * Transform conversation for API response
     */
    private function transformConversation($conversation, $user)
    {
        // Simple transformation - can be enhanced as needed
        return [
            'id' => $conversation->id,
            'uuid' => $conversation->uuid,
            'subject' => $conversation->subject,
            'type' => $conversation->type,
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'unread_count' => $conversation->getUnreadCountFor($user->id),
            'is_archived' => $conversation->is_archived,
            'is_closed' => $conversation->is_closed,
            'latest_message' => $conversation->latestMessage ? [
                'content' => $conversation->latestMessage->is_deleted 
                    ? '[Message deleted]' 
                    : $conversation->latestMessage->message,
                'sender_id' => $conversation->latestMessage->sender_id,
                'created_at' => $conversation->latestMessage->created_at->toISOString(),
            ] : null,
        ];
    }

    /**
     * Mark conversation as read
     */
    private function markConversationAsRead($conversation, $user)
    {
        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markReadBy($user);
        }
    }
}
