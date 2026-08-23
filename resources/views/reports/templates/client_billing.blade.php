

<div class="report-container">
    <h1>Billing Report for {{ $data['customer']->name }}</h1>
    
    <div class="summary-section">
        <h2>Payment Status</h2>
        <div class="status-grid">
            <div class="status-item">
                <span class="label">Total Invoiced:</span>
                <span class="amount">{{ number_format($data['payment_status']['total_invoiced'], 2) }}</span>
            </div>
            <div class="status-item">
                <span class="label">Total Paid:</span>
                <span class="amount">{{ number_format($data['payment_status']['total_paid'], 2) }}</span>
            </div>
            <div class="status-item">
                <span class="label">Outstanding Balance:</span>
                <span class="amount">{{ number_format($data['payment_status']['total_outstanding'], 2) }}</span>
            </div>
            <div class="status-item">
                <span class="label">Overdue Amount:</span>
                <span class="amount">{{ number_format($data['payment_status']['overdue_amount'], 2) }}</span>
            </div>
        </div>
    </div>

    <div class="billing-history">
        <h2>Billing History</h2>
        <table class="billing-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['billing_history'] as $invoice)
                <tr>
                    <td>{{ $invoice['invoice_number'] }}</td>
                    <td>{{ $invoice['date'] }}</td>
                    <td>{{ $invoice['due_date'] }}</td>
                    <td>{{ number_format($invoice['amount'], 2) }} {{ $invoice['currency'] }}</td>
                    <td>{{ ucfirst($invoice['status']) }}</td>
                    <td>{{ number_format($invoice['balance'], 2) }} {{ $invoice['currency'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="payment-trends">
        <h2>Payment Trends</h2>
        <div class="trend-chart">
            @foreach($data['payment_trends'] as $trend)
            <div class="trend-bar" style="height: {{ ($trend->total_paid / $data['max_monthly_payment']) * 100 }}%">
                <span class="amount">{{ number_format($trend->total_paid, 2) }}</span>
                <span class="month">{{ $trend->month }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>