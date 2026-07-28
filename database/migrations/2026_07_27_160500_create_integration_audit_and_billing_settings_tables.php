<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('authority_name');
            $table->text('postal_address')->nullable();
            $table->string('telephone', 64)->nullable();
            $table->string('fax', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('vrn', 64)->nullable();
            $table->string('tin', 64)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->text('payment_instructions')->nullable();
            $table->text('bill_footer')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 64);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('route')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('aggregate_type');
            $table->unsignedBigInteger('aggregate_id');
            $table->uuid('aggregate_uuid');
            $table->string('operation', 32);
            $table->json('payload');
            $table->string('idempotency_key', 191)->unique();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('integration_inbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source', 64);
            $table->string('event_id', 191);
            $table->string('event_type', 64);
            $table->json('payload');
            $table->string('status', 32)->default('pending');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['source', 'event_id']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_inbox');
        Schema::dropIfExists('integration_outbox');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('billing_settings');
    }
};
