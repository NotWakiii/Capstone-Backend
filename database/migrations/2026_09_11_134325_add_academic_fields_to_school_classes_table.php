<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('school_year_id')
                ->nullable()
                ->after('faculty_id')
                ->constrained('school_years')
                ->restrictOnDelete();
            $table->string('semester', 30)
                ->nullable()
                ->after('school_year_id');
            $table->foreignId('strand_id')
                ->nullable()
                ->after('grade')
                ->constrained('strands')
                ->restrictOnDelete();
            $table->foreignId('section_id')
                ->nullable()
                ->after('strand_id')
                ->constrained('sections')
                ->restrictOnDelete();
            $table->foreignId('subject_id')
                ->nullable()
                ->after('section_id')
                ->constrained('subjects')
                ->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['strand_id']);
            $table->dropForeign(['school_year_id']);
            $table->dropColumn([
                'school_year_id',
                'semester',
                'strand_id',
                'section_id',
                'subject_id',
            ]);
        });
    }
};
