<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For each user, set username to email prefix (before @)
        DB::table('users')->get()->each(function ($user) {
            $username = strtok($user->email, '@');
            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this operation
    }
};
