<?php

namespace App\Enums;

enum BackendLocaleType: string
{
    case ATTRIBUTE = 'Attribute';
    case VALIDATION = 'Validation';
    case CUSTOM_VALIDATION = 'CustomValidation';
    case API_RESPONSE_MESSAGE = 'ApiResponseMessage';
    case NOTIFICATION_TITLE = 'NotificationTitle';
    case NOTIFICATION_CONTENT = 'NotificationContent';
    case VALUE = 'Value';
}
