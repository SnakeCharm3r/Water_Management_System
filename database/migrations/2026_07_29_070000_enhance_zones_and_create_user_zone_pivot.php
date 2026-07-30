<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance zones table with geographical and auditing fields
        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'region')) {
                $table->string('region', 128)->nullable()->after('name');
            }
            if (! Schema::hasColumn('zones', 'district')) {
                $table->string('district', 128)->nullable()->after('region');
            }
            if (! Schema::hasColumn('zones', 'status')) {
                $table->string('status', 32)->default('active')->after('is_active');
                $table->index('status');
            }
            if (! Schema::hasColumn('zones', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('zones', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            $table->index(['code', 'status']);
            $table->index(['parent_id', 'status']);
        });

        // Sync legacy is_active with status column
        DB::statement("UPDATE zones SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");

        // Create user_zone pivot for many-to-many staff-zone assignments
        if (! Schema::hasTable('user_zone')) {
            Schema::create('user_zone', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'zone_id']);
                $table->index(['user_id', 'is_primary']);
                $table->index(['zone_id', 'is_primary']);
            });
        }

        // Seed pivot table from legacy single-zone assignments
        if (Schema::hasColumn('users', 'zone_id')) {
            DB::table('user_zone')->insertUsing(
                ['user_id', 'zone_id', 'is_primary', 'assigned_by', 'assigned_at', 'created_at', 'updated_at'],
                DB::table('users')
                    ->whereNotNull('zone_id')
                    ->select([
                        'id as user_id',
                        'zone_id',
                        DB::raw('1 as is_primary'),
                        DB::raw('NULL as assigned_by'),
                        DB::raw('NOW() as assigned_at'),
                        DB::raw('NOW() as created_at'),
                        DB::raw('NOW() as updated_at'),
                    ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_zone');

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumnIfExists('region');
            $table->dropColumnIfExists('district');
            $table->dropColumnIfExists('status');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropIndexIfExists(['code', 'status']);
            $table->dropIndexIfExists(['parent_id', 'status']);
        });
    }
};
