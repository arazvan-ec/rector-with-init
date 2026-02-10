<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Infrastructure\Enum\SitesEnum;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Http\Promise\Promise;
use Psr\Http\Message\UriFactoryInterface;

final readonly class MembershipLinkResolver
{
    public function __construct(
        private QueryMembershipClient $queryMembershipClient,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    public function createPromise(Editorial $editorial, string $siteId): MembershipLinkPromise
    {
        $linksData = $this->extractLinksFromBody($editorial->body());

        $links = [];
        $uris = [];
        /** @var string $membershipLink */
        foreach ($linksData as $membershipLink) {
            $uris[] = $this->uriFactory->createUri($membershipLink);
            $links[] = $membershipLink;
        }

        /** @var Promise $promise */
        $promise = $this->queryMembershipClient->getMembershipUrl(
            $editorial->id()->id(),
            $uris,
            SitesEnum::getEncodenameById($siteId),
            true
        );

        return new MembershipLinkPromise($promise, $links);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(MembershipLinkPromise $membershipLinkPromise): array
    {
        if (!$membershipLinkPromise->hasLinks() || null === $membershipLinkPromise->promise) {
            return [];
        }

        try {
            /** @var array<string, mixed> $result */
            $result = $membershipLinkPromise->promise->wait();
        } catch (\Throwable) {
            return [];
        }

        if (empty($result)) {
            return [];
        }

        return array_combine($membershipLinkPromise->originalLinks, $result);
    }

    /**
     * @return array<int, string>
     */
    private function extractLinksFromBody(Body $body): array
    {
        $linksData = [];

        $bodyElementsMembership = $body->bodyElementsOf(BodyTagMembershipCard::class);
        /** @var BodyTagMembershipCard $bodyElement */
        foreach ($bodyElementsMembership as $bodyElement) {
            /** @var MembershipCardButton $button */
            foreach ($bodyElement->buttons()->buttons() as $button) {
                $linksData[] = $button->urlMembership();
                $linksData[] = $button->url();
            }
        }

        return $linksData;
    }
}
