<?php

namespace MeestShipping\Exceptions;

class MissingTokenException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     */
    public function __construct()
    {
        parent::__construct(__('Token is missing!', MEEST_PLUGIN_DOMAIN), 401);
    }
}
