<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->uuid('supabase_id')->nullable()->unique();
            $table->uuid('supabase_auth_user_id')->nullable()->unique();
            $table->string('customer_number', 64)->unique();
            $table->string('customer_type', 32);
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('business_name', 255)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32);
            $table->string('alternative_phone', 32)->nullable();
            $table->string('national_id', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->unsignedInteger('sync_version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('national_id');
            $table->index('registration_number');
            $table->index(['customer_type', 'status']);
        });

        Schema::create('tariff_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('customer_class', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('water_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->uuid('supabase_id')->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tariff_category_id')->constrained()->restrictOnDelete();
            $table->string('ip_number', 64)->unique();
            $table->string('account_name');
            $table->text('service_address');
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('street', 150)->nullable();
            $table->string('plot_number', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('connection_type', 64)->nullable();
            $table->string('account_class', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(7);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_billed_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->unsignedInteger('sync_version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'status']);
            $table->index(['zone_id', 'status']);
            $table->index(['tariff_category_id', 'status']);
            $table->index(['region', 'district', 'ward']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_accounts');
        Schema::dropIfExists('tariff_categories');
        Schema::dropIfExists('customers');
    }
};
