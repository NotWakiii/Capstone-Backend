<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'test_bank_question_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->foreignId('test_bank_question_id')
                    ->nullable()
                    ->after('exam_id')
                    ->constrained('test_bank_questions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'test_bank_question_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['test_bank_question_id']);
                $table->dropColumn('test_bank_question_id');
            });
        }
    }
};
