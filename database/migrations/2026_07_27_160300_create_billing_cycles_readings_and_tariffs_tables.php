<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->string('cycle_code', 64)->unique();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('reading_start_date')->nullable();
            $table->date('reading_end_date')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 32)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['period_start', 'period_end']);
            $table->index(['status', 'issue_date']);
        });

        Schema::create('meter_readings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('meter_installation_id')->constrained()->restrictOnDelete();
            $table->foreignId('billing_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_reading_id')->nullable()->constrained('meter_readings')->nullOnDelete();
            $table->date('reading_date');
            $table->decimal('previous_reading', 15, 3);
            $table->decimal('current_reading', 15, 3);
            $table->decimal('consumption', 15, 3);
            $table->string('reading_type', 32);
            $table->string('reading_status', 32)->default('submitted');
            $table->foreignId('reader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('exception_code', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['meter_installation_id', 'billing_cycle_id'], 'readings_installation_cycle_unique');
            $table->index(['billing_cycle_id', 'reading_status']);
        });

        Schema::create('tariff_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('tariff_category_id')->constrained()->restrictOnDelete();
            $table->string('charge_type', 32);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('minimum_charge', 15, 2)->default(0);
            $table->decimal('fixed_charge', 15, 2)->default(0);
            $table->decimal('unit_rate', 15, 6)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tariff_category_id', 'charge_type', 'effective_from'], 'tariff_rates_effective_lookup');
        });

        Schema::create('tariff_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('tariff_rate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->decimal('from_quantity', 15, 3);
            $table->decimal('to_quantity', 15, 3)->nullable();
            $table->decimal('rate_per_unit', 15, 6);
            $table->timestamps();
            $table->unique(['tariff_rate_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_blocks');
        Schema::dropIfExists('tariff_rates');
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('billing_cycles');
    }
};
