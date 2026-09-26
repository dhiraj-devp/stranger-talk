<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('banned_until')->nullable();
            $table->string('ban_reason')->nullable();
            $table->boolean('is_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id', 'avatar', 'status', 'last_seen_at', 'banned_until', 'ban_reason', 'is_admin',
            ]);
        });
    }
};
