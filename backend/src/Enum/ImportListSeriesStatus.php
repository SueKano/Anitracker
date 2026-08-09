<?php

namespace App\Enum;

enum ImportListSeriesStatus: string
{
    case CURRENT = 'CURRENT';
    case COMPLETED = 'COMPLETED';
    case REPEATING = 'REPEATING';

}
