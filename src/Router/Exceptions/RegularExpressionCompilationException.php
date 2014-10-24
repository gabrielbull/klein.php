<?php
/**
 * Router (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */

namespace Router\Exceptions;

use \RuntimeException;

/**
 * RegularExpressionCompilationException
 *
 * Exception used for when a regular expression fails to compile
 * 
 * @uses       Exception
 * @package    Router\Exceptions
 */
class RegularExpressionCompilationException extends RuntimeException implements KleinExceptionInterface
{
}
