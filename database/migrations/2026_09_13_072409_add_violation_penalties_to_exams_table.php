<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('tab_switch_penalty_seconds')
                ->nullable()
                ->after('duration');

            $table->unsignedInteger('fullscreen_exit_penalty_seconds')
                ->nullable()
                ->after('tab_switch_penalty_seconds');

            $table->unsignedInteger('copy_attempt_penalty_seconds')
                ->nullable()
                ->after('fullscreen_exit_penalty_seconds');

            $table->unsignedInteger('paste_attempt_penalty_seconds')
                ->nullable()
                ->after('copy_attempt_penalty_seconds');

            $table->unsignedInteger('idle_penalty_seconds')
                ->nullable()
                ->after('paste_attempt_penalty_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'tab_switch_penalty_seconds',
                'fullscreen_exit_penalty_seconds',
                'copy_attempt_penalty_seconds',
                'paste_attempt_penalty_seconds',
                'idle_penalty_seconds',
            ]);
        });
    }
};
