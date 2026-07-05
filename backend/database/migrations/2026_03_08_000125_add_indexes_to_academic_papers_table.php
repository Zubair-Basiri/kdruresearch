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
        Schema::table('academic_papers', function (Blueprint $table) {
            $table->index('lecturer_id');
            $table->index('year');
            $table->index('indexed');
            $table->index('publication');
            $table->index('status');
            $table->index('author_position');
            $table->index('citation');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->index('faculty_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_papers', function (Blueprint $table) {
            //
        });
    }
};
