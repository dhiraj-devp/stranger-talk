<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 32)->nullable();
            $table->string('country_code', 2)->nullable()->index();
        });

        Schema::create('match_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gender_preference', 32)->default('anyone')->index();
            $table->string('country_preference', 2)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_preferences');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'country_code']);
        });
    }
};
