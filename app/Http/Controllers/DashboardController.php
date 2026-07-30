<?php

namespace App\Http\Controllers;

use App\Models\BillingCycle;
use App\Models\TariffCategory;
use App\Models\Zone;
use App\Services\DashboardFilters;
use App\Services\DashboardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $stats,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $filters = DashboardFilters::fromRequest($request);

        return view('dashboard.index', [
            'user' => $user,
            'filters' => $filters,
            'summaryCards' => $this->stats->summaryCards($user, $filters),
            'alerts' => $this->stats->alerts($user, $filters),
            'readingProgress' => $this->stats->readingProgress($user, $filters),
            'chartData' => $this->stats->billingCollections($user, $filters),
            'recentPayments' => $this->stats->recentPayments($user, $filters),
            'quickActions' => $this->stats->quickActions($user),
            'navigation' => $this->stats->navigation($user),
            'activeCycle' => $this->stats->activeBillingCycle($filters->billingCycleId),
            'zones' => Zone::orderBy('name')->pluck('name', 'id'),
            'billingCycles' => BillingCycle::orderByDesc('period_start')->limit(12)->pluck('name', 'id'),
            'tariffCategories' => TariffCategory::orderBy('name')->pluck('name', 'id'),
            'refreshedAt' => now(),
        ]);
    }
}
