<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING   = 'PENDING';
    case SUCCESS   = 'SUCCESS';
    case FAILED    = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case ERROR     = 'ERROR';
    case PROCESSING = 'PROCESSING';
}