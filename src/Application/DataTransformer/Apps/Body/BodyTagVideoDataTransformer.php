<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagVideo;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagVideoDataTransformer extends ElementTypeDataTransformer
{
    /** @var BodyTagVideo */
    protected BodyElement $bodyElement;
    private string $playerHost;

    public function __construct(string $playerHost)
    {
        $this->playerHost = $playerHost;
    }

    public function read(): array
    {
        $message = \sprintf('BodyElement should be instance of %s', BodyTagVideo::class);
        Assertion::isInstanceOf($this->bodyElement, BodyTagVideo::class, $message);

        $elementArray = parent::read();
        $elementArray['id'] = $this->bodyElement->id()->id();
        $elementArray['width'] = $this->bodyElement->width();
        $elementArray['height'] = $this->bodyElement->height();
        $elementArray['caption'] = $this->bodyElement->caption();
        $elementArray['video'] =
            \sprintf(
                '%s/embed/video/%s/%s/%s/',
                $this->playerHost,
                $this->bodyElement->id()->id(),
                $this->bodyElement->width(),
                $this->bodyElement->height()
            );

        return $elementArray;
    }

    public function canTransform(): string
    {
        return BodyTagVideo::class;
    }
}
