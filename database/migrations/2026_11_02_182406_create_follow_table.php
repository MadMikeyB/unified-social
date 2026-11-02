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
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignUuid('followee_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('status')->default('accepted'); // pending, accepted
            $table->timestamps();

            $table->unique(['follower_id', 'followee_id']);
            $table->index(['followee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
