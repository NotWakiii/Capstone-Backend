<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->text('details')->nullable()->after('activity');
            $table->unsignedInteger('duration_seconds')->nullable()->after('details');
        });

        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->dropColumn([
                'details',
                'duration_seconds',
            ]);
        });

        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable(false)->change();
        });
    }
};
