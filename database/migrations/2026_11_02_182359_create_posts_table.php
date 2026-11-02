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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('space_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignUuid('root_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignUuid('quoted_post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignUuid('reposted_post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->longText('body')->nullable();
            $table->string('visibility')->default('public'); // public, followers, friends, space
            $table->string('client')->nullable(); // which front end it was posted from
            $table->boolean('is_locked')->default(false);
            $table->integer('score')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->unsignedInteger('repost_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'root_id', 'last_activity_at']);
            $table->index(['space_id', 'root_id', 'score']);
            $table->index(['root_id', 'created_at']);
            $table->index(['parent_id', 'created_at']);
            $table->index(['profile_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
