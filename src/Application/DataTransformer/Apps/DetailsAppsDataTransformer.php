<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use App\Infrastructure\Enum\ClossingModeEnum;
use App\Infrastructure\Enum\EditorialTypesEnum;
use App\Infrastructure\Trait\UrlGeneratorTrait;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class DetailsAppsDataTransformer implements AppsDataTransformer
{
    use UrlGeneratorTrait;

    /** @var string */
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private Editorial $editorial;

    private Section $section;

    /** @var Tag[] */
    private array $tags;

    public function __construct(
        string $extension,
    ) {
        $this->setExtension($extension);
    }

    /**
     * @param Tag[] $tags
     */
    public function write(
        Editorial $editorial,
        Section $section,
        array $tags,
    ): DetailsAppsDataTransformer {
        $this->editorial = $editorial;
        $this->section = $section;
        $this->tags = $tags;

        return $this;
    }

    public function read(): array
    {
        $editorial = $this->transformerEditorial();
        $editorial['section'] = $this->transformerSection();
        $editorial['tags'] = $this->transformerTags();
        $editorial['adsOptions'] = $this->transformerOptions();
        $editorial['analiticsOptions'] = $this->transformerOptions();

        return $editorial;
    }

    /**
     * @return array<string, array<string, mixed>|bool|int|string>
     */
    private function transformerEditorial(): array
    {
        $editorialType = EditorialTypesEnum::getNameById($this->editorial->editorialType());

        return
            [
                'id' => $this->editorial->id()->id(),
                'url' => $this->editorialUrl(),
                'titles' => [
                    'title' => $this->editorial->editorialTitles()->title(),
                    'preTitle' => $this->editorial->editorialTitles()->preTitle(),
                    'urlTitle' => $this->editorial->editorialTitles()->urlTitle(),
                    'mobileTitle' => $this->editorial->editorialTitles()->mobileTitle(),
                ],
                'lead' => $this->editorial->lead(),
                'publicationDate' => $this->editorial->publicationDate()->format(self::DATE_FORMAT),
                'updatedOn' => $this->editorial->publicationDate()->format(self::DATE_FORMAT),
                'endOn' => $this->editorial->endOn()->format(self::DATE_FORMAT),
                'type' => [
                    'id' => $editorialType['id'],
                    'name' => $editorialType['name'],
                ],
                'indexable' => $this->editorial->indexed(),
                'deleted' => $this->editorial->isDeleted(),
                'published' => $this->editorial->isPublished(),
                'closingModeId' => ClossingModeEnum::getClosingModeById($this->editorial->closingModeId()),
                'commentable' => $this->editorial->canComment(),
                'isBrand' => $this->editorial->isBrand(),
                'isAmazonOnsite' => $this->editorial->isAmazonOnsite(),
                'contentType' => $this->editorial->contentType(),
                'canonicalEditorialId' => $this->editorial->canonicalEditorialId(),
                'urlDate' => $this->editorial->urlDate()->format(self::DATE_FORMAT),
                'countWords' => $this->editorial->body()->countWords(),
            ];
    }

    private function editorialUrl(): string
    {
        $editorialPath = \sprintf(
            '%s/%s/%s_%s',
            $this->section->getPath(),
            $this->editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($this->editorial->editorialTitles()->urlTitle()),
            $this->editorial->id()->id()
        );

        return $this->generateUrl(
            'https://%s.%s.%s/%s',
            $this->section->isSubdomainBlog() ? 'blog' : 'www',
            $this->section->siteId(),
            $editorialPath
        );
    }

    /**
     * @param Section|null $section
     *
     * @return array{
     *  id: string,
     *  name: string,
     *  url: string,
     *  encodeName: string
     * }
     */
    private function transformerSection(?Section $section = null): array
    {
        if (null === $section) {
            $section = $this->section;
        }
        $url = $this->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $section->getPath()
        );

        return [
            'id' => $section->id()->id(),
            'name' => $section->name(),
            'url' => $url,
            'encodeName' => $section->encodeName(),
        ];
    }

    /**
     * @return array<int<0, max>, array<string, mixed>>
     */
    private function transformerTags(): array
    {
        $result = [];
        foreach ($this->tags as $tag) {
            $urlPath = \sprintf(
                '/tags/%s/%s-%s',
                Encode::encodeUrl($tag->type()->name()),
                Encode::encodeUrl($tag->name()),
                $tag->id()->id(),
            );

            $result[] = [
                'id' => $tag->id()->id(),
                'name' => $tag->name(),
                'url' => $this->generateUrl(
                    'https://%s.%s.%s/%s',
                    'www',
                    $this->section->siteId(),
                    $urlPath,
                ),
            ];
        }

        return $result;
    }

    /**
     * @param Section|null $section
     * @param array<int, array{
     *   id: string,
     *   name: string,
     *   url: string,
     *   encodeName: string
     *  }> $result
     *
     * @return array<int, array{
     *  id: string,
     *  name: string,
     *  url: string,
     *  encodeName: string
     * }>
     */
    private function transformerOptions(?Section $section = null, array $result = []): array
    {
        if (null === $section) {
            $section = $this->section;
        }
        $result[] = $this->transformerSection($section);

        if (null === $section->parent()) {
            return array_reverse($result);
        }

        return $this->transformerOptions($section->parent(), $result);
    }
}
