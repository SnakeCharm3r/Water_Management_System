<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class DashboardFilters
{
    public function __construct(
        public readonly ?int $zoneId = null,
        public readonly ?int $billingCycleId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?int $tariffCategoryId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            zoneId: $request->integer('zone') ?: null,
            billingCycleId: $request->integer('billing_cycle') ?: null,
            dateFrom: $request->date('date_from')?->toDateString(),
            dateTo: $request->date('date_to')?->toDateString(),
            tariffCategoryId: $request->integer('tariff_category') ?: null,
        );
    }

    public function toQueryString(): array
    {
        return array_filter([
            'zone' => $this->zoneId,
            'billing_cycle' => $this->billingCycleId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'tariff_category' => $this->tariffCategoryId,
        ]);
    }

    public function cacheKey(string $prefix, array $zoneIds): string
    {
        return $prefix . ':' . md5(json_encode([
            'z' => $zoneIds,
            'bc' => $this->billingCycleId,
            'df' => $this->dateFrom,
            'dt' => $this->dateTo,
            'tc' => $this->tariffCategoryId,
        ]));
    }
}
