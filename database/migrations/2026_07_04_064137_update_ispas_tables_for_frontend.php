<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('exams', function (Blueprint $table) {
    $table->string('course')->after('description')->nullable();
    $table->timestamp('started_at')->nullable()->after('status');
    $table->timestamp('ended_at')->nullable()->after('started_at');
});

Schema::table('questions', function (Blueprint $table) {
    $table->integer('time_limit')->default(30)->after('points');
    $table->integer('question_order')->default(1)->after('time_limit');
});

Schema::table('exam_sessions', function (Blueprint $table) {
    $table->integer('tab_switches')->default(0)->after('score');
    $table->integer('idle_seconds')->default(0)->after('tab_switches');
    $table->integer('time_spent')->default(0)->after('idle_seconds');
    $table->decimal('percentage', 5, 2)->default(0)->after('time_spent');
});

Schema::table('student_answers', function (Blueprint $table) {
    $table->integer('time_spent')->default(0)->after('is_correct');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('exams', function (Blueprint $table) {
    $table->dropColumn(['course', 'started_at', 'ended_at']);
});

Schema::table('questions', function (Blueprint $table) {
    $table->dropColumn(['time_limit', 'question_order']);
});

Schema::table('exam_sessions', function (Blueprint $table) {
    $table->dropColumn(['tab_switches', 'idle_seconds', 'time_spent', 'percentage']);
});

Schema::table('student_answers', function (Blueprint $table) {
    $table->dropColumn('time_spent');
});
    }
};
