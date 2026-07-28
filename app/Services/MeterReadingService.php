<?php

namespace App\Services;

use App\Models\BillingCycle;
use App\Models\MeterInstallation;
use App\Models\MeterReading;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class MeterReadingService
{
    public function submit(MeterInstallation $installation, BillingCycle $cycle, CarbonInterface $readingDate, string $currentReading, string $readingType, ?int $readerId = null): MeterReading
    {
        return DB::transaction(function () use ($installation, $cycle, $readingDate, $currentReading, $readingType, $readerId): MeterReading {
            $lockedInstallation = MeterInstallation::query()->lockForUpdate()->findOrFail($installation->id);

            if (! $lockedInstallation->is_active && $lockedInstallation->removal_date?->lt($readingDate)) {
                throw new DomainException('A reading cannot be submitted after meter removal.');
            }

            $existing = MeterReading::query()->where('meter_installation_id', $lockedInstallation->id)->where('billing_cycle_id', $cycle->id)->lockForUpdate()->first();

            if ($existing !== null) {
                throw new DomainException('A reading already exists for this meter installation and billing cycle.');
            }

            $previous = MeterReading::query()->where('meter_installation_id', $lockedInstallation->id)->whereIn('reading_status', ['submitted', 'verified', 'billed'])->orderByDesc('reading_date')->orderByDesc('id')->lockForUpdate()->first();
            $previousValue = $previous?->current_reading ?? $lockedInstallation->initial_reading;

            if (bccomp($currentReading, $previousValue, 3) < 0) {
                throw new DomainException('Current reading cannot be below the previous reading.');
            }

            $consumption = bcmul(bcsub($currentReading, $previousValue, 3), $lockedInstallation->meter_multiplier, 3);

            return MeterReading::query()->create([
                'meter_installation_id' => $lockedInstallation->id,
                'billing_cycle_id' => $cycle->id,
                'previous_reading_id' => $previous?->id,
                'reading_date' => $readingDate,
                'previous_reading' => $previousValue,
                'current_reading' => $currentReading,
                'consumption' => $consumption,
                'reading_type' => $readingType,
                'reading_status' => 'submitted',
                'reader_id' => $readerId,
                'submitted_at' => now(),
            ]);
        });
    }

    public function verify(MeterReading $reading, int $verifierId): MeterReading
    {
        return DB::transaction(function () use ($reading, $verifierId): MeterReading {
            $locked = MeterReading::query()->lockForUpdate()->findOrFail($reading->id);

            if ($locked->reading_status !== 'submitted') {
                throw new DomainException('Only submitted readings can be verified.');
            }

            $locked->update(['reading_status' => 'verified', 'verified_by' => $verifierId, 'verified_at' => now()]);

            return $locked->refresh();
        });
    }
}
