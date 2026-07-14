<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedInteger('current_question')
                ->default(1)
                ->after('progress');

            $table->unsignedInteger('time_remaining')
                ->default(0)
                ->after('idle_seconds');

            $table->timestamp('last_seen_at')
                ->nullable()
                ->after('time_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'current_question',
                'time_remaining',
                'last_seen_at',
            ]);
        });
    }
};
