<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Trait;

use App\Infrastructure\Enum\SitesEnum;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
trait UrlGeneratorTrait
{
    private string $extension;

    public function extension(): string
    {
        return $this->extension;
    }

    private function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    protected function generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string
    {
        return \sprintf(
            $format,
            $subdomain,
            SitesEnum::getHostnameById($siteId),
            $this->extension,
            trim($urlPath, '/')
        );
    }

    protected function editorialUrl(Editorial $editorial, Section $section): string
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

    protected function sectionUrl(Section $section): string
    {
        return $this->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $section->getPath()
        );
    }
}
