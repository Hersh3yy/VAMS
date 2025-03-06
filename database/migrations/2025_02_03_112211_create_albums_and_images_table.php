<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->string('cover_image_path')->nullable();
            $table->json('metadata')->nullable(); // For future extensibility
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('album_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('album_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('altText')->nullable();
            $table->text('caption')->nullable();
            $table->text('author')->nullable();
            $table->timestamp('dateCreated')->nullable();
            $table->string('location')->nullable();
            $table->string('tags')->nullable();
            $table->integer('order')->default(0);
            $table->json('properties')->nullable(); // For custom properties
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('album_images');
        Schema::dropIfExists('albums');
    }
}; 