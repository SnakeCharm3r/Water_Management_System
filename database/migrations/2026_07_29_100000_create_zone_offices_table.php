<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_offices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('office_type', 64)->default('customer_care');
            $table->text('address')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            // Geographic coordinates
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // UTM / local grid coordinates (Tanzania uses metres)
            $table->decimal('easting', 12, 2)->nullable();
            $table->decimal('northing', 12, 2)->nullable();
            $table->string('utm_zone', 8)->nullable();
            // Operating hours
            $table->time('opening_time')->default('08:00');
            $table->time('closing_time')->default('16:30');
            $table->json('opening_days')->nullable();
            $table->boolean('is_main_office')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['zone_id', 'is_active']);
            $table->index(['is_main_office', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_offices');
    }
};
