<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->index();
        });
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        Schema::table('matches', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('first_path', 190);
            $table->string('referrer_host', 190)->nullable();
            $table->string('device', 20);
            $table->string('country', 2)->nullable();
            $table->timestamp('started_at')->index();
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->string('path', 190)->index();
            $table->unsignedInteger('duration')->default(0);
            $table->timestamp('entered_at')->index();
        });

        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric', 40);
            $table->string('dimension', 120)->default('');
            $table->unsignedInteger('value')->default(0);
            $table->unique(['date', 'metric', 'dimension']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('preference_matching')->default(false);
            $table->json('limits')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('daily_metrics');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visits');
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
