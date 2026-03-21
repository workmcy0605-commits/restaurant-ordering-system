<?php

namespace App\Enums;

enum PermissionType: string
{
    case READ = 'Read';
    case CREATE = 'Create';
    case UPDATE = 'Update';
    case DELETE = 'Delete';

    public static function all(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
