<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_students', function (Blueprint $table) {

            $table->id();

            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->cascadeOnDelete();

            $table->string('student_name');

            $table->timestamps();

            $table->unique([
                'class_id',
                'student_name'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_students');
    }
};
