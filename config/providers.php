<?php

declare(strict_types=1);

return [
    'seerbit' => [
        'priority'   => 10,
        'currencies' => ['NGN', 'GHS', 'KES', 'ZAR', 'XOF'],
        'countries'  => ['NG', 'GH', 'KE', 'ZA', 'SN', 'CI'],
    ],
    'stripe' => [
        'priority'   => 20,
        'currencies' => ['USD', 'EUR', 'GBP'],
        'countries'  => ['US', 'GB', 'DE', 'FR'],
    ],
    'flutterwave' => [
        'priority'   => 30,
        'currencies' => ['NGN', 'GHS', 'KES', 'UGX', 'ZAR'],
        'countries'  => ['NG', 'GH', 'KE', 'UG', 'ZA'],
    ],
];
