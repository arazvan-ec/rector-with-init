<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain\Resolver;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Orchestrator\Chain\Resolver\SignatureResolver;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Domain\Model\SignatureId;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SignatureResolver::class)]
class SignatureResolverTest extends TestCase
{
    private QueryJournalistClient&MockObject $queryJournalistClient;
    private JournalistFactory&MockObject $journalistFactory;
    private JournalistsDataTransformer&MockObject $journalistsDataTransformer;
    private LoggerInterface&MockObject $logger;
    private SignatureResolver $signatureResolver;

    protected function setUp(): void
    {
        $this->queryJournalistClient = $this->createMock(QueryJournalistClient::class);
        $this->journalistFactory = $this->createMock(JournalistFactory::class);
        $this->journalistsDataTransformer = $this->createMock(JournalistsDataTransformer::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->signatureResolver = new SignatureResolver(
            $this->queryJournalistClient,
            $this->journalistFactory,
            $this->journalistsDataTransformer,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->signatureResolver,
            $this->queryJournalistClient,
            $this->journalistFactory,
            $this->journalistsDataTransformer,
            $this->logger,
        );
    }

    #[Test]
    public function resolveSignaturesShouldReturnEmptyArrayWhenNoSignatures(): void
    {
        $signatures = $this->createMock(Signatures::class);
        $signatures->expects($this->once())
            ->method('getArrayCopy')
            ->willReturn([]);

        $section = $this->createMock(Section::class);

        $result = $this->signatureResolver->resolveSignatures($signatures, $section);

        $this->assertSame([], $result);
    }

    #[Test]
    public function resolveSignaturesShouldReturnResolvedSignatures(): void
    {
        $aliasId1 = 'alias-1';
        $aliasId2 = 'alias-2';

        $signatureId1 = $this->createMock(SignatureId::class);
        $signatureId1->method('id')->willReturn($aliasId1);
        $signature1 = $this->createMock(Signature::class);
        $signature1->method('id')->willReturn($signatureId1);

        $signatureId2 = $this->createMock(SignatureId::class);
        $signatureId2->method('id')->willReturn($aliasId2);
        $signature2 = $this->createMock(Signature::class);
        $signature2->method('id')->willReturn($signatureId2);

        $signatures = $this->createMock(Signatures::class);
        $signatures->expects($this->once())
            ->method('getArrayCopy')
            ->willReturn([$signature1, $signature2]);

        $section = $this->createMock(Section::class);

        $aliasIdModel1 = $this->createMock(\stdClass::class);
        $aliasIdModel2 = $this->createMock(\stdClass::class);

        $journalist1 = $this->createMock(Journalist::class);
        $journalist2 = $this->createMock(Journalist::class);

        $expectedData1 = ['journalistId' => '100', 'aliasId' => $aliasId1, 'name' => 'Author One'];
        $expectedData2 = ['journalistId' => '200', 'aliasId' => $aliasId2, 'name' => 'Author Two'];

        $callIndex = 0;
        $this->journalistFactory->expects($this->exactly(2))
            ->method('buildAliasId')
            ->willReturnCallback(function (string $id) use ($aliasId1, $aliasId2, $aliasIdModel1, $aliasIdModel2, &$callIndex) {
                $callIndex++;
                if ($id === $aliasId1) {
                    return $aliasIdModel1;
                }

                return $aliasIdModel2;
            });

        $this->queryJournalistClient->expects($this->exactly(2))
            ->method('findJournalistByAliasId')
            ->willReturnCallback(function ($aliasIdModel) use ($aliasIdModel1, $aliasIdModel2, $journalist1, $journalist2) {
                if ($aliasIdModel === $aliasIdModel1) {
                    return $journalist1;
                }

                return $journalist2;
            });

        $writeCallIndex = 0;
        $this->journalistsDataTransformer->expects($this->exactly(2))
            ->method('write')
            ->willReturnSelf();

        $this->journalistsDataTransformer->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls($expectedData1, $expectedData2);

        $result = $this->signatureResolver->resolveSignatures($signatures, $section);

        $this->assertCount(2, $result);
        $this->assertSame($expectedData1, $result[0]);
        $this->assertSame($expectedData2, $result[1]);
    }

    #[Test]
    public function resolveSignaturesShouldSkipEmptyResolvedSignatures(): void
    {
        $aliasId1 = 'alias-1';
        $aliasId2 = 'alias-2';

        $signatureId1 = $this->createMock(SignatureId::class);
        $signatureId1->method('id')->willReturn($aliasId1);
        $signature1 = $this->createMock(Signature::class);
        $signature1->method('id')->willReturn($signatureId1);

        $signatureId2 = $this->createMock(SignatureId::class);
        $signatureId2->method('id')->willReturn($aliasId2);
        $signature2 = $this->createMock(Signature::class);
        $signature2->method('id')->willReturn($signatureId2);

        $signatures = $this->createMock(Signatures::class);
        $signatures->expects($this->once())
            ->method('getArrayCopy')
            ->willReturn([$signature1, $signature2]);

        $section = $this->createMock(Section::class);

        $aliasIdModel1 = $this->createMock(\stdClass::class);
        $aliasIdModel2 = $this->createMock(\stdClass::class);

        $journalist1 = $this->createMock(Journalist::class);

        $expectedData1 = ['journalistId' => '100', 'aliasId' => $aliasId1, 'name' => 'Author One'];

        $this->journalistFactory->expects($this->exactly(2))
            ->method('buildAliasId')
            ->willReturnCallback(function (string $id) use ($aliasId1, $aliasIdModel1, $aliasIdModel2) {
                if ($id === $aliasId1) {
                    return $aliasIdModel1;
                }

                return $aliasIdModel2;
            });

        $this->queryJournalistClient->expects($this->exactly(2))
            ->method('findJournalistByAliasId')
            ->willReturnCallback(function ($aliasIdModel) use ($aliasIdModel1, $journalist1) {
                if ($aliasIdModel === $aliasIdModel1) {
                    return $journalist1;
                }

                throw new \RuntimeException('Journalist not found');
            });

        $this->journalistsDataTransformer->expects($this->once())
            ->method('write')
            ->willReturnSelf();

        $this->journalistsDataTransformer->expects($this->once())
            ->method('read')
            ->willReturn($expectedData1);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Journalist not found');

        $result = $this->signatureResolver->resolveSignatures($signatures, $section);

        $this->assertCount(1, $result);
        $this->assertSame($expectedData1, $result[0]);
    }

    #[Test]
    public function resolveSignaturesShouldPassHasTwitterFlag(): void
    {
        $aliasId = 'alias-twitter';

        $signatureId = $this->createMock(SignatureId::class);
        $signatureId->method('id')->willReturn($aliasId);
        $signature = $this->createMock(Signature::class);
        $signature->method('id')->willReturn($signatureId);

        $signatures = $this->createMock(Signatures::class);
        $signatures->expects($this->once())
            ->method('getArrayCopy')
            ->willReturn([$signature]);

        $section = $this->createMock(Section::class);
        $aliasIdModel = $this->createMock(\stdClass::class);
        $journalist = $this->createMock(Journalist::class);

        $expectedData = ['journalistId' => '300', 'aliasId' => $aliasId, 'twitter' => '@handle'];

        $this->journalistFactory->expects($this->once())
            ->method('buildAliasId')
            ->with($aliasId)
            ->willReturn($aliasIdModel);

        $this->queryJournalistClient->expects($this->once())
            ->method('findJournalistByAliasId')
            ->with($aliasIdModel)
            ->willReturn($journalist);

        $this->journalistsDataTransformer->expects($this->once())
            ->method('write')
            ->with($aliasId, $journalist, $section, true)
            ->willReturnSelf();

        $this->journalistsDataTransformer->expects($this->once())
            ->method('read')
            ->willReturn($expectedData);

        $result = $this->signatureResolver->resolveSignatures($signatures, $section, true);

        $this->assertCount(1, $result);
        $this->assertSame($expectedData, $result[0]);
    }

    #[Test]
    public function resolveAliasShouldReturnTransformedJournalist(): void
    {
        $aliasId = 'alias-happy';
        $section = $this->createMock(Section::class);
        $aliasIdModel = $this->createMock(\stdClass::class);
        $journalist = $this->createMock(Journalist::class);

        $expectedData = ['journalistId' => '400', 'aliasId' => $aliasId, 'name' => 'Happy Author'];

        $this->journalistFactory->expects($this->once())
            ->method('buildAliasId')
            ->with($aliasId)
            ->willReturn($aliasIdModel);

        $this->queryJournalistClient->expects($this->once())
            ->method('findJournalistByAliasId')
            ->with($aliasIdModel)
            ->willReturn($journalist);

        $this->journalistsDataTransformer->expects($this->once())
            ->method('write')
            ->with($aliasId, $journalist, $section, false)
            ->willReturnSelf();

        $this->journalistsDataTransformer->expects($this->once())
            ->method('read')
            ->willReturn($expectedData);

        $result = $this->signatureResolver->resolveAlias($aliasId, $section);

        $this->assertSame($expectedData, $result);
    }

    #[Test]
    public function resolveAliasShouldReturnEmptyArrayOnException(): void
    {
        $aliasId = 'alias-error';
        $section = $this->createMock(Section::class);
        $aliasIdModel = $this->createMock(\stdClass::class);

        $this->journalistFactory->expects($this->once())
            ->method('buildAliasId')
            ->with($aliasId)
            ->willReturn($aliasIdModel);

        $this->queryJournalistClient->expects($this->once())
            ->method('findJournalistByAliasId')
            ->with($aliasIdModel)
            ->willThrowException(new \RuntimeException('Service unavailable'));

        $this->journalistsDataTransformer->expects($this->never())
            ->method('write');

        $result = $this->signatureResolver->resolveAlias($aliasId, $section);

        $this->assertSame([], $result);
    }

    #[Test]
    public function resolveAliasShouldLogErrorOnException(): void
    {
        $aliasId = 'alias-log';
        $section = $this->createMock(Section::class);
        $aliasIdModel = $this->createMock(\stdClass::class);
        $errorMessage = 'Connection timed out';

        $this->journalistFactory->expects($this->once())
            ->method('buildAliasId')
            ->with($aliasId)
            ->willReturn($aliasIdModel);

        $this->queryJournalistClient->expects($this->once())
            ->method('findJournalistByAliasId')
            ->with($aliasIdModel)
            ->willThrowException(new \RuntimeException($errorMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->signatureResolver->resolveAlias($aliasId, $section);
    }
}
