<?php
namespace Router\Exceptions;

use RuntimeException;

class LockedResponseException extends RuntimeException implements KleinExceptionInterface
{
}
