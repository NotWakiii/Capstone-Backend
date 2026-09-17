<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {

            // Add a separate index for the faculty foreign key
            $table->index(
                'faculty_id',
                'school_classes_faculty_id_index'
            );
        });

        Schema::table('school_classes', function (Blueprint $table) {

            // Remove old unique rule
            $table->dropUnique(
                'school_classes_faculty_id_grade_section_unique'
            );

            // New unique rule
            $table->unique(
                [
                    'faculty_id',
                    'school_year_id',
                    'semester',
                    'grade',
                    'strand_id',
                    'section_id',
                    'subject_id',
                ],
                'school_classes_academic_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {

            // Remove new unique rule
            $table->dropUnique(
                'school_classes_academic_unique'
            );

            // Restore old unique rule
            $table->unique(
                [
                    'faculty_id',
                    'grade',
                    'section',
                ],
                'school_classes_faculty_id_grade_section_unique'
            );
        });

        Schema::table('school_classes', function (Blueprint $table) {

            // Remove separate faculty index
            $table->dropIndex(
                'school_classes_faculty_id_index'
            );
        });
    }
};
