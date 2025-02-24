<?php

namespace MeestShipping\Exceptions;

class UnauthorizedRequestException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  $response
     * @return void
     */
    public function __construct($response)
    {
        $message = null;

        if (isset($response['response']['code']) && $response['response']['code'] === 401) {
            $body = json_decode($response['body'], true);

            $message = $body['info']['message'];
        }

        parent::__construct($message, 401);
    }
}
