<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_bank_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_bank_question_id')
                ->constrained('test_bank_questions')
                ->onDelete('cascade');

            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_bank_question_options');
    }
};
