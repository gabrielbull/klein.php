<?php
namespace Router\Exceptions;

use RuntimeException;

class DispatchHaltedException extends RuntimeException implements KleinExceptionInterface
{

    /**
     * Skip this current match/callback
     *
     * @const int
     */
    const SKIP_THIS = 1;

    /**
     * Skip the next match/callback
     *
     * @const int
     */
    const SKIP_NEXT = 2;

    /**
     * Skip the rest of the matches
     *
     * @const int
     */
    const SKIP_REMAINING = 0;


    /**
     * The number of next matches to skip on a "next" skip
     *
     * @var int
     */
    protected $number_of_skips = 1;


    /**
     * Gets the number of matches to skip on a "next" skip
     *
     * @return int
     */
    public function getNumberOfSkips()
    {
        return $this->number_of_skips;
    }

    /**
     * Sets the number of matches to skip on a "next" skip
     *
     * @param int $number_of_skips
     * @return DispatchHaltedException
     */
    public function setNumberOfSkips($number_of_skips)
    {
        $this->number_of_skips = (int)$number_of_skips;

        return $this;
    }
}
