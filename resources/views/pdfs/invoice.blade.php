

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }
        .invoice-header {
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .invoice-items {
            margin-top: 20px;
        }
        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-items th, .invoice-items td {
            padding: 10px;
            border: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            @if($template->logo_path)
                <img src="{{ storage_path('app/public/' . $template->logo_path) }}" style="max-width: 200px;">
            @endif
            <h1>INVOICE</h1>
            <p>Invoice Number: {{ $invoice->invoice_number }}</p>
            <p>Date: {{ $invoice->issue_date->format('Y-m-d') }}</p>
            <p>Due Date: {{ $invoice->due_date->format('Y-m-d') }}</p>
        </div>

        <div class="company-details">
            <h3>{{ $template->company_name }}</h3>
            <p>{!! nl2br(e($template->company_address)) !!}</p>
            <p>Phone: {{ $template->company_phone }}</p>
            <p>Email: {{ $template->company_email }}</p>
        </div>

        <div class="customer-details">
            <h3>Bill To:</h3>
            <p>{{ $customer->name }}</p>
            <p>{!! nl2br(e($customer->address)) !!}</p>
            <p>Email: {{ $customer->email }}</p>
        </div>

        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->productService->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 2) }} {{ $invoice->currency }}</td>
                        <td>{{ number_format($item->total_price, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>Subtotal:</strong></td>
                        <td>{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                    @if($invoice->discount_amount)
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>Discount:</strong></td>
                        <td>{{ number_format($invoice->discount_amount, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>Total:</strong></td>
                        <td>{{ number_format($invoice->final_total, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($template->footer_text)
        <div class="footer">
            <p>{!! nl2br(e($template->footer_text)) !!}</p>
        </div>
        @endif
    </div>
</body>
</html>