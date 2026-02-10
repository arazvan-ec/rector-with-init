<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EditorialResponse
{
    /**
     * @param TagDto[]                       $tags
     * @param SectionDto[]                   $adsOptions
     * @param SectionDto[]                   $analiticsOptions
     * @param array<int, array<string, mixed>> $signatures
     * @param array<int, array<string, mixed>> $body
     * @param array<int, array<string, mixed>> $standfirst
     * @param array<int, array<string, mixed>> $recommendedEditorials
     */
    public function __construct(
        public string $id,
        public string $url,
        public EditorialTitlesDto $titles,
        public string $lead,
        public string $publicationDate,
        public string $updatedOn,
        public string $endOn,
        public EditorialTypeDto $type,
        public bool $indexable,
        public bool $deleted,
        public bool $published,
        public string $closingModeId,
        public bool $commentable,
        public bool $isBrand,
        public bool $isAmazonOnsite,
        public string $contentType,
        public string $canonicalEditorialId,
        public string $urlDate,
        public int $countWords,
        public int $countComments,
        public SectionDto $section,
        public array $tags,
        public array $adsOptions,
        public array $analiticsOptions,
        public array $signatures,
        public array $body,
        public ?array $multimedia,
        public array $standfirst,
        public array $recommendedEditorials,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'titles' => $this->titles->toArray(),
            'lead' => $this->lead,
            'publicationDate' => $this->publicationDate,
            'updatedOn' => $this->updatedOn,
            'endOn' => $this->endOn,
            'type' => $this->type->toArray(),
            'indexable' => $this->indexable,
            'deleted' => $this->deleted,
            'published' => $this->published,
            'closingModeId' => $this->closingModeId,
            'commentable' => $this->commentable,
            'isBrand' => $this->isBrand,
            'isAmazonOnsite' => $this->isAmazonOnsite,
            'contentType' => $this->contentType,
            'canonicalEditorialId' => $this->canonicalEditorialId,
            'urlDate' => $this->urlDate,
            'countWords' => $this->countWords,
            'section' => $this->section->toArray(),
            'tags' => array_map(
                static fn (TagDto $tag) => $tag->toArray(),
                $this->tags
            ),
            'adsOptions' => array_map(
                static fn (SectionDto $section) => $section->toArray(),
                $this->adsOptions
            ),
            'analiticsOptions' => array_map(
                static fn (SectionDto $section) => $section->toArray(),
                $this->analiticsOptions
            ),
            'countComments' => $this->countComments,
            'signatures' => $this->signatures,
            'body' => $this->body,
            'multimedia' => $this->multimedia,
            'standfirst' => $this->standfirst,
            'recommendedEditorials' => $this->recommendedEditorials,
        ];
    }
}
