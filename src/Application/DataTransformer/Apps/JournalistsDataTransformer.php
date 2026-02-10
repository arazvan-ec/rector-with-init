<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Trait\UrlGeneratorTrait;
use Ec\Encode\Encode;
use Ec\Journalist\Domain\Model\Alias;
use Ec\Journalist\Domain\Model\Department;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
class JournalistsDataTransformer
{
    use UrlGeneratorTrait;
    private string $aliasId;
    private bool $hasTwitter = false;
    private Journalist $journalist;
    private Section $section;

    private const TWITTER_REGEX = '/^([A-Za-z0-9_]{1,15})$/';

    public function __construct(
        string $extension,
        private readonly Thumbor $thumbor,
    ) {
        $this->setExtension($extension);
    }

    /**
     * @return $this
     */
    public function write(string $aliasId, Journalist $journalist, Section $section, bool $hasTwitter): JournalistsDataTransformer
    {
        $this->aliasId = $aliasId;
        $this->journalist = $journalist;
        $this->section = $section;
        $this->hasTwitter = $hasTwitter;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        return $this->transformerJournalists();
    }

    /**
     * @return array<string, mixed> $journalists
     */
    private function transformerJournalists(): array
    {
        $signature = [];

        /** @var Alias $alias */
        foreach ($this->journalist->aliases() as $alias) {
            if ($alias->id()->id() === $this->aliasId) {
                $signature['journalistId'] = $this->journalist->id()->id();
                $signature['aliasId'] = $alias->id()->id();
                $signature['name'] = $alias->name();
                $signature['private'] = $alias->private();
                $signature['url'] = '';
                if ($this->journalist->isVisible()) {
                    $signature['url'] = $this->journalistUrl($this->journalist);
                }

                $photo = $this->photoUrl($this->journalist);
                $signature['photo'] = $photo;

                $departments = [];
                /** @var Department $department */
                foreach ($this->journalist->departments() as $department) {
                    $departments[] = [
                        'id' => $department->id()->id(),
                        'name' => $department->name(),
                    ];
                }

                $signature['departments'] = $departments;

                if ($this->hasTwitter && !empty($this->journalist->twitter())) {
                    $signature['twitter'] = $this->withAt($this->journalist->twitter());
                }
            }
        }

        return $signature;
    }

    private function journalistUrl(Journalist $journalist): string
    {
        return $this->generateUrl(
            'https://%s.%s.%s/autores/%s/',
            'www',
            $this->section->siteId(),
            \sprintf('%s-%s', Encode::encodeUrl($journalist->name()), $journalist->id()->id())
        );
    }

    private function photoUrl(Journalist $journalist): string
    {
        if (!empty($journalist->blogPhoto())) {
            return $this->thumbor->createJournalistImage($journalist->blogPhoto());
        }

        if (!empty($journalist->photo())) {
            return $this->thumbor->createJournalistImage($journalist->photo());
        }

        return '';
    }

    private function withAt(string $twitter): string
    {
        if (preg_match(self::TWITTER_REGEX, $twitter)) {
            return \sprintf('@%s', $twitter);
        }

        return $twitter;
    }
}
