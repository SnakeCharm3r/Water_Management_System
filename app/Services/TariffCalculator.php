<?php

namespace App\Services;

use App\Models\TariffCategory;
use App\Models\TariffRate;
use Carbon\CarbonInterface;
use DomainException;

class TariffCalculator
{
    public function effectiveRate(TariffCategory $category, string $chargeType, CarbonInterface $on): TariffRate
    {
        return TariffRate::query()
            ->where('tariff_category_id', $category->id)
            ->where('charge_type', $chargeType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $on)
            ->where(static function ($query) use ($on): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on);
            })
            ->orderByDesc('effective_from')
            ->firstOr(function (): never {
                throw new DomainException('No active tariff rate applies to the requested date.');
            });
    }

    public function consumptionCharge(TariffRate $rate, string $consumption): array
    {
        if (bccomp($consumption, '0', 3) < 0) {
            throw new DomainException('Consumption cannot be negative.');
        }

        $blocks = $rate->blocks()->orderBy('sequence')->get();
        $remaining = $consumption;
        $amount = '0.00';
        $details = [];

        if ($blocks->isEmpty()) {
            $amount = bcmul($consumption, (string) ($rate->unit_rate ?? '0'), 2);
        } else {
            foreach ($blocks as $block) {
                if (bccomp($remaining, '0', 3) <= 0) {
                    break;
                }

                $quantity = $remaining;

                if ($block->to_quantity !== null) {
                    $capacity = bcsub((string) $block->to_quantity, (string) $block->from_quantity, 3);

                    if (bccomp($capacity, '0', 3) <= 0) {
                        throw new DomainException('Tariff block limits must be increasing.');
                    }

                    if (bccomp($remaining, $capacity, 3) > 0) {
                        $quantity = $capacity;
                    }
                }
                $lineAmount = bcmul($quantity, (string) $block->rate_per_unit, 2);
                $amount = bcadd($amount, $lineAmount, 2);
                $remaining = bcsub($remaining, $quantity, 3);
                $details[] = ['sequence' => $block->sequence, 'quantity' => $quantity, 'unit_rate' => (string) $block->rate_per_unit, 'amount' => $lineAmount];
            }

            if (bccomp($remaining, '0', 3) > 0) {
                throw new DomainException('Tariff blocks do not cover the full consumption quantity.');
            }
        }

        $amount = bcadd($amount, (string) $rate->fixed_charge, 2);
        $amount = bccomp($amount, (string) $rate->minimum_charge, 2) < 0 ? (string) $rate->minimum_charge : $amount;

        return ['amount' => $amount, 'details' => $details];
    }
}
