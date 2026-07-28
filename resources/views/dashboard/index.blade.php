<x-layouts.portal title="Operations Dashboard — DAWASA">
    <div class="portal-shell" data-sidebar>
        {{-- ── Sidebar navigation ─────────────────────────────── --}}
        <x-dashboard.sidebar :navigation="$navigation" :user="$user" />

        <main class="portal-main">
            {{-- ── Top bar ────────────────────────────────────────── --}}
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Operations Dashboard</p>
                    <h1>Welcome, {{ $user->fname }}</h1>
                </div>
                <div class="topbar__context">
                    <span class="topbar__role">{{ $user->roles->first()?->name ?? 'Staff' }}</span>
                    <span class="topbar__zone">{{ $user->zone?->name ?? 'Head Office' }}</span>
                    <time class="topbar__date" datetime="{{ now()->toDateString() }}">{{ now()->format('l, d M Y') }}</time>
                    @if($activeCycle)
                        <span class="topbar__cycle badge badge--info">{{ $activeCycle->name ?? 'Cycle #' . $activeCycle->id }} &middot; {{ ucfirst($activeCycle->status) }}</span>
                    @endif
                </div>
            </header>

            {{-- ── Filter bar ─────────────────────────────────────── --}}
            <x-dashboard.filter-bar
                :filters="$filters"
                :zones="$zones"
                :billing-cycles="$billingCycles"
                :tariff-categories="$tariffCategories"
                :refreshed-at="$refreshedAt"
            />

            {{-- ── Summary metric cards ───────────────────────────── --}}
            <section class="metric-grid" aria-label="Key metrics">
                @foreach ($summaryCards as $card)
                    <x-dashboard.metric-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :change="$card['change']"
                        :description="$card['description']"
                        :icon="$card['icon']"
                        :link="$card['link']"
                        :is-currency="$card['is_currency'] ?? false"
                    />
                @endforeach
            </section>

            {{-- ── Quick actions ───────────────────────────────────── --}}
            @if(count($quickActions) > 0)
                <section class="quick-actions-grid" aria-label="Quick actions">
                    @foreach ($quickActions as $action)
                        <x-dashboard.quick-action
                            :label="$action['label']"
                            :icon="$action['icon']"
                            :route="$action['route']"
                        />
                    @endforeach
                </section>
            @endif

            {{-- ── Attention-required alerts ──────────────────────── --}}
            <section class="alerts-section" aria-label="Attention required">
                <h2 class="section-title">Attention Required</h2>
                <div class="alerts-list">
                    @foreach ($alerts as $alert)
                        <x-dashboard.alert-item
                            :label="$alert['label']"
                            :count="$alert['count']"
                            :severity="$alert['severity']"
                            :description="$alert['description']"
                            :link="$alert['link']"
                        />
                    @endforeach
                </div>
            </section>

            {{-- ── Two-column panels ──────────────────────────────── --}}
            <div class="dashboard-panels">
                {{-- Reading progress --}}
                <section aria-label="Meter reading progress">
                    <x-dashboard.reading-progress :data="$readingProgress" />
                </section>

                {{-- Billing vs collections chart --}}
                <section aria-label="Billing versus collections chart">
                    <x-dashboard.chart-panel :data="$chartData" />
                </section>
            </div>

            {{-- ── Recent payments table ──────────────────────────── --}}
            <section aria-label="Recent payments">
                <x-dashboard.recent-payments :payments="$recentPayments" />
            </section>
        </main>
    </div>
</x-layouts.portal>
