<?php

namespace App\Services;

use App\Enums\MeterInstallationStatus;
use App\Enums\MeterStatus;
use App\Models\Meter;
use App\Models\MeterInstallation;
use App\Models\WaterAccount;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class MeterInstallationService
{
    public function install(WaterAccount $account, Meter $meter, CarbonInterface $installationDate, string $initialReading = '0', ?int $userId = null, ?string $location = null, ?string $sealNumber = null, string $multiplier = '1'): MeterInstallation
    {
        return DB::transaction(function () use ($account, $meter, $installationDate, $initialReading, $userId, $location, $sealNumber, $multiplier): MeterInstallation {
            $lockedAccount = WaterAccount::query()->lockForUpdate()->findOrFail($account->id);
            $lockedMeter = Meter::query()->lockForUpdate()->findOrFail($meter->id);

            if ($lockedMeter->status !== MeterStatus::Available && $lockedMeter->status !== MeterStatus::Removed) {
                throw new DomainException('Only available or removed meters can be installed.');
            }

            if (MeterInstallation::query()->where('water_account_id', $lockedAccount->id)->where('is_active', true)->lockForUpdate()->exists()) {
                throw new DomainException('The water account already has an active meter installation.');
            }

            if (MeterInstallation::query()->where('meter_id', $lockedMeter->id)->where('is_active', true)->lockForUpdate()->exists()) {
                throw new DomainException('The meter is already actively installed.');
            }

            $installation = MeterInstallation::query()->create([
                'water_account_id' => $lockedAccount->id,
                'meter_id' => $lockedMeter->id,
                'installed_by' => $userId,
                'installation_date' => $installationDate,
                'initial_reading' => $initialReading,
                'installation_location' => $location,
                'seal_number' => $sealNumber,
                'meter_multiplier' => $multiplier,
                'status' => MeterInstallationStatus::Active,
                'is_active' => true,
            ]);

            $lockedMeter->update(['status' => MeterStatus::Installed]);

            return $installation;
        });
    }

    public function remove(MeterInstallation $installation, CarbonInterface $removalDate, string $finalReading, ?int $userId = null, MeterInstallationStatus $status = MeterInstallationStatus::Removed): MeterInstallation
    {
        return DB::transaction(function () use ($installation, $removalDate, $finalReading, $userId, $status): MeterInstallation {
            $lockedInstallation = MeterInstallation::query()->lockForUpdate()->findOrFail($installation->id);

            if (! $lockedInstallation->is_active) {
                throw new DomainException('Only an active installation can be removed.');
            }

            if (bccomp($finalReading, $lockedInstallation->initial_reading, 3) < 0) {
                throw new DomainException('Final reading cannot be below the initial reading.');
            }

            $lockedInstallation->update([
                'removal_date' => $removalDate,
                'final_reading' => $finalReading,
                'removed_by' => $userId,
                'status' => $status,
                'is_active' => false,
            ]);

            Meter::query()->lockForUpdate()->findOrFail($lockedInstallation->meter_id)->update(['status' => MeterStatus::Removed]);

            return $lockedInstallation->refresh();
        });
    }

    public function replace(MeterInstallation $previousInstallation, Meter $replacementMeter, CarbonInterface $replacementDate, string $finalReading, string $initialReading = '0', ?int $userId = null): MeterInstallation
    {
        return DB::transaction(function () use ($previousInstallation, $replacementMeter, $replacementDate, $finalReading, $initialReading, $userId): MeterInstallation {
            $previous = MeterInstallation::query()->lockForUpdate()->findOrFail($previousInstallation->id);
            $this->remove($previous, $replacementDate, $finalReading, $userId, MeterInstallationStatus::Replaced);

            return $this->install($previous->waterAccount()->firstOrFail(), $replacementMeter, $replacementDate, $initialReading, $userId);
        });
    }
}
