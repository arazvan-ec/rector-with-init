<?php

/**
 * @copyright
 */

namespace App\Exception;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class EditorialNotPublishedYetException extends \Exception
{
    private const MESSAGE = 'Editorial not published';
    private const CODE = 404;

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, self::CODE, $previous);
    }
}
