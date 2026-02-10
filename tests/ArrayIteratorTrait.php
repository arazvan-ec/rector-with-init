<?php

/**
 * @copyright
 */

namespace App\Tests;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
trait ArrayIteratorTrait
{
    /**
     * @param MockObject $listItemMock
     * @param MockObject $bodyElementMock
     */
    private function configureArrayIteratorMock(MockObject $listItemMock, MockObject $bodyElementMock): void
    {
        $bodyIterator = new \ArrayIterator([$listItemMock]);
        $bodyElementMock
            ->method('rewind')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->rewind();
            });

        $bodyElementMock
            ->method('current')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->current();
            });

        $bodyElementMock
            ->method('key')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->key();
            });

        $bodyElementMock
            ->method('next')
            ->willReturnCallback(static function () use ($bodyIterator) {
                $bodyIterator->next();
            });

        $bodyElementMock
            ->method('valid')
            ->willReturnCallback(static function () use ($bodyIterator) {
                return $bodyIterator->valid();
            });
    }
}
