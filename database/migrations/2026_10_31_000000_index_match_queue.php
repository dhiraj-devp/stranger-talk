<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->index(['status', 'user_two_id', 'id'], 'matches_queue_idx');
            $table->index(['user_one_id', 'user_one_token'], 'matches_user_one_token_idx');
            $table->index(['user_two_id', 'user_two_token'], 'matches_user_two_token_idx');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_queue_idx');
            $table->dropIndex('matches_user_one_token_idx');
            $table->dropIndex('matches_user_two_token_idx');
        });
    }
};
