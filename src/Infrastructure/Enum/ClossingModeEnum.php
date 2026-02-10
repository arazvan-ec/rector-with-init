<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Enum;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
enum ClossingModeEnum: string
{
    case REGISTRY = '1';
    case PAYMENT = '2';
    case APPPAYMENT = '3';

    public static function getClosingModeById(string $id): string
    {
        return match ($id) {
            self::REGISTRY->value => 'registry',
            self::PAYMENT->value => 'payment',
            self::APPPAYMENT->value => 'apppayment',
            default => '',
        };
    }
}
