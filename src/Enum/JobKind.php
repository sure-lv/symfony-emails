<?php

namespace SureLv\Emails\Enum;

enum JobKind: string
{
    case TRANSACTIONAL = 'transactional';
    case LIST = 'list';

    /**
     * Get all job kind values
     * 
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn(self $value) => $value->value, self::cases());
    }

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}