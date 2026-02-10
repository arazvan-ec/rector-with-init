<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\ElementContentDataTransformer;
use App\Application\DataTransformer\Apps\Body\ElementContentWithLinksDataTransformer;
use App\Application\DataTransformer\Apps\Body\ElementTypeDataTransformer;
use App\Application\DataTransformer\Apps\Body\ParagraphDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\Paragraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(ParagraphDataTransformer::class)]
#[CoversClass(ElementContentWithLinksDataTransformer::class)]
#[CoversClass(ElementContentDataTransformer::class)]
#[CoversClass(ElementTypeDataTransformer::class)]
class ParagraphDataTransformerTest extends TestCase
{
    private ParagraphDataTransformer $paragraphDataTransformer;

    protected function setUp(): void
    {
        $this->paragraphDataTransformer = new ParagraphDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnParagraphString(): void
    {
        static::assertSame(Paragraph::class, $this->paragraphDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedLink = [
            'type' => 'link',
            'content' => 'links',
            'url' => 'https://www.elconfidencial.com/',
            'target' => '_self',
        ];

        $linkMock = $this->createConfiguredMock(Link::class, $expectedLink);

        $expectedArray = [
            'type' => 'paragraph',
            'content' => 'Contenido #1, con links',
            'links' => [$linkMock],
        ];

        $bodyElementMock = $this->createConfiguredMock(Paragraph::class, $expectedArray);

        $result = $this->paragraphDataTransformer->write($bodyElementMock)->read();

        $expectedArray['links'] = [$expectedLink];

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function readShouldReturnExpectedArrayWithEmptyLinks(): void
    {
        $expectedArray = [
            'type' => 'paragraph',
            'content' => 'Contenido #1, sin links',
            'links' => [],
        ];

        $bodyElementMock = $this->createConfiguredMock(Paragraph::class, $expectedArray);

        $result = $this->paragraphDataTransformer->write($bodyElementMock)->read();

        $expectedArray['links'] = null;

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotParagraph(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.Paragraph::class);

        $this->paragraphDataTransformer->write($bodyElementMock)->read();
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    #[Test]
    public function resolveDataShouldBEPublicAndShouldReturnTheSameArrayAsPassed(): void
    {
        $expectedArray = ['someKey' => 'someValue'];
        $bodyElementMock = $this->createConfiguredMock(Paragraph::class, [
            'type' => 'paragraph',
            'content' => 'Contenido #1, sin links',
            'links' => [],
        ]);

        $method = new \ReflectionMethod($this->paragraphDataTransformer, 'resolveData');
        self::assertTrue($method->isPublic());
        self::assertFalse($method->isProtected());
        self::assertFalse($method->isPrivate());

        $this->paragraphDataTransformer->write($bodyElementMock, $expectedArray);

        $method->invokeArgs($this->paragraphDataTransformer, []);
    }
}
