<?php

namespace App\Services;

use App\Models\AccountLedgerEntry;
use App\Models\WaterAccount;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class AccountLedgerService
{
    public function post(WaterAccount $account, string $entryType, string $referenceNumber, string $description, string $idempotencyKey, CarbonInterface $entryDate, string $debitAmount = '0.00', string $creditAmount = '0.00', array $links = [], ?int $createdBy = null): AccountLedgerEntry
    {
        if ((bccomp($debitAmount, '0', 2) > 0) === (bccomp($creditAmount, '0', 2) > 0)) {
            throw new DomainException('A ledger entry must contain exactly one positive debit or credit amount.');
        }

        return DB::transaction(function () use ($account, $entryType, $referenceNumber, $description, $idempotencyKey, $entryDate, $debitAmount, $creditAmount, $links, $createdBy): AccountLedgerEntry {
            $existing = AccountLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if ($existing !== null) {
                return $existing;
            }

            $lockedAccount = WaterAccount::query()->lockForUpdate()->findOrFail($account->id);
            $previous = AccountLedgerEntry::query()->where('water_account_id', $lockedAccount->id)->orderByDesc('id')->lockForUpdate()->first();
            $balance = bcadd((string) ($previous?->running_balance ?? '0.00'), bcsub($debitAmount, $creditAmount, 2), 2);

            $entry = AccountLedgerEntry::query()->create([
                'water_account_id' => $lockedAccount->id,
                'bill_id' => $links['bill_id'] ?? null,
                'payment_id' => $links['payment_id'] ?? null,
                'adjustment_id' => $links['adjustment_id'] ?? null,
                'reversal_of_id' => $links['reversal_of_id'] ?? null,
                'entry_date' => $entryDate,
                'entry_type' => $entryType,
                'reference_number' => $referenceNumber,
                'description' => $description,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'running_balance' => $balance,
                'currency' => $lockedAccount->currency ?? 'TZS',
                'idempotency_key' => $idempotencyKey,
                'created_by' => $createdBy,
            ]);

            $lockedAccount->update(['current_balance' => $balance]);

            return $entry;
        });
    }
}
