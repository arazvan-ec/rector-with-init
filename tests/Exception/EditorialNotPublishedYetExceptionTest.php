<?php

/**
 * @copyright
 */

namespace App\Tests\Exception;

use App\Exception\EditorialNotPublishedYetException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(EditorialNotPublishedYetException::class)]
class EditorialNotPublishedYetExceptionTest extends TestCase
{
    private const MESSAGE = 'Editorial not published';
    private const CODE = 404;

    #[Test]
    public function exceptionMessageShouldBeExpectedOne(): void
    {
        $exception = new EditorialNotPublishedYetException();
        $this->assertEquals(self::MESSAGE, $exception->getMessage());
    }

    #[Test]
    public function exceptionCodeShouldBeExpectedOne(): void
    {
        $exception = new EditorialNotPublishedYetException();
        $this->assertEquals(self::CODE, $exception->getCode());
    }
}
