<?php

namespace MeestShipping\Exceptions;

class BadRequestException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param array $response
     * @param int $code
     */
    public function __construct($response, $code = 400)
    {
        $message = null;

        if (isset($response['response']['code']) && $response['response']['code'] === 400) {
            $body = json_decode($response['body'], true);

            $message = $body['info']['message']
                .(!empty($body['info']['fieldName']) ? ' ('.$body['info']['fieldName'].') ' : null)
                .(!empty($body['info']['messageDetails']) ? ': '.$body['info']['messageDetails'] : null);
        }

        parent::__construct($message, $code);
    }
}
