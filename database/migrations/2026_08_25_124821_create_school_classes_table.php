<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('faculty_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('grade');

            $table->string('section');

            $table->timestamps();

            $table->unique([
                'faculty_id',
                'grade',
                'section'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
