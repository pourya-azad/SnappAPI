<?php

namespace App\Enums;

enum PaymentType : string
{
    case ONLINE = 'online';
    case CASH = 'cash';
}
