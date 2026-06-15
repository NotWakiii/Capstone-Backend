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
        Schema::create('questions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('exam_id')
                  ->constrained('exams')
                  ->onDelete('cascade');

            $table->text('question');

            $table->enum('question_type', [
                'multiple_choice',
                'true_false',
                'identification',
                'matching_type'
            ]);

            $table->text('answer')->nullable();

            $table->integer('points')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
