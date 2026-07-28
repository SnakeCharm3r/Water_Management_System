<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillingCycle;
use App\Models\Customer;
use App\Models\MeterInstallation;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Models\WaterAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsService
{
    private const TTL = 120; // seconds

    public function __construct(
        private readonly ZoneAccessService $zoneAccess,
    ) {}

    // ── helpers ──────────────────────────────────────────────

    private function effectiveZoneIds(User $user, DashboardFilters $filters): array
    {
        $accessible = $this->zoneAccess->accessibleZoneIds($user);

        if ($filters->zoneId !== null && in_array($filters->zoneId, $accessible, true)) {
            return [$filters->zoneId];
        }

        return $accessible;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'system-admin']);
    }

    private function scopeByZones($query, array $zoneIds, string $col = 'zone_id', bool $isAdmin = false)
    {
        if ($isAdmin && count($zoneIds) === 0) {
            return $query;
        }
        if (!$isAdmin || count($zoneIds) > 0) {
            $query->whereIn($col, $zoneIds);
        }
        return $query;
    }

    private function cached(string $key, callable $callback)
    {
        return Cache::remember($key, self::TTL, $callback);
    }

    // ── summary cards ───────────────────────────────────────

    public function summaryCards(User $user, DashboardFilters $filters): array
    {
        $zones = $this->effectiveZoneIds($user, $filters);
        $admin = $this->isAdmin($user);
        $key = $filters->cacheKey('dash:summary', $zones);

        return $this->cached($key, function () use ($zones, $admin, $filters) {
            $accountQuery = fn () => WaterAccount::query()
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereIn('zone_id', $zones))
                ->when($filters->tariffCategoryId, fn ($q, $v) => $q->where('tariff_category_id', $v));

            $activeCustomers = Customer::query()
                ->where('status', 'active')
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('waterAccounts', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->count();

            $activeAccounts = $accountQuery()->where('status', 'active')->count();
            $installedMeters = MeterInstallation::query()
                ->where('is_active', true)
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('waterAccount', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->when($filters->tariffCategoryId, fn ($q, $v) => $q->whereHas('waterAccount', fn ($sq) => $sq->where('tariff_category_id', $v)))
                ->count();

            $receivables = Bill::query()
                ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->when($filters->tariffCategoryId, fn ($q, $v) => $q->whereHas('account', fn ($sq) => $sq->where('tariff_category_id', $v)))
                ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(balance_due), 0) as total_outstanding')
                ->first();

            // Previous period comparison (30 days ago snapshot approximation)
            $prevCustomers = Customer::query()
                ->where('status', 'active')
                ->where('created_at', '<', now()->subDays(30))
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('waterAccounts', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->count();

            $prevAccounts = $accountQuery()->where('status', 'active')->where('created_at', '<', now()->subDays(30))->count();

            return [
                [
                    'label' => 'Active customers',
                    'value' => $activeCustomers,
                    'prev' => $prevCustomers,
                    'change' => $this->pctChange($prevCustomers, $activeCustomers),
                    'description' => 'Registered customers with active service status',
                    'icon' => 'users',
                    'link' => '#customers',
                ],
                [
                    'label' => 'Active accounts',
                    'value' => $activeAccounts,
                    'prev' => $prevAccounts,
                    'change' => $this->pctChange($prevAccounts, $activeAccounts),
                    'description' => 'Service accounts currently receiving water',
                    'icon' => 'droplet',
                    'link' => '#water-accounts',
                ],
                [
                    'label' => 'Installed meters',
                    'value' => $installedMeters,
                    'prev' => 0,
                    'change' => null,
                    'description' => 'Active meters assigned to service accounts',
                    'icon' => 'gauge',
                    'link' => '#meters',
                ],
                [
                    'label' => 'Outstanding receivables',
                    'value' => (float) ($receivables->total_outstanding ?? 0),
                    'prev' => 0,
                    'change' => null,
                    'description' => number_format((int) ($receivables->bill_count ?? 0)) . ' open bills',
                    'icon' => 'banknotes',
                    'link' => '#bills',
                    'is_currency' => true,
                ],
            ];
        });
    }

    private function pctChange(int $prev, int $curr): ?float
    {
        if ($prev === 0) {
            return null;
        }
        return round((($curr - $prev) / $prev) * 100, 1);
    }

    // ── alerts ──────────────────────────────────────────────

    public function alerts(User $user, DashboardFilters $filters): array
    {
        $zones = $this->effectiveZoneIds($user, $filters);
        $admin = $this->isAdmin($user);
        $key = $filters->cacheKey('dash:alerts', $zones);

        return $this->cached($key, function () use ($zones, $admin, $filters) {
            $alerts = [];

            // Readings awaiting verification
            $pendingReadings = MeterReading::query()
                ->where('reading_status', 'submitted')
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('installation.waterAccount', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->count();
            if ($pendingReadings > 0) {
                $alerts[] = ['label' => 'Readings awaiting verification', 'count' => $pendingReadings, 'severity' => 'amber', 'description' => $pendingReadings . ' submitted reading(s) need review', 'link' => '#readings?status=submitted'];
            }

            // Overdue accounts (>30 days)
            $overdueAccounts = Bill::query()
                ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
                ->where('due_date', '<', now()->subDays(30))
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->distinct('water_account_id')
                ->count('water_account_id');
            if ($overdueAccounts > 0) {
                $alerts[] = ['label' => 'Accounts overdue >30 days', 'count' => $overdueAccounts, 'severity' => 'red', 'description' => $overdueAccounts . ' account(s) with overdue receivables', 'link' => '#bills?overdue=30'];
            }

            // Faulty meters
            $faultyMeters = DB::table('meters')->where('status', 'faulty')->count();
            if ($faultyMeters > 0) {
                $alerts[] = ['label' => 'Faulty meters', 'count' => $faultyMeters, 'severity' => 'red', 'description' => $faultyMeters . ' meter(s) reported faulty', 'link' => '#meters?status=faulty'];
            }

            // Accounts without active meters
            $noMeter = WaterAccount::query()
                ->where('status', 'active')
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereIn('zone_id', $zones))
                ->whereDoesntHave('installations', fn ($q) => $q->where('is_active', true))
                ->count();
            if ($noMeter > 0) {
                $alerts[] = ['label' => 'Accounts without meter', 'count' => $noMeter, 'severity' => 'amber', 'description' => $noMeter . ' active account(s) lack an installed meter', 'link' => '#water-accounts?no_meter=1'];
            }

            // Unallocated confirmed payments
            $unallocated = Payment::query()
                ->where('status', 'confirmed')
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->whereDoesntHave('allocations')
                ->count();
            if ($unallocated > 0) {
                $alerts[] = ['label' => 'Unallocated payments', 'count' => $unallocated, 'severity' => 'amber', 'description' => $unallocated . ' confirmed payment(s) not yet allocated', 'link' => '#payments?unallocated=1'];
            }

            // Failed sync events
            $failedSync = DB::table('integration_outbox')->where('status', 'failed')->count();
            if ($failedSync > 0) {
                $alerts[] = ['label' => 'Failed synchronization', 'count' => $failedSync, 'severity' => 'red', 'description' => $failedSync . ' outbox event(s) failed delivery', 'link' => '#sync?status=failed'];
            }

            if (empty($alerts)) {
                $alerts[] = ['label' => 'All clear', 'count' => 0, 'severity' => 'green', 'description' => 'No items require attention', 'link' => null];
            }

            return $alerts;
        });
    }

    // ── reading progress ────────────────────────────────────

    public function readingProgress(User $user, DashboardFilters $filters): array
    {
        $zones = $this->effectiveZoneIds($user, $filters);
        $admin = $this->isAdmin($user);
        $key = $filters->cacheKey('dash:reading', $zones);

        return $this->cached($key, function () use ($zones, $admin, $filters) {
            $cycle = $filters->billingCycleId
                ? BillingCycle::find($filters->billingCycleId)
                : BillingCycle::where('status', '!=', 'closed')->orderByDesc('period_start')->first();

            if (!$cycle) {
                return ['cycle' => null, 'total' => 0, 'completed' => 0, 'submitted' => 0, 'rejected' => 0, 'remaining' => 0, 'pct' => 0];
            }

            $totalExpected = MeterInstallation::query()
                ->where('is_active', true)
                ->where('installation_date', '<=', $cycle->period_end)
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('waterAccount', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->count();

            $readings = MeterReading::query()
                ->where('billing_cycle_id', $cycle->id)
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('installation.waterAccount', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->selectRaw("
                    COUNT(*) as total_readings,
                    SUM(CASE WHEN reading_status IN ('verified','billed') THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN reading_status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN reading_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                ")->first();

            $completed = (int) ($readings->completed ?? 0);
            $submitted = (int) ($readings->submitted ?? 0);
            $rejected = (int) ($readings->rejected ?? 0);
            $remaining = max(0, $totalExpected - ($completed + $submitted + $rejected));
            $pct = $totalExpected > 0 ? round(($completed / $totalExpected) * 100, 1) : 0;

            return [
                'cycle' => $cycle,
                'total' => $totalExpected,
                'completed' => $completed,
                'submitted' => $submitted,
                'rejected' => $rejected,
                'remaining' => $remaining,
                'pct' => $pct,
            ];
        });
    }

    // ── billing vs collections chart ────────────────────────

    public function billingCollections(User $user, DashboardFilters $filters): array
    {
        $zones = $this->effectiveZoneIds($user, $filters);
        $admin = $this->isAdmin($user);
        $key = $filters->cacheKey('dash:billcol', $zones);

        return $this->cached($key, function () use ($zones, $admin) {
            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $months->push(CarbonImmutable::now()->subMonths($i)->startOfMonth());
            }

            $billed = Bill::query()
                ->whereNotIn('status', ['draft', 'voided', 'cancelled'])
                ->whereNotNull('issued_at')
                ->where('issued_at', '>=', $months->first())
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->selectRaw("DATE_FORMAT(issued_at, '%Y-%m') as month, SUM(total_amount) as total")
                ->groupBy('month')
                ->pluck('total', 'month');

            $collected = Payment::query()
                ->where('status', 'confirmed')
                ->whereNotNull('confirmed_at')
                ->where('confirmed_at', '>=', $months->first())
                ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
                ->selectRaw("DATE_FORMAT(confirmed_at, '%Y-%m') as month, SUM(amount) as total")
                ->groupBy('month')
                ->pluck('total', 'month');

            return $months->map(function (CarbonImmutable $m) use ($billed, $collected) {
                $key = $m->format('Y-m');
                $b = (float) ($billed[$key] ?? 0);
                $c = (float) ($collected[$key] ?? 0);
                return [
                    'label' => $m->format('M Y'),
                    'billed' => $b,
                    'collected' => $c,
                    'rate' => $b > 0 ? round(($c / $b) * 100, 1) : 0,
                ];
            })->values()->toArray();
        });
    }

    // ── recent payments ─────────────────────────────────────

    public function recentPayments(User $user, DashboardFilters $filters, int $limit = 8): Collection
    {
        $zones = $this->effectiveZoneIds($user, $filters);
        $admin = $this->isAdmin($user);

        return Payment::query()
            ->with(['account:id,ip_number,customer_id,zone_id', 'account.customer:id,first_name,last_name,business_name,customer_type'])
            ->when(!$admin || count($zones) > 0, fn ($q) => $q->whereHas('account', fn ($sq) => $sq->whereIn('zone_id', $zones)))
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get();
    }

    // ── quick actions ───────────────────────────────────────

    public function quickActions(User $user): array
    {
        $actions = [
            ['label' => 'Register customer', 'permission' => 'customers.create', 'icon' => 'user-plus', 'route' => '#customers/create'],
            ['label' => 'Create water account', 'permission' => 'water-accounts.create', 'icon' => 'droplet', 'route' => '#water-accounts/create'],
            ['label' => 'Install meter', 'permission' => 'meters.install', 'icon' => 'gauge', 'route' => '#meters/install'],
            ['label' => 'Capture reading', 'permission' => 'meter-readings.submit', 'icon' => 'clipboard', 'route' => '#readings/create'],
            ['label' => 'Record payment', 'permission' => 'payments.confirm', 'icon' => 'banknotes', 'route' => '#payments/create'],
            ['label' => 'Open billing cycle', 'permission' => 'billing-cycles.manage', 'icon' => 'calendar', 'route' => '#billing-cycles/create'],
        ];

        return array_values(array_filter($actions, fn ($a) => $user->can($a['permission'])));
    }

    // ── active billing cycle ────────────────────────────────

    public function activeBillingCycle(?int $cycleId = null): ?BillingCycle
    {
        if ($cycleId) {
            return BillingCycle::find($cycleId);
        }
        return BillingCycle::where('status', '!=', 'closed')->orderByDesc('period_start')->first();
    }

    // ── navigation ──────────────────────────────────────────

    public function navigation(User $user): array
    {
        $groups = [
            ['group' => 'Workspace', 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view', 'icon' => 'home'],
            ]],
            ['group' => 'Operations', 'items' => [
                ['label' => 'Customers', 'route' => '#customers', 'permission' => 'customers.view', 'icon' => 'users'],
                ['label' => 'Water Accounts', 'route' => '#water-accounts', 'permission' => 'water-accounts.view', 'icon' => 'droplet'],
                ['label' => 'Meters', 'route' => '#meters', 'permission' => 'meters.view', 'icon' => 'gauge'],
                ['label' => 'Meter Readings', 'route' => '#readings', 'permission' => 'meter-readings.view', 'icon' => 'clipboard'],
            ]],
            ['group' => 'Billing', 'items' => [
                ['label' => 'Billing Cycles', 'route' => '#billing-cycles', 'permission' => 'billing-cycles.manage', 'icon' => 'calendar'],
                ['label' => 'Bills', 'route' => '#bills', 'permission' => 'bills.view', 'icon' => 'file-text'],
                ['label' => 'Adjustments', 'route' => '#adjustments', 'permission' => 'adjustments.request', 'icon' => 'sliders'],
                ['label' => 'Tariffs', 'route' => '#tariffs', 'permission' => 'tariffs.view', 'icon' => 'layers'],
            ]],
            ['group' => 'Finance', 'items' => [
                ['label' => 'Payments', 'route' => '#payments', 'permission' => 'payments.view', 'icon' => 'banknotes'],
                ['label' => 'Allocations', 'route' => '#allocations', 'permission' => 'payments.allocate', 'icon' => 'git-branch'],
                ['label' => 'Account Ledger', 'route' => '#ledger', 'permission' => 'ledger.view', 'icon' => 'book-open'],
            ]],
            ['group' => 'Reports', 'items' => [
                ['label' => 'Operational', 'route' => '#reports/operational', 'permission' => 'dashboard.view', 'icon' => 'bar-chart'],
                ['label' => 'Financial', 'route' => '#reports/financial', 'permission' => 'ledger.view', 'icon' => 'trending-up'],
            ]],
            ['group' => 'Administration', 'items' => [
                ['label' => 'Staff', 'route' => 'staff.index', 'permission' => 'staff-users.view', 'icon' => 'shield'],
                ['label' => 'Roles', 'route' => 'roles.index', 'permission' => 'roles.view', 'icon' => 'key'],
                ['label' => 'Zones', 'route' => '#zones', 'permission' => 'zones.view', 'icon' => 'map-pin'],
                ['label' => 'Billing Settings', 'route' => '#billing-settings', 'permission' => 'billing-settings.manage', 'icon' => 'settings'],
                ['label' => 'Audit Logs', 'route' => '#audit-logs', 'permission' => 'audit-logs.view', 'icon' => 'scroll'],
            ]],
            ['group' => 'Integrations', 'items' => [
                ['label' => 'Supabase Sync', 'route' => '#sync', 'permission' => 'synchronization.monitor', 'icon' => 'refresh-cw'],
                ['label' => 'Failed Events', 'route' => '#sync/failed', 'permission' => 'synchronization.monitor', 'icon' => 'alert-triangle'],
            ]],
        ];

        return array_values(array_filter(array_map(function ($group) use ($user) {
            $items = array_values(array_filter($group['items'], fn ($i) => $user->can($i['permission'])));
            return $items === [] ? null : ['group' => $group['group'], 'items' => $items];
        }, $groups)));
    }

    // ── cache invalidation ──────────────────────────────────

    public static function invalidate(): void
    {
        Cache::forget('dash:summary:*');
        Cache::forget('dash:alerts:*');
        Cache::forget('dash:reading:*');
        Cache::forget('dash:billcol:*');

        // Pattern-based flush for database/redis cache stores
        if (method_exists(Cache::getStore(), 'flush')) {
            // For file/array stores, tags aren't available so we rely on TTL expiry
        }
    }
}
