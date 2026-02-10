<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagMembershipCardDataTransformer;
use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use App\Tests\Application\DataTransformer\Apps\Body\DataProvider\BodyTagMembershipCardDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
class BodyTagMembershipCardDataTransformerTest extends TestCase
{
    /** @var BodyElementDataTransformerHandler|MockObject */
    private BodyElementDataTransformerHandler $handler;
    private BodyTagMembershipCardDataTransformer $dataTransformer;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(BodyElementDataTransformerHandler::class);
        $this->dataTransformer = new BodyTagMembershipCardDataTransformer($this->handler);
    }

    #[Test]
    public function canTransformShouldReturnBodyTagHtmlString(): void
    {
        static::assertSame(BodyTagMembershipCard::class, $this->dataTransformer->canTransform());
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotBodyTagMembershipCard(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.BodyTagMembershipCard::class);

        $this->dataTransformer->write($bodyElementMock)->read();
    }

    /**
     * @param array{
     *      btns: array<array{
     *          url: string,
     *          urlMembership: string,
     *          cta: string
     *      }>,
     *      title: string,
     *      titleBanner: string,
     *      classBanner: string
     *  } $bodyTag
     * @param array<array<string, string>> $combinedLinks
     * @param array<array<string, string>> $expected
     */
    #[DataProviderExternal(BodyTagMembershipCardDataProvider::class, 'getData')]
    #[Test]
    public function readShouldReturnExpectedArray(array $bodyTag, array $combinedLinks, array $expected): void
    {
        $arrayBtnMock = [];
        foreach ($bodyTag['btns'] as $btn) {
            $buttonMock = $this->createMock(MembershipCardButton::class);

            $buttonMock->expects(static::once())
                ->method('url')
                ->willReturn($btn['url']);
            $buttonMock->expects(static::once())
                ->method('urlMembership')
                ->willReturn($btn['urlMembership']);
            $buttonMock->expects(static::once())
                ->method('cta')
                ->willReturn($btn['cta']);
            $arrayBtnMock[] = $buttonMock;
        }
        $buttonCollectionMock = $this->createMock(MembershipCardButtons::class);
        $buttonCollectionMock->expects(static::once())
            ->method('buttons')
            ->willReturn($arrayBtnMock);

        $bodyElementMock = $this->createMock(BodyTagMembershipCard::class);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytagmembershipcard');
        $bodyElementMock->expects(static::once())
            ->method('title')
            ->willReturn($bodyTag['title']);
        $bodyElementMock->expects(static::once())
            ->method('buttons')
            ->willReturn($buttonCollectionMock);
        $bodyElementMock->expects(static::once())
            ->method('titleBanner')
            ->willReturn($bodyTag['titleBanner']);
        $bodyElementMock->expects(static::once())
            ->method('classBanner')
            ->willReturn($bodyTag['classBanner']);

        $pictureMock = $this->createMock(BodyTagPictureMembership::class);
        $bodyElementMock->expects(static::once())
            ->method('bodyTagPictureMembership')
            ->willReturn($pictureMock);
        $this->handler->expects(static::once())
            ->method('execute')
            ->with($pictureMock, $combinedLinks);

        $result = $this->dataTransformer->write($bodyElementMock, $combinedLinks)->read();

        static::assertSame($expected, $result);
    }
}
