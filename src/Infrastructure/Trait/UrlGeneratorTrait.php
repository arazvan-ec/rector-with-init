<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Trait;

use App\Infrastructure\Enum\SitesEnum;

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
}
