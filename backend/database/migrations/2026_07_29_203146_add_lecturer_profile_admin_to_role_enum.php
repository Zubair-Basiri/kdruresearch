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
        // For MySQL
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'admin', 'super_admin', 'lecturer_profile_admin') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'admin', 'super_admin') NOT NULL");
    }
};
