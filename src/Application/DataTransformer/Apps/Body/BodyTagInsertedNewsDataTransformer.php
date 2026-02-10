<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Trait\MultimediaTrait;
use App\Infrastructure\Trait\UrlGeneratorTrait;
use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Ec\Encode\Encode;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
class BodyTagInsertedNewsDataTransformer extends ElementTypeDataTransformer
{
    use UrlGeneratorTrait;
    use MultimediaTrait;

    public function __construct(
        Thumbor $thumbor,
        string $extension,
    ) {
        $this->setExtension($extension);
        $this->setThumbor($thumbor);
    }

    public function canTransform(): string
    {
        return BodyTagInsertedNews::class;
    }

    public function read(): array
    {
        $message = \sprintf('BodyElement should be instance of %s', BodyTagInsertedNews::class);
        /** @var BodyTagInsertedNews $bodyElement */
        $bodyElement = $this->bodyElement;
        Assertion::isInstanceOf($bodyElement, BodyTagInsertedNews::class, $message);

        $elementArray = parent::read();

        /** @var array<string, array<string, mixed>> $resolveData */
        $resolveData = $this->resolveData();

        $editorialId = $bodyElement->editorialId()->id();
        if (!isset($resolveData['insertedNews'][$editorialId])) {
            throw new BodyDataTransformerNotFoundException('Inserted news: editorial not found for id: '.$editorialId);
        }

        /** @var array<string, mixed> $currentInsertedNews */
        $currentInsertedNews = $resolveData['insertedNews'][$editorialId];
        $signatures = $currentInsertedNews['signatures'];
        /** @var Editorial $editorial */
        $editorial = $currentInsertedNews['editorial'];
        /** @var Section $sectionInserted */
        $sectionInserted = $currentInsertedNews['section'];

        $elementArray['editorialId'] = $editorial->id()->id();
        $elementArray['title'] = $editorial->editorialTitles()->title();
        $elementArray['signatures'] = $signatures;
        $elementArray['editorial'] = $this->editorialUrl($editorial, $sectionInserted);

        $shots = [];

        if ($this->getMultimediaOpening($editorialId)) {
            $shots = $this->getMultimediaOpening($editorialId);
        } else {
            $shots = $this->getMultimedia($editorialId);
        }

        $elementArray['shots'] = $shots;
        $elementArray['photo'] = empty($shots) ? '' : reset($shots);

        return $elementArray;
    }

    private function editorialUrl(Editorial $editorial, Section $section): string
    {
        $editorialPath = \sprintf(
            '%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id()
        );

        return $this->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $editorialPath
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMultimedia(string $editorialId): array
    {
        $shots = [];

        /** @var array<string, array<string, array<string, string>>> $resolveData */
        $resolveData = $this->resolveData();
        /** @var ?Multimedia $multimedia */
        $multimedia = $resolveData['multimedia'][$resolveData['insertedNews'][$editorialId]['multimediaId']] ?? null;
        if (null === $multimedia) {
            return $shots;
        }

        return $this->getShotsLandscape($multimedia);
    }

    /**
     * @return array<string, string>|array{}
     */
    private function getMultimediaOpening(string $editorialId): array
    {
        /** @var array<string, array<string, array<string, string>>> $resolveData */
        $resolveData = $this->resolveData();
        /** @var ?array{
         *     opening: Multimedia\MultimediaPhoto,
         *     resource: Photo
         * } $multimedia
         */
        $multimedia = $resolveData['multimediaOpening'][$resolveData['insertedNews'][$editorialId]['multimediaId']] ?? null;
        if (null === $multimedia) {
            return [];
        }

        return $this->getShotsLandscapeFromMedia($multimedia);
    }
}
