<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();

            $table->string('grade', 20);

            $table->foreignId('strand_id')
                ->constrained('strands')
                ->cascadeOnDelete();

            $table->string('section', 100);

            $table->timestamps();

            $table->unique([
                'grade',
                'strand_id',
                'section'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
