<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagVideoYoutube;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagVideoYoutubeDataTransformer extends ElementTypeDataTransformer
{
    /** @var BodyTagVideoYoutube */
    protected BodyElement $bodyElement;
    private string $playerHost;

    public function __construct(string $playerHost)
    {
        $this->playerHost = $playerHost;
    }

    public function read(): array
    {
        $message = \sprintf('BodyElement should be instance of %s', BodyTagVideoYoutube::class);
        Assertion::isInstanceOf($this->bodyElement, BodyTagVideoYoutube::class, $message);

        $elementArray = parent::read();
        $elementArray['id'] = $this->bodyElement->id();
        $elementArray['width'] = $this->bodyElement->width();
        $elementArray['height'] = $this->bodyElement->height();
        $elementArray['caption'] = $this->bodyElement->caption();
        $elementArray['start'] = $this->bodyElement->start();
        $elementArray['video'] =
            \sprintf(
                '%s/embed/video/%s/%s/%s/%s/',
                $this->playerHost,
                $this->bodyElement->id(),
                $this->bodyElement->width(),
                $this->bodyElement->height(),
                $this->bodyElement->start()
            );

        return $elementArray;
    }

    public function canTransform(): string
    {
        return BodyTagVideoYoutube::class;
    }
}
