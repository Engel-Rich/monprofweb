<?php

namespace App\Enums;

enum TransactionSense: string
{
    case IN  = 'IN';
    case OUT = 'OUT';
}