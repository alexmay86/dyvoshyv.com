<?php

namespace MeestShipping\Modules;

class RestApi
{
    public function init()
    {
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    public function restApiInit()
    {
        register_rest_route('meest/v1', 'meest/branch/(?P<ref>[^\/]*)', [
            'callback' => [$this, 'getBranch']
        ]);
    }

    public function getBranch(\WP_REST_Request $request)
    {
    }
}
