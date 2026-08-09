<?php

namespace App\Enum;

enum ErrorCode: string
{
    // Series
    case SERIES_NOT_FOUND = 'series_not_found';
    case SERIES_NOT_IN_LIST = 'series_not_in_list';
    case SERIES_ALREADY_IN_LIST = 'series_already_in_list';
    case SERIES_ALREADY_COMPLETED = 'series_already_completed';
    case SERIES_NOT_COMPLETED = 'series_not_completed';
    case SERIES_NOT_ADULT = 'series_not_adult';
    case SERIES_NOT_YET_RELEASED = 'series_not_yet_released';
    case SERIES_LOOKUP_FAILED = 'series_lookup_failed';
    case EPISODE_NOT_AIRED = 'episode_not_aired';
    case EPISODE_VERIFICATION_FAILED = 'episode_verification_failed';
    case INVALID_VALUE = 'invalid_value';

    // Import de listas
    case ANILIST_USER_NOT_FOUND = 'anilist_user_not_found';
    case ANILIST_LIST_PRIVATE = 'anilist_list_private';

    // Rate limiting / abuso
    case RATE_LIMITED = 'rate_limited';
    case ACCESS_SUSPENDED_ABUSE = 'access_suspended_abuse';
    case ACCESS_SUSPENDED_12H = 'access_suspended_12h';

    // Usuario / auth
    case INVALID_INPUT = 'invalid_input';
    case VALIDATION_FAILED = 'validation_failed';
    case MISSING_FIELDS = 'missing_fields';
    case WRONG_CURRENT_PASSWORD = 'wrong_current_password';
    case PASSWORD_TOO_SHORT = 'password_too_short';
}