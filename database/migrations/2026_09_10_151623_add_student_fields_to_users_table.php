<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('lrn', 20)->nullable()->unique()->after('id');
            $table->enum('sex', ['Male', 'Female'])->nullable()->after('email');
            $table->foreignId('strand_id')
                ->nullable()
                ->after('sex')
                ->constrained('strands')
                ->restrictOnDelete();
            $table->foreignId('section_id')
                ->nullable()
                ->after('strand_id')
                ->constrained('sections')
                ->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropForeign(['strand_id']);
            $table->dropColumn([
                'lrn',
                'sex',
                'strand_id',
                'section_id',
            ]);
        });
    }
};
