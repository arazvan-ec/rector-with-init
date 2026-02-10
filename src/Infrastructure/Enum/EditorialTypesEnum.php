<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Enum;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
enum EditorialTypesEnum: string
{
    case NEWS = 'news';
    case BLOG = 'blog';
    case LIVESPORT = 'livesport';
    case LIVE = 'live';
    case CHRONICLE = 'chronicle';
    case LOVERS = 'lovers';

    /**
     * @return array<string, mixed>
     */
    public static function getNameById(string $id): array
    {
        return match ($id) {
            self::NEWS->value => ['id' => '1', 'name' => self::NEWS->value],
            self::BLOG->value => ['id' => '3', 'name' => self::BLOG->value],
            self::LIVESPORT->value => ['id' => '12', 'name' => self::LIVESPORT->value],
            self::LIVE->value => ['id' => '13', 'name' => self::LIVE->value],
            self::CHRONICLE->value => ['id' => '14', 'name' => self::CHRONICLE->value],
            self::LOVERS->value => ['id' => '15', 'name' => self::LOVERS->value],
            default => ['id' => '1', 'name' => self::NEWS->value],
        };
    }
}
