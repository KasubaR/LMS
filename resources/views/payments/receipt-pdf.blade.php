<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->payment_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #ddd; }
        .muted { color: #666; font-size: 11px; }
        .row { margin: 4px 0; }
    </style>
</head>
<body>
    @include('payments.partials.receipt-body', ['payment' => $payment, 'pdf' => true])
</body>
</html>
