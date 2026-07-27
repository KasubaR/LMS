<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Receipt') }} {{ $payment->payment_number }}</title>
    <x-theme-boot />
    @vite(['resources/css/app.css', 'resources/css/nocturne.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
</head>
<body class="bg-bg text-ink font-sans p-8">
    <div class="no-print mb-4 flex gap-2 items-center">
        <button onclick="window.print()" class="btn btn-primary">{{ __('Print') }}</button>
        <a href="{{ route('payments.receipt.pdf', $payment) }}" class="btn btn-secondary">{{ __('Download PDF') }}</a>
        <a href="{{ route('payments.show', $payment) }}" class="btn btn-ghost">{{ __('Back') }}</a>
        <span class="ms-auto"><x-theme-toggle /></span>
    </div>

    @include('payments.partials.receipt-body', ['payment' => $payment])
</body>
</html>
