<?php

namespace App\Enum;

enum SeriesStatus: string
{
    case FINISHED = 'FINISHED';
    case RELEASING = 'RELEASING';
    case NOT_YET_RELEASED = 'NOT_YET_RELEASED';

    public static function values(?array $options = null): array
    {
        return array_column(!empty($options) ? $options : self::cases(), 'value');
    }
}
