<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {

            if (Schema::hasColumn('exam_sessions', 'student_id')) {
                $table->dropForeign(['student_id']);
                $table->dropColumn('student_id');
            }

            if (!Schema::hasColumn('exam_sessions', 'student_name')) {
                $table->string('student_name')->after('exam_id');
            }

            if (!Schema::hasColumn('exam_sessions', 'progress')) {
                $table->integer('progress')->default(0)->after('score');
            }

        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {

            if (!Schema::hasColumn('exam_sessions', 'student_id')) {
                $table->foreignId('student_id')
                    ->after('exam_id')
                    ->constrained('users')
                    ->onDelete('cascade');
            }

            if (Schema::hasColumn('exam_sessions', 'student_name')) {
                $table->dropColumn('student_name');
            }

            if (Schema::hasColumn('exam_sessions', 'progress')) {
                $table->dropColumn('progress');
            }

        });
    }
};