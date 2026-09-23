<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_bank_question_classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_bank_question_id')
                ->constrained('test_bank_questions')
                ->cascadeOnDelete();

            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'test_bank_question_id',
                'class_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'test_bank_question_classes'
        );
    }
};
