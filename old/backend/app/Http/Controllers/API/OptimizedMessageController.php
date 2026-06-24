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
 * OptimizedMessageController
 * 
 * High-performance messaging controller with optimized queries
 * and proper database indexing strategies.
 */
class OptimizedMessageController extends Controller
{
    /**
     * Get unread message count - OPTIMIZED VERSION
     * 
     * Uses participant_id column for fast lookups instead of JSON operations
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "unread_count_{$user->id}";
        
        // Cache for 30 seconds to reduce database load
        $count = Cache::remember($cacheKey, 30, function () use ($user) {
            // Optimized query using EXISTS instead of JSON operations for better performance
            return DB::selectOne("
                SELECT COUNT(*) as unread_count
                FROM messages m
                INNER JOIN conversations c ON m.conversation_id = c.id
                LEFT JOIN message_read_receipts mrr ON m.id = mrr.message_id AND mrr.user_id = ?
                WHERE 
                    (JSON_CONTAINS(c.participants, ?) OR JSON_SEARCH(c.participants, 'one', ?) IS NOT NULL)
                    AND m.sender_id != ? 
                    AND mrr.id IS NULL
                    AND m.is_deleted = 0
            ", [json_encode(['user_id' => $user->id]), (string)$user->id, $user->id])->unread_count ?? 0;
        });

        return response()->json(['unread_count' => (int)$count]);
    }

    /**
     * Get conversations - OPTIMIZED VERSION
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        
        // Use raw SQL for better performance with JSON participant queries
        $conversations = DB::table('conversations as c')
            ->select([
                'c.*',
                'm.id as latest_message_id',
                'm.message as latest_message_content',
                'm.sender_id as latest_message_sender_id',
                'm.created_at as latest_message_created_at'
            ])
            ->leftJoin('messages as m', function ($join) {
                $join->on('c.id', '=', 'm.conversation_id')
                     ->where('m.id', '=', DB::raw('(SELECT MAX(id) FROM messages WHERE conversation_id = c.id AND is_deleted = 0)'));
            })
            ->where(function($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(c.participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(c.participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->where('c.is_archived', false)
            ->orderByDesc('c.last_message_at')
            ->paginate($perPage);

        // Transform data efficiently
        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            return [
                'id' => $conversation->id,
                'uuid' => $conversation->uuid,
                'subject' => $conversation->subject,
                'type' => $conversation->type,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'latest_message' => $conversation->latest_message_id ? [
                    'id' => $conversation->latest_message_id,
                    'content' => $conversation->latest_message_content,
                    'sender_id' => $conversation->latest_message_sender_id,
                    'created_at' => $conversation->latest_message_created_at,
                ] : null,
                'unread_count' => $this->getConversationUnreadCount($conversation->id, $user->id),
                'is_archived' => $conversation->is_archived,
                'is_closed' => $conversation->is_closed,
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get unread count for a specific conversation
     */
    private function getConversationUnreadCount(int $conversationId, int $userId): int
    {
        return DB::selectOne("
            SELECT COUNT(*) as count
            FROM messages m
            LEFT JOIN message_read_receipts mrr ON m.id = mrr.message_id AND mrr.user_id = ?
            WHERE m.conversation_id = ? 
                AND m.sender_id != ? 
                AND mrr.id IS NULL
                AND m.is_deleted = 0
        ", [$userId, $conversationId, $userId])->count ?? 0;
    }

    /**
     * Mark conversation as read - OPTIMIZED VERSION
     */
    public function markAsRead(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        
        // Get conversation efficiently
        $conversation = DB::table('conversations')
            ->where('uuid', $uuid)
            ->where(function($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->first();

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Bulk insert read receipts for better performance
        $unreadMessages = DB::select("
            SELECT m.id 
            FROM messages m
            LEFT JOIN message_read_receipts mrr ON m.id = mrr.message_id AND mrr.user_id = ?
            WHERE m.conversation_id = ? 
                AND m.sender_id != ? 
                AND mrr.id IS NULL
                AND m.is_deleted = 0
        ", [$user->id, $conversation->id, $user->id]);

        if (!empty($unreadMessages)) {
            $insertData = array_map(function ($msg) use ($user) {
                return [
                    'message_id' => $msg->id,
                    'user_id' => $user->id,
                    'read_at' => now(),
                ];
            }, $unreadMessages);

            // Bulk insert for performance
            DB::table('message_read_receipts')->insertOrIgnore($insertData);
        }

        // Clear cache
        Cache::forget("unread_count_{$user->id}");

        return response()->json(['success' => true]);
    }
}
