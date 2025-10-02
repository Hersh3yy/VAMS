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
        // Entry collections (user-configurable categories like "I AMS", "Blog Posts", etc.)
        Schema::create('entry_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "I AMS", "Blog Posts", "Recipes", etc.
            $table->string('slug'); // "i-ams", "blog-posts", "recipes"
            $table->text('description')->nullable();
            $table->json('field_config')->nullable(); // What fields are available for this collection
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'slug']);
        });

        // Entries - dynamic content within collections
        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('entry_collection_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('content'); // Dynamic content based on collection field_config
            $table->string('status')->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->integer('order')->default(0); // For drag & drop ordering within collection
            $table->timestamps();
            
            $table->index(['user_id', 'entry_collection_id', 'status']);
            $table->index(['entry_collection_id', 'order']);
            $table->index('published_at');
        });

        // Entry images (for entries that have image fields)
        Schema::create('entry_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entry_id')->constrained()->onDelete('cascade');
            $table->string('field_name'); // which field this image belongs to
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->integer('order')->default(0); // for multiple images in one field
            $table->json('properties')->nullable(); // video info, dimensions, etc.
            $table->timestamps();
            
            $table->index(['entry_id', 'field_name', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_images');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('entry_collections');
    }
};
