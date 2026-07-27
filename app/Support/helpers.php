<?php

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return config('lms.currency_symbol', 'K');
    }
}
