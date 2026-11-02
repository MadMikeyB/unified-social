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
        Schema::create('pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('pinner'); // profile or space
            $table->uuidMorphs('pinnable'); // post or profile
            $table->string('kind'); // pinned, featured
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['pinner_type', 'pinner_id', 'pinnable_type', 'pinnable_id', 'kind'], 'pins_unique');
            $table->index(['pinner_type', 'pinner_id', 'kind', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};
