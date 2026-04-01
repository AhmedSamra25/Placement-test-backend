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
        Schema::create('cefr_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');          // e.g. Starter, Elementary
            $table->string('cefr_map');      // e.g. A1, A2, B1, B2, C1, C2
            $table->unsignedSmallInteger('score_min')->default(0);
            $table->unsignedSmallInteger('score_max')->default(100);
            $table->text('goals')->nullable();
            $table->string('color', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cefr_levels');
    }
};
