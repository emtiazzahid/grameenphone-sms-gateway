<?php

namespace Emtiaz\GrameenphoneSmsGateway\Facades;

use Illuminate\Support\Facades\Facade;

class Grameenphone extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'grameenphone';
    }
}