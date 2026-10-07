<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_program_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');   // 1, 2, 3
            $table->string('day_label');                 // "Jour 1"
            $table->string('date_label');                // "12 déc."
            $table->time('starts_at');                    // 09:00
            $table->string('title');
            $table->string('speaker')->nullable();
            $table->string('tag')->nullable();            // Keynote, Atelier, …
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'day_number', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_program_items');
    }
};
