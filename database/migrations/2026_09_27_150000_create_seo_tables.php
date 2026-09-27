<?php

use Database\Seeders\SeoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('tagline')->nullable();
            $table->string('default_title');
            $table->text('default_description');
            $table->string('keywords')->nullable();
            $table->string('canonical_base')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_light')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('header_logo')->nullable();
            $table->string('footer_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('apple_touch_icon')->nullable();
            $table->string('web_app_icon')->nullable();
            $table->string('organization_name')->nullable();
            $table->text('organization_description')->nullable();
            $table->string('organization_logo')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('support_url')->nullable();
            $table->json('socials')->nullable();
            $table->string('theme_color')->default('#7c3aed');
            $table->string('language')->default('en');
            $table->string('locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('primary_color')->default('#7c3aed');
            $table->string('secondary_color')->default('#a78bfa');
            $table->text('footer_text')->nullable();
            $table->string('copyright')->nullable();
            $table->string('header_cta_label')->nullable();
            $table->string('header_cta_url')->nullable();
            $table->string('gsc_verification')->nullable();
            $table->string('ga_measurement_id')->nullable();
            $table->string('gtm_id')->nullable();
            $table->text('robots_extra')->nullable();
            $table->boolean('robots_disallow_all')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->text('body');
            $table->string('featured_image')->nullable();
            $table->string('image_alt')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical')->nullable()->index();
            $table->boolean('robots_index')->default(true)->index();
            $table->boolean('robots_follow')->default(true);
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('focus_topic')->nullable();
            $table->string('secondary_topics')->nullable();
            $table->string('breadcrumb_title')->nullable();
            $table->string('schema_type')->nullable();
            $table->text('json_ld')->nullable();
            $table->json('related_ids')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('content_tag', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['content_id', 'tag_id']);
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path')->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('enabled')->default(true)->index();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('location')->index();
            $table->string('label');
            $table->string('url');
            $table->string('group_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        (new SeoSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('content_tag');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('site_settings');
    }
};
