<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('test_bank_questions', 'class_id')) {
            Schema::table('test_bank_questions', function (Blueprint $table) {
                $table->foreignId('class_id')
                    ->nullable()
                    ->after('faculty_id')
                    ->constrained('school_classes')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('test_bank_questions', 'class_id')) {
            Schema::table('test_bank_questions', function (Blueprint $table) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            });
        }
    }
};
