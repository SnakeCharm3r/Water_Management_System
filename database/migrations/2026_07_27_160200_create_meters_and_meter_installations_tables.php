<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->uuid('supabase_id')->nullable()->unique();
            $table->string('meter_number', 100)->unique();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('meter_size', 64)->nullable();
            $table->string('meter_type', 32);
            $table->string('unit_of_measurement', 32)->default('cubic_meter');
            $table->date('manufactured_at')->nullable();
            $table->date('commissioned_at')->nullable();
            $table->string('status', 32)->default('available');
            $table->text('notes')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->unsignedInteger('sync_version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'meter_type']);
        });

        Schema::create('meter_installations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('water_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('meter_id')->constrained()->restrictOnDelete();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('installation_date');
            $table->decimal('initial_reading', 15, 3)->default(0);
            $table->date('removal_date')->nullable();
            $table->decimal('final_reading', 15, 3)->nullable();
            $table->string('installation_location', 255)->nullable();
            $table->string('seal_number', 100)->nullable();
            $table->unsignedBigInteger('reading_sequence_start')->nullable();
            $table->decimal('meter_multiplier', 12, 4)->default(1);
            $table->string('status', 32)->default('active');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['water_account_id', 'is_active']);
            $table->index(['meter_id', 'is_active']);
            $table->index(['water_account_id', 'status']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('meter_installations', function (Blueprint $table) {
                $table->string('active_account_key', 64)->storedAs("IF(`is_active` = 1 AND `deleted_at` IS NULL, CAST(`water_account_id` AS CHAR), NULL)");
                $table->string('active_meter_key', 64)->storedAs("IF(`is_active` = 1 AND `deleted_at` IS NULL, CAST(`meter_id` AS CHAR), NULL)");
                $table->unique('active_account_key', 'meter_installations_one_active_account');
                $table->unique('active_meter_key', 'meter_installations_one_active_meter');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_installations');
        Schema::dropIfExists('meters');
    }
};
