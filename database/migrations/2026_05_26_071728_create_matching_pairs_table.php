<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('matching_pairs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('question_id')
                  ->constrained('questions')
                  ->onDelete('cascade');

            $table->string('left_item');

            $table->string('right_item');

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matching_pairs');
    }
};
