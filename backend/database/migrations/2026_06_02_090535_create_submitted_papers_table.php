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
        Schema::create('submitted_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->string('title');
            $table->integer('year');
            $table->string('publication');
            $table->string('indexed')->nullable();
            $table->integer('citation')->default(0);
            $table->string('funding')->nullable();
            $table->string('collaboration')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('author_position')->nullable();
            $table->text('admin_comment')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('submitted_papers');
    }
};
