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
        Schema::create('student_answers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('exam_session_id')
                  ->constrained('exam_sessions')
                  ->onDelete('cascade');

            $table->foreignId('question_id')
                  ->constrained('questions')
                  ->onDelete('cascade');

            $table->text('answer');

            $table->boolean('is_correct')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
