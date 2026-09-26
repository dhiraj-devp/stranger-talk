<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->index();
            $table->json('signal')->nullable();
            $table->string('end_reason', 20)->nullable();
            $table->timestamp('user_one_seen_at')->nullable();
            $table->timestamp('user_two_seen_at')->nullable();
            $table->string('user_one_token', 64)->nullable();
            $table->string('user_two_token', 64)->nullable();
            $table->timestamps();

            $table->index(['user_one_id', 'status']);
            $table->index(['user_two_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
