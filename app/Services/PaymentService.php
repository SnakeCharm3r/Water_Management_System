<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function confirm(Payment $payment, ?array $callback = null, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($payment, $callback, $userId): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'confirmed') {
                return $lockedPayment;
            }

            if ($lockedPayment->status !== 'pending') {
                throw new DomainException('Only pending payments can be confirmed.');
            }

            $lockedPayment->update(['status' => 'confirmed', 'confirmed_at' => now(), 'raw_callback' => $callback]);

            app(AccountLedgerService::class)->post(
                $lockedPayment->account()->firstOrFail(),
                'payment',
                $lockedPayment->receipt_number ?? $lockedPayment->provider_reference ?? $lockedPayment->public_uuid,
                'Confirmed payment',
                'payment-confirmation:'.$lockedPayment->id,
                $lockedPayment->payment_date,
                creditAmount: $lockedPayment->amount,
                links: ['payment_id' => $lockedPayment->id],
                createdBy: $userId,
            );

            return $lockedPayment->refresh();
        });
    }

    public function allocate(Payment $payment, Bill $bill, string $amount): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $bill, $amount): PaymentAllocation {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);

            if ($lockedPayment->status !== 'confirmed') {
                throw new DomainException('Only confirmed payments can be allocated.');
            }

            if ($lockedPayment->water_account_id !== $lockedBill->water_account_id) {
                throw new DomainException('Payments may only be allocated to bills for the same water account.');
            }

            $allocated = (string) PaymentAllocation::query()->where('payment_id', $lockedPayment->id)->sum('allocated_amount');
            $available = bcsub($lockedPayment->amount, $allocated, 2);

            if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $available, 2) > 0 || bccomp($amount, $lockedBill->balance_due, 2) > 0) {
                throw new DomainException('The requested allocation exceeds the available payment or bill balance.');
            }

            $allocation = PaymentAllocation::query()->create(['payment_id' => $lockedPayment->id, 'bill_id' => $lockedBill->id, 'allocated_amount' => $amount, 'allocated_at' => now()]);
            $amountPaid = bcadd($lockedBill->amount_paid, $amount, 2);
            $balanceDue = bcsub($lockedBill->total_amount, $amountPaid, 2);
            $lockedBill->update(['amount_paid' => $amountPaid, 'balance_due' => $balanceDue, 'status' => bccomp($balanceDue, '0', 2) === 0 ? 'paid' : 'partially_paid']);

            return $allocation;
        });
    }
}
