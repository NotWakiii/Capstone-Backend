<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_bank_questions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('points');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('test_bank_questions', 'is_active')) {
            Schema::table('test_bank_questions', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
