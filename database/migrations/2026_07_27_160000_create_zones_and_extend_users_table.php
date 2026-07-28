<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('zone_type', 32);
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['parent_id', 'is_active']);
            $table->index(['zone_type', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_uuid')->nullable()->unique()->after('id');
            $table->foreignId('zone_id')->nullable()->after('public_uuid')->constrained('zones')->nullOnDelete();
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('employee_number', 64)->nullable()->unique()->after('phone');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
            $table->index(['zone_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropIndex(['zone_id', 'is_active']);
            $table->dropUnique(['public_uuid']);
            $table->dropUnique(['employee_number']);
            $table->dropColumn(['public_uuid', 'zone_id', 'phone', 'employee_number', 'is_active', 'last_login_at', 'deleted_at']);
        });

        Schema::dropIfExists('zones');
    }
};
