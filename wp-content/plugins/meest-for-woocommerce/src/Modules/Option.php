<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;
use MeestShipping\Core\Error;
use MeestShipping\Exceptions\UnauthorizedRequestException;

class Option implements Module
{
    private $options;
    private $defaultOptions;

    public function __construct()
    {
        $this->defaultOptions = $this->loadDefaultOptions();
        $this->options = $this->loadOptions();
    }

    public function init()
    {
        return $this;
    }

    public function loadDefaultOptions(): array
    {
        return require_once MEEST_PLUGIN_PATH . '/config/main.php';
    }

    public function loadOptions(): array
    {
        $options = get_option(MEEST_PLUGIN_NAME.'_plugin');
        $options = $options !== false ? (json_decode(meest_crypt($options, false), true) ?? []) : [];

        $tokens = get_option(MEEST_PLUGIN_NAME.'_api');
        $tokens = $tokens !== false ? (json_decode(meest_crypt($tokens, false), true) ?? []) : [];

        return array_replace_recursive($this->defaultOptions, $options, ['tokens' => $tokens]);
    }

    public function all(): array
    {
        return $this->options;
    }

    public function get($key)
    {
        return $this->options[$key] ?? null;
    }

    public function saveOptions($data)
    {
        update_option(MEEST_PLUGIN_NAME.'_plugin', meest_crypt(json_encode($data, JSON_UNESCAPED_UNICODE)));

        $this->options = $this->loadOptions();
    }

    public function getTokens($credential)
    {
        try {
            $data = meest_init('Api')->authGet([
                'username' => $credential['username'],
                'password' => $credential['password'],
            ]);

            return [
                'token' => $data['token'],
                'refreshToken' => $data['refreshToken'],
                'expiresIn' => self::dateExpires($data['expiresIn']),
            ];
        } catch (UnauthorizedRequestException $e) {
            Error::add('setting-save', $e->getMessage(), 'error');
        } catch (\Exception $e) {
            Error::add('bad-request', $e->getMessage(), 'error');
        }

        return [];
    }

    public function saveTokens($data)
    {
        update_option(MEEST_PLUGIN_NAME.'_api', meest_crypt(json_encode($data, JSON_UNESCAPED_UNICODE)));

        $this->options = $this->loadOptions();
    }

    public function checkTokens($data)
    {
        if (empty($data) || self::isExpires($data['expiresIn'])) {
            $data = $this->getTokens($this->options['credential']);

            $this->saveTokens($data);
        };

        return $data;
    }

    private static function dateExpires($time)
    {
        return $time + date('U');
    }

    private static function isExpires($time): bool
    {
        return !empty($time) && (int)date('U') >= $time;
    }
}
