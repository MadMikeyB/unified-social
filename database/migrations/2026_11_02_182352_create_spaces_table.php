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
        Schema::create('spaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->constrained('spaces')->nullOnDelete();
            $table->nullableUuidMorphs('owner'); // a profile's wall, a group's creator
            $table->string('kind'); // wall, community, board, category, group
            $table->string('name');
            $table->string('slug')->unique(); // rewritten on soft delete so it can be reused
            $table->text('description')->nullable();
            $table->string('visibility')->default('public'); // public, members, private
            $table->boolean('requires_approval')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->json('settings')->nullable(); // namespaced per front end
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kind', 'parent_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
