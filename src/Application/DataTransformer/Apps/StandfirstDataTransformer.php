<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Ec\Editorial\Domain\Model\Standfirst;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class StandfirstDataTransformer
{
    private Standfirst $standFirst;

    public function __construct(
        private readonly BodyElementDataTransformerHandler $bodyElementDataTransformerHandler,
    ) {
    }

    /**
     * @return $this
     */
    public function write(Standfirst $standfirst): StandfirstDataTransformer
    {
        $this->standFirst = $standfirst;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        return $this->bodyElementDataTransformerHandler->execute(
            $this->standFirst->content()
        );
    }
}
