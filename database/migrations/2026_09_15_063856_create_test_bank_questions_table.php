<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_bank_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('faculty_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade');

            $table->text('question');

            $table->enum('question_type', [
                'multiple_choice',
                'true_false',
                'identification'
            ]);

            $table->text('competency')->nullable();
            $table->text('answer')->nullable();
            $table->integer('points')->default(1);
            $table->timestamps();

            $table->index(['faculty_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_bank_questions');
    }
};
