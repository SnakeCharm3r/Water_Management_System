<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fname', 100)->default('')->after('public_uuid');
            $table->string('mname', 100)->default('')->after('fname');
            $table->string('lname', 100)->default('')->after('mname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fname', 'mname', 'lname']);
        });
    }
};
