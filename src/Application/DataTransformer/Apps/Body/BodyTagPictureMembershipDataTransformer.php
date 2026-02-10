<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Infrastructure\Service\PictureShots;
use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagPictureMembershipDataTransformer extends ElementTypeDataTransformer
{
    /** @var BodyTagPicture */
    protected BodyElement $bodyElement;

    public function __construct(
        private readonly PictureShots $pictureShots,
    ) {
    }

    public function read(): array
    {
        $message = \sprintf('BodyElement should be instance of %s', BodyTagPictureMembership::class);
        Assertion::isInstanceOf($this->bodyElement, BodyTagPictureMembership::class, $message);

        $elementArray = parent::read();

        $shots = $this->pictureShots->retrieveShotsByPhotoId($this->resolveData(), $this->bodyElement);

        if (\count($shots)) {
            $elementArray['shots'] = $shots;
            $elementArray['url'] = reset($shots);
            $elementArray['orientation'] = $this->bodyElement->orientation();
        }

        return $elementArray;
    }

    public function canTransform(): string
    {
        return BodyTagPictureMembership::class;
    }
}
