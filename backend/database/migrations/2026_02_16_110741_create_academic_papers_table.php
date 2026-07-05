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
        Schema::create('academic_papers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('lecturer_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->string('publication');
            $table->string('indexed');
            $table->integer('citation')->default(0);
            $table->string('funding')->nullable();
            $table->string('collaboration')->nullable();
            $table->string('language');
            $table->string('status');
            $table->string('author_position');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_papers');
    }
};
