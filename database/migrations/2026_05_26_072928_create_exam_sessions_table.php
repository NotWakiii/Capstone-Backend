<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('exam_id')
                  ->constrained('exams')
                  ->onDelete('cascade');

            $table->foreignId('student_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->integer('score')->default(0);

            $table->enum('status', [
                'ongoing',
                'submitted'
            ])->default('ongoing');

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
