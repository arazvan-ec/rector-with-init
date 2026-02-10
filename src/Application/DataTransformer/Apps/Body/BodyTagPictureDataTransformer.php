<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Infrastructure\Service\PictureShots;
use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagPictureDataTransformer extends ElementTypeDataTransformer
{
    /** @var BodyTagPicture */
    protected BodyElement $bodyElement;

    public function __construct(
        private readonly PictureShots $pictureShots,
    ) {
    }

    public function read(): array
    {
        $message = \sprintf('BodyElement should be instance of %s', BodyTagPicture::class);
        Assertion::isInstanceOf($this->bodyElement, BodyTagPicture::class, $message);

        $elementArray = parent::read();
        $shots = $this->pictureShots->retrieveShotsByPhotoId($this->resolveData(), $this->bodyElement);

        if (\count($shots)) {
            $elementArray['shots'] = $shots;
            $elementArray['url'] = reset($shots);
            $elementArray['caption'] = $this->bodyElement->caption() ?: $this->bodyElement->alternate();
            $elementArray['alternate'] = $this->bodyElement->alternate();
            $elementArray['orientation'] = $this->bodyElement->orientation();
        }

        return $elementArray;
    }

    public function canTransform(): string
    {
        return BodyTagPicture::class;
    }
}
