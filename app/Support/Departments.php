<?php

namespace App\Support;

class Departments
{
    public const PAYMENT = 2;

    public const DISPUTE = 3;

    public const WRITER = 6;

    public const CUSTOMER = 7;

    public const SUPPORT = 8;

    public const WRITERS = 9;

    public const NAMES = [
        self::PAYMENT => 'Payment',
        self::DISPUTE => 'Dispute',
        self::WRITER => 'Writer',
        self::CUSTOMER => 'You',
        self::SUPPORT => 'Support',
        self::WRITERS => 'Writers',
    ];
}
