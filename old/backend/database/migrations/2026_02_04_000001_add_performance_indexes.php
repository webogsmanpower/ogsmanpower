<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add performance indexes for conversations table
        Schema::table('conversations', function (Blueprint $table) {
            // Index for JSON participant queries - MySQL 5.7+
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                // Check if column exists before adding
                if (!Schema::hasColumn('conversations', 'participant_user_ids')) {
                    // Add generated column for participant user_id extraction
                    DB::statement("ALTER TABLE conversations ADD COLUMN participant_user_ids JSON GENERATED ALWAYS AS (JSON_EXTRACT(participants, '$[*].user_id')) STORED");
                }
                if (!Schema::hasIndex('conversations', 'idx_conversations_participant_ids')) {
                    $table->index('participant_user_ids', 'idx_conversations_participant_ids');
                }
            }
            
            // Composite indexes for common queries
            if (!Schema::hasIndex('conversations', 'idx_conversations_type_last_message')) {
                $table->index(['type', 'last_message_at'], 'idx_conversations_type_last_message');
            }
            if (!Schema::hasIndex('conversations', 'idx_conversations_status_last_message')) {
                $table->index(['is_archived', 'is_closed', 'last_message_at'], 'idx_conversations_status_last_message');
            }
        });

        // Add performance indexes for messages table
        Schema::table('messages', function (Blueprint $table) {
            // Composite indexes for unread count query
            if (!Schema::hasIndex('messages', 'idx_messages_sender_created')) {
                $table->index(['sender_id', 'created_at'], 'idx_messages_sender_created');
            }
            if (!Schema::hasIndex('messages', 'idx_messages_conv_sender_created')) {
                $table->index(['conversation_id', 'sender_id', 'created_at'], 'idx_messages_conv_sender_created');
            }
            if (!Schema::hasIndex('messages', 'idx_messages_conv_sender_deleted')) {
                $table->index(['conversation_id', 'sender_id', 'is_deleted'], 'idx_messages_conv_sender_deleted');
            }
        });

        // Add performance indexes for message_read_receipts table
        Schema::table('message_read_receipts', function (Blueprint $table) {
            // Composite index for unread queries
            if (!Schema::hasIndex('message_read_receipts', 'idx_read_receipts_user_read_at')) {
                $table->index(['user_id', 'read_at'], 'idx_read_receipts_user_read_at');
            }
        });

        // Add performance indexes for visa_statuses table
        Schema::table('visa_statuses', function (Blueprint $table) {
            // Composite indexes for seeker queries
            if (!Schema::hasIndex('visa_statuses', 'idx_visa_seeker_created')) {
                $table->index(['seeker_id', 'created_at'], 'idx_visa_seeker_created');
            }
            if (!Schema::hasIndex('visa_statuses', 'idx_visa_seeker_step')) {
                $table->index(['seeker_id', 'current_step'], 'idx_visa_seeker_step');
            }
            if (!Schema::hasIndex('visa_statuses', 'idx_visa_employer_step_created')) {
                $table->index(['employer_id', 'current_step', 'created_at'], 'idx_visa_employer_step_created');
            }
        });

        // Add performance indexes for visa_steps table
        Schema::table('visa_steps', function (Blueprint $table) {
            // Check if indexes exist before adding
            if (!Schema::hasIndex('visa_steps', 'idx_visa_steps_status_order')) {
                $table->index(['visa_status_id', 'step_order'], 'idx_visa_steps_status_order');
            }
            if (!Schema::hasIndex('visa_steps', 'idx_visa_steps_status')) {
                $table->index(['visa_status_id', 'status'], 'idx_visa_steps_status');
            }
            if (!Schema::hasIndex('visa_steps', 'idx_visa_steps_status_order_status')) {
                $table->index(['visa_status_id', 'step_order', 'status'], 'idx_visa_steps_status_order_status');
            }
        });

        // Add performance indexes for visa_step_documents table
        Schema::table('visa_step_documents', function (Blueprint $table) {
            // Composite indexes for document queries
            if (!Schema::hasIndex('visa_step_documents', 'idx_visa_docs_seeker_step')) {
                $table->index(['seeker_id', 'visa_step_id'], 'idx_visa_docs_seeker_step');
            }
            if (!Schema::hasIndex('visa_step_documents', 'idx_visa_docs_seeker_process')) {
                $table->index(['seeker_id', 'visa_process_step_id'], 'idx_visa_docs_seeker_process');
            }
            if (!Schema::hasIndex('visa_step_documents', 'idx_visa_docs_step_seeker_status')) {
                $table->index(['visa_step_id', 'seeker_id', 'status'], 'idx_visa_docs_step_seeker_status');
            }
            if (!Schema::hasIndex('visa_step_documents', 'idx_visa_docs_process_seeker_status')) {
                $table->index(['visa_process_step_id', 'seeker_id', 'status'], 'idx_visa_docs_process_seeker_status');
            }
        });

        // Add performance indexes for visa_process_steps table
        Schema::table('visa_process_steps', function (Blueprint $table) {
            if (!Schema::hasIndex('visa_process_steps', 'idx_visa_process_status')) {
                $table->index(['visa_status_id', 'status'], 'idx_visa_process_status');
            }
            if (!Schema::hasIndex('visa_process_steps', 'idx_visa_process_custom_created')) {
                $table->index(['visa_status_id', 'is_custom', 'created_at'], 'idx_visa_process_custom_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_type_last_message');
            $table->dropIndex('idx_conversations_status_last_message');
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->dropIndex('idx_conversations_participant_ids');
                $table->dropColumn('participant_user_ids');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_sender_created');
            $table->dropIndex('idx_messages_conv_sender_created');
            $table->dropIndex('idx_messages_conv_sender_deleted');
        });

        Schema::table('message_read_receipts', function (Blueprint $table) {
            $table->dropIndex('idx_read_receipts_user_read_at');
        });

        Schema::table('visa_statuses', function (Blueprint $table) {
            $table->dropIndex('idx_visa_seeker_created');
            $table->dropIndex('idx_visa_seeker_step');
            $table->dropIndex('idx_visa_employer_step_created');
        });

        Schema::table('visa_steps', function (Blueprint $table) {
            $table->dropIndex('idx_visa_steps_status_order');
            $table->dropIndex('idx_visa_steps_status');
            $table->dropIndex('idx_visa_steps_status_order_status');
        });

        Schema::table('visa_step_documents', function (Blueprint $table) {
            $table->dropIndex('idx_visa_docs_seeker_step');
            $table->dropIndex('idx_visa_docs_seeker_process');
            $table->dropIndex('idx_visa_docs_step_seeker_status');
            $table->dropIndex('idx_visa_docs_process_seeker_status');
        });

        Schema::table('visa_process_steps', function (Blueprint $table) {
            $table->dropIndex('idx_visa_process_status');
            $table->dropIndex('idx_visa_process_custom_created');
        });
    }
};
