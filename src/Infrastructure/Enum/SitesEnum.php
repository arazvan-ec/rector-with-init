<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Enum;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
enum SitesEnum: string
{
    case ELCONFIDENCIAL = '1';
    case VANITATIS = '2';
    case ALIMENTE = '5';

    public static function getHostnameById(string $id): string
    {
        return match ($id) {
            self::ELCONFIDENCIAL->value => 'elconfidencial',
            self::VANITATIS->value => 'vanitatis.elconfidencial',
            self::ALIMENTE->value => 'alimente.elconfidencial',
            default => 'elconfidencial',
        };
    }

    public static function getEncodenameById(string $id): string
    {
        return match ($id) {
            self::ELCONFIDENCIAL->value => 'el-confidencial',
            self::VANITATIS->value => 'vanitatis',
            self::ALIMENTE->value => 'alimente',
            default => 'el-confidencial',
        };
    }
}
