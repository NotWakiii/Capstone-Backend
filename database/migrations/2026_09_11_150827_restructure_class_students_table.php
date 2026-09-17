<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_students', function (Blueprint $table) {
            $table->index(
                'class_id',
                'class_students_class_id_index'
            );
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->dropUnique(
                'class_students_class_id_student_name_unique'
            );
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->dropColumn('student_name');
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->unique(
                ['class_id', 'student_id'],
                'class_students_class_student_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table) {
            $table->dropUnique(
                'class_students_class_student_unique'
            );
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->string('student_name')
                ->after('student_id');
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->unique(
                ['class_id', 'student_name'],
                'class_students_class_id_student_name_unique'
            );
        });

        Schema::table('class_students', function (Blueprint $table) {
            $table->dropIndex(
                'class_students_class_id_index'
            );
        });
    }
};
