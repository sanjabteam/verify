<?php

namespace SanjabVerify\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SanjabVerify\Verify
 */
class Verify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'verify';
    }
}
