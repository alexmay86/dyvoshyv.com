<?php
namespace MeestShipping\Core;

use WP_Http;
use MeestShipping\Exceptions\MissingTokenException;
use MeestShipping\Exceptions\BadRequestException;
use MeestShipping\Exceptions\UnauthorizedRequestException;

class Http
{
    private $options;
    private $http;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
        $this->http = new WP_Http();
    }

    public function makeUri($urn, $query = [], $params = []): string
    {
        $url = $urn === 'calculate' ? 'https://api.meest.com/v3.0/internationalCourier' : $this->options['url'];
        $urn = $this->options['urns'][$urn];

        return $url
            .(!empty($query) ? strtr($urn, $query) : $urn)
            .(!empty($params) ? '?'.http_build_query($params) : null);
    }

    public function request($method, $urn, $params = [], $query = [], $file = false)
    {
        $data = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ];

        if (!in_array($urn, ['auth_get', 'auth_refresh'])) {
            $this->options['tokens'] = meest_init('Option')->checkTokens($this->options['tokens']);

            if (empty($this->options['tokens']['token'])) {
                throw new MissingTokenException();
            }

            $data['headers']['token'] = $this->options['tokens']['token'];
        }

        if ($method === 'GET') {
            $uri = $this->makeUri($urn, $query, $params);
        } else {
            $uri = $this->makeUri($urn, $query);
            $data['body'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        $response = $this->http->request($uri, $data);

        if (is_array($response)) {
            if (!empty($response['response']) && $response['response']['code'] === 200) {
                if ($file === false) {
                    $body = json_decode($response['body'], true);

                    if ($body['status'] === 'OK') {
                        return $body['result'];
                    }
                } else {
                    return $response['body'];
                }
            } elseif (!empty($response['response']) && $response['response']['code'] === 401) {
                throw new UnauthorizedRequestException($response);
            } else {
                throw new BadRequestException($response, $response['response']['code'] ?? 400);
            }
        } else {
            wp_die($response->get_error_message());
        }
    }

    public static function addSettingsError($setting, $code, $message, $type = 'error')
    {
        global $wp_settings_errors;

        $wp_settings_errors[] = array(
            'setting' => $setting,
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        );
    }
}
