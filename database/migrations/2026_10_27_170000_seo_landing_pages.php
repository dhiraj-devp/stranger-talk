<?php

use Database\Seeders\SeoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new SeoSeeder)->syncGrowth();
    }

    public function down(): void
    {
        // Published pages stay. Removing them would drop editorial URLs.
    }
};
