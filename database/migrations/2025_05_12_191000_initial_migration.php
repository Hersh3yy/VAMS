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
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_admin')->default(false);
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->string('password');
            $table->json('album_display_settings')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('theme_settings')->nullable();
            $table->json('site_settings')->nullable();
            $table->string('api_key', 64)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Albums table
        Schema::create('albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Album images table
        Schema::create('album_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('album_id');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('author')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->string('location')->nullable();
            $table->string('tags')->nullable();
            $table->json('properties')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
        });

        // Mosaics table
        Schema::create('mosaics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('columns')->default(3);
            $table->json('display_settings')->nullable();
            $table->timestamps();
        });

        // Mosaic items table
        Schema::create('mosaic_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mosaic_id');
            $table->foreign('mosaic_id')->references('id')->on('mosaics')->onDelete('cascade');
            $table->integer('column_index')->default(0);
            $table->string('type'); // 'image', 'video', 'text', 'album'
            $table->json('content')->nullable(); // For storing image paths, video URLs, text content, etc.
            $table->uuid('album_id')->nullable(); // Reference to album if type is 'album'
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
            $table->json('properties')->nullable(); // Display settings, styling, etc.
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Jobs table for queues
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Job batches table
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Personal access tokens table
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('album_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('album_id');
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('mosaic_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('mosaic_id');
            $table->foreign('mosaic_id')->references('id')->on('mosaics')->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('description');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->uuidMorphs('subject');
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('mosaic_media');
        Schema::dropIfExists('album_media');
        Schema::dropIfExists('media');
        Schema::dropIfExists('mosaic_items');
        Schema::dropIfExists('mosaics');
        Schema::dropIfExists('album_images');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('users');
    }
}; 