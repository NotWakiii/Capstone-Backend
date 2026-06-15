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
        Schema::create('monitor_logs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('activity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_logs');
    }
};
