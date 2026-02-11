<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Resolver\MembershipLinkPromise;
use App\Orchestrator\Chain\Resolver\MembershipLinkResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(MembershipLinkResolver::class)]
class MembershipLinkResolverTest extends TestCase
{
    private QueryMembershipClient&MockObject $queryMembershipClient;

    private UriFactoryInterface&MockObject $uriFactory;

    private MembershipLinkResolver $membershipLinkResolver;

    protected function setUp(): void
    {
        $this->queryMembershipClient = $this->createMock(QueryMembershipClient::class);
        $this->uriFactory = $this->createMock(UriFactoryInterface::class);

        $this->membershipLinkResolver = new MembershipLinkResolver(
            $this->queryMembershipClient,
            $this->uriFactory,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->queryMembershipClient,
            $this->uriFactory,
            $this->membershipLinkResolver,
        );
    }

    #[Test]
    public function createPromiseShouldReturnMembershipLinkPromiseWithLinks(): void
    {
        $editorialId = '12345';
        $siteId = '1';
        $urlMembership1 = 'https://membership.example.com/link1';
        $url1 = 'https://example.com/link1';
        $urlMembership2 = 'https://membership.example.com/link2';
        $url2 = 'https://example.com/link2';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $button1 = $this->createMock(MembershipCardButton::class);
        $button1->expects(static::once())->method('urlMembership')->willReturn($urlMembership1);
        $button1->expects(static::once())->method('url')->willReturn($url1);

        $button2 = $this->createMock(MembershipCardButton::class);
        $button2->expects(static::once())->method('urlMembership')->willReturn($urlMembership2);
        $button2->expects(static::once())->method('url')->willReturn($url2);

        $buttonsMock = $this->createMock(MembershipCardButtons::class);
        $buttonsMock->expects(static::once())->method('buttons')->willReturn([$button1, $button2]);

        $membershipCardMock = $this->createMock(BodyTagMembershipCard::class);
        $membershipCardMock->expects(static::once())->method('buttons')->willReturn($buttonsMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagMembershipCard::class)
            ->willReturn([$membershipCardMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->expects(static::once())->method('body')->willReturn($bodyMock);

        $uriMock = $this->createMock(UriInterface::class);

        $expectedLinks = [$urlMembership1, $url1, $urlMembership2, $url2];
        $callArgumentsCreateUri = [];
        $this->uriFactory->expects(static::exactly(4))
            ->method('createUri')
            ->willReturnCallback(function (string $uri) use (&$callArgumentsCreateUri, $uriMock): UriInterface {
                $callArgumentsCreateUri[] = $uri;

                return $uriMock;
            });

        $promiseMock = $this->createMock(Promise::class);
        $this->queryMembershipClient->expects(static::once())
            ->method('getMembershipUrl')
            ->with(
                $editorialId,
                [$uriMock, $uriMock, $uriMock, $uriMock],
                'el-confidencial',
                true
            )
            ->willReturn($promiseMock);

        $result = $this->membershipLinkResolver->createPromise($editorialMock, $siteId);

        static::assertInstanceOf(MembershipLinkPromise::class, $result);
        static::assertSame($promiseMock, $result->promise);
        static::assertSame($expectedLinks, $result->originalLinks);
        static::assertTrue($result->hasLinks());
        static::assertSame($expectedLinks, $callArgumentsCreateUri);
    }

    #[Test]
    public function createPromiseShouldReturnEmptyPromiseWhenNoMembershipCards(): void
    {
        $editorialId = '12345';
        $siteId = '1';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagMembershipCard::class)
            ->willReturn([]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->expects(static::once())->method('body')->willReturn($bodyMock);

        $this->uriFactory->expects(static::never())->method('createUri');

        $promiseMock = $this->createMock(Promise::class);
        $this->queryMembershipClient->expects(static::once())
            ->method('getMembershipUrl')
            ->with(
                $editorialId,
                [],
                'el-confidencial',
                true
            )
            ->willReturn($promiseMock);

        $result = $this->membershipLinkResolver->createPromise($editorialMock, $siteId);

        static::assertInstanceOf(MembershipLinkPromise::class, $result);
        static::assertSame($promiseMock, $result->promise);
        static::assertSame([], $result->originalLinks);
        static::assertFalse($result->hasLinks());
    }

    #[Test]
    public function resolveShouldReturnCombinedLinksOnSuccess(): void
    {
        $originalLinks = ['https://membership.example.com/link1', 'https://example.com/link1'];
        $resolvedLinks = ['https://resolved.example.com/link1', 'https://resolved.example.com/link2'];

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->expects(static::once())
            ->method('wait')
            ->willReturn($resolvedLinks);

        $membershipLinkPromise = new MembershipLinkPromise($promiseMock, $originalLinks);

        $result = $this->membershipLinkResolver->resolve($membershipLinkPromise);

        $expected = array_combine($originalLinks, $resolvedLinks);
        static::assertSame($expected, $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenNoLinks(): void
    {
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->expects(static::never())->method('wait');

        $membershipLinkPromise = new MembershipLinkPromise($promiseMock, []);

        $result = $this->membershipLinkResolver->resolve($membershipLinkPromise);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenPromiseIsNull(): void
    {
        $membershipLinkPromise = new MembershipLinkPromise(null, ['https://example.com/link1']);

        $result = $this->membershipLinkResolver->resolve($membershipLinkPromise);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayOnException(): void
    {
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->expects(static::once())
            ->method('wait')
            ->willThrowException(new \RuntimeException('Promise resolution failed'));

        $membershipLinkPromise = new MembershipLinkPromise($promiseMock, ['https://example.com/link1']);

        $result = $this->membershipLinkResolver->resolve($membershipLinkPromise);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenResultIsEmpty(): void
    {
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->expects(static::once())
            ->method('wait')
            ->willReturn([]);

        $membershipLinkPromise = new MembershipLinkPromise($promiseMock, ['https://example.com/link1']);

        $result = $this->membershipLinkResolver->resolve($membershipLinkPromise);

        static::assertSame([], $result);
    }
}
