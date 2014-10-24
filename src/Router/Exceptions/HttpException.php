<?php
namespace Router\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    /**
     * Create an HTTP exception from nothing but an HTTP code
     *
     * @param int $code
     * @return HttpException
     */
    public static function createFromCode($code)
    {
        return new static(null, (int)$code);
    }
}
