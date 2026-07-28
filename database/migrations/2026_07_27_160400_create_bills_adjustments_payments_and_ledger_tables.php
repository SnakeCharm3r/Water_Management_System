<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->uuid('supabase_id')->nullable()->unique();
            $table->foreignId('billing_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('water_account_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 64)->unique();
            $table->string('bill_number', 64)->unique();
            $table->unsignedInteger('revision_number')->default(1);
            $table->foreignId('revised_bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->string('account_number_snapshot', 64);
            $table->string('customer_name_snapshot');
            $table->text('property_snapshot');
            $table->string('tariff_category_snapshot', 128);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date');
            $table->timestamp('printed_at')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_charges', 15, 2)->default(0);
            $table->decimal('adjustment_total', 15, 2)->default(0);
            $table->decimal('penalty_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('credit_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->string('currency', 3)->default('TZS');
            $table->string('status', 32)->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->unsignedInteger('sync_version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['water_account_id', 'billing_cycle_id', 'revision_number'], 'bills_account_cycle_revision_unique');
            $table->index(['water_account_id', 'status']);
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->foreignId('meter_reading_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tariff_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 32);
            $table->string('description');
            $table->date('service_date')->nullable();
            $table->string('meter_number_snapshot', 100)->nullable();
            $table->string('category_snapshot', 128)->nullable();
            $table->string('reading_type_snapshot', 32)->nullable();
            $table->decimal('previous_reading', 15, 3)->nullable();
            $table->decimal('current_reading', 15, 3)->nullable();
            $table->decimal('consumption', 15, 3)->nullable();
            $table->decimal('unit_rate', 15, 6)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('amount', 15, 2);
            $table->json('calculation_details')->nullable();
            $table->unsignedInteger('sequence');
            $table->timestamps();
            $table->unique(['bill_id', 'sequence']);
        });

        Schema::create('bill_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('water_account_id')->constrained()->restrictOnDelete();
            $table->string('adjustment_type', 32);
            $table->string('code', 64)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('description');
            $table->date('adjustment_date');
            $table->decimal('amount', 15, 2);
            $table->string('status', 32)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->index(['water_account_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->uuid('supabase_id')->nullable()->unique();
            $table->foreignId('water_account_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number', 64)->nullable()->unique();
            $table->string('control_number', 100)->nullable()->index();
            $table->string('provider_reference', 191)->nullable()->unique();
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->timestamp('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('payment_method', 32);
            $table->string('payment_channel', 64)->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_phone', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->json('raw_callback')->nullable();
            $table->timestamps();
            $table->index(['water_account_id', 'status']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamp('allocated_at');
            $table->timestamps();
            $table->unique(['payment_id', 'bill_id']);
        });

        Schema::create('account_ledger_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_uuid')->unique();
            $table->foreignId('water_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('adjustment_id')->nullable()->constrained('bill_adjustments')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('account_ledger_entries')->nullOnDelete();
            $table->date('entry_date');
            $table->string('entry_type', 32);
            $table->string('reference_number', 100);
            $table->text('description');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('idempotency_key', 191)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['water_account_id', 'entry_date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_ledger_entries');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bill_adjustments');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
