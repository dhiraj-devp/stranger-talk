<?php

use Database\Seeders\IndiaSeo;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new IndiaSeo)->sync();
    }

    public function down(): void
    {
        // Published copy and unpublished drafts are left in place so a rollback does not delete editorial content.
    }
};
