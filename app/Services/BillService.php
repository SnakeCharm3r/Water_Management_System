<?php

namespace App\Services;

use App\Models\Bill;
use DomainException;
use Illuminate\Support\Facades\DB;

class BillService
{
    public function approveAndIssue(Bill $bill, int $userId): Bill
    {
        return DB::transaction(function () use ($bill, $userId): Bill {
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);

            if (! in_array($lockedBill->status, ['calculated', 'pending_approval'], true)) {
                throw new DomainException('Only calculated or pending-approval bills can be issued.');
            }

            $total = bcsub(
                bcadd(
                    bcadd(
                        bcadd($lockedBill->opening_balance, $lockedBill->current_charges, 2),
                        $lockedBill->adjustment_total,
                        2,
                    ),
                    bcadd($lockedBill->penalty_total, $lockedBill->tax_total, 2),
                    2,
                ),
                $lockedBill->credit_total,
                2,
            );

            $lockedBill->update([
                'total_amount' => $total,
                'balance_due' => bcsub($total, $lockedBill->amount_paid, 2),
                'status' => 'issued',
                'approved_by' => $userId,
                'approved_at' => now(),
                'issued_at' => now(),
            ]);

            app(AccountLedgerService::class)->post(
                $lockedBill->account()->firstOrFail(),
                'bill_charge',
                $lockedBill->invoice_number,
                'Issued bill '.$lockedBill->invoice_number,
                'bill-issue:'.$lockedBill->id,
                now(),
                debitAmount: $lockedBill->total_amount,
                links: ['bill_id' => $lockedBill->id],
                createdBy: $userId,
            );

            return $lockedBill->refresh();
        });
    }
}
