<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->id();
            // All fields – completely standalone
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('code_no')->nullable();
            $table->string('status')->nullable(); // text box, not dropdown
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('academic_grade')->nullable(); // from dropdown
            $table->string('qualification')->nullable(); // from dropdown
            $table->string('course')->nullable(); // Education field
            $table->enum('domestic_international', ['domestic', 'international'])->nullable();
            $table->date('academic_grade_entrence_date')->nullable();
            $table->date('promotion_date')->nullable(); // recent promotion
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lecturer_profiles');
    }
};
