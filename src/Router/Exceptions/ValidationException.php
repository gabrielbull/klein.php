<?php
namespace Router\Exceptions;

use UnexpectedValueException;

class ValidationException extends UnexpectedValueException implements KleinExceptionInterface
{
}
