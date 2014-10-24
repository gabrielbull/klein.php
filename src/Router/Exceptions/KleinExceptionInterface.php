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

/**
 * KleinExceptionInterface
 *
 * Exception interface that Router's exceptions should implement
 *
 * This is mostly for having a simple, common Interface class/namespace
 * that can be type-hinted/instance-checked against, therefore making it
 * easier to handle Router exceptions while still allowing the different
 * exception classes to properly extend the corresponding SPL Exception type
 *
 * @package    Router\Exceptions
 */
interface KleinExceptionInterface
{
}
