<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagMembershipCardDataTransformer extends ElementTypeDataTransformer
{
    /** @var BodyTagMembershipCard */
    protected BodyElement $bodyElement;

    public function __construct(
        private readonly BodyElementDataTransformerHandler $bodyElementDataTransformerHandler,
    ) {
    }

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.BodyTagMembershipCard::class;
        Assertion::isInstanceOf($this->bodyElement, BodyTagMembershipCard::class, $message);

        $elementArray = parent::read();
        $elementArray['title'] = $this->bodyElement->title();
        $elementArray['buttons'] = $this->retrieveButtons(
            $this->bodyElement->buttons(),
            $this->resolveData()
        );
        $elementArray['titleBanner'] = $this->bodyElement->titleBanner();
        $elementArray['classBanner'] = $this->bodyElement->classBanner();
        $elementArray['picture'] = $this->bodyElementDataTransformerHandler->execute(
            $this->bodyElement->bodyTagPictureMembership(),
            $this->resolveData()
        );

        return $elementArray;
    }

    public function canTransform(): string
    {
        return BodyTagMembershipCard::class;
    }

    /**
     * @param array<string, mixed> $membershipLinkCombine
     *
     * @return array<int, array<string, mixed>>
     */
    private function retrieveButtons(MembershipCardButtons $buttons, array $membershipLinkCombine): array
    {
        $arrayButtons = [];
        /** @var array<string, mixed> $membershipLinkCombine */
        $membershipLinkCombine = $membershipLinkCombine['membershipLinkCombine'] ?? [];
        /** @var MembershipCardButton $button */
        foreach ($buttons->buttons() as $button) {
            $url = $button->url();
            $urlMembership = $button->urlMembership();
            $arrayButtons[] = [
                'url' => $membershipLinkCombine[$url] ?? $url,
                'urlMembership' => $membershipLinkCombine[$urlMembership] ?? $urlMembership,
                'text' => $button->cta(),
            ];
        }

        return $arrayButtons;
    }
}
