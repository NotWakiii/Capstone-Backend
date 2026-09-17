<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('school_year_id')
                ->nullable()
                ->after('faculty_id');

            $table->string('semester')
                ->nullable()
                ->after('school_year_id');

            $table->unsignedBigInteger('strand_id')
                ->nullable()
                ->after('grade');

            $table->unsignedBigInteger('section_id')
                ->nullable()
                ->after('strand_id');

            $table->unsignedBigInteger('subject_id')
                ->nullable()
                ->after('section_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
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
