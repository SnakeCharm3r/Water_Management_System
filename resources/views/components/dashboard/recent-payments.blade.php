@props(['payments'])

<div class="recent-payments">
    <div class="recent-payments__header">
        <h3>Recent Payments</h3>
        <a href="#payments" class="recent-payments__link">View all payments</a>
    </div>

    @if($payments->isEmpty())
        <x-dashboard.empty-state message="No payments recorded yet." />
    @else
        <div class="table-scroll">
            <table class="data-table" aria-label="Recent payments">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Customer</th>
                        <th>Account</th>
                        <th>Channel</th>
                        <th class="text-right">Amount (TZS)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>
                                <a href="#payments/{{ $payment->id }}">{{ $payment->receipt_number ?? '-' }}</a>
                            </td>
                            <td>{{ $payment->account?->customer?->display_name ?? '-' }}</td>
                            <td>{{ $payment->account?->account_number ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_channel ?? '-')) }}</td>
                            <td class="text-right">{{ number_format($payment->amount) }}</td>
                            <td>
                                <span class="badge badge--{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
                            </td>
                            <td>{{ $payment->payment_date?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
