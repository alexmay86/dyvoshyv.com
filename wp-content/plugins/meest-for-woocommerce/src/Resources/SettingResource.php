<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class SettingResource extends Resource
{
    public function toArray(): array
    {
        $data =  [
            'credential' => [
                'username' => $this->data['credential']['username'] ?? null,
                'password' => $this->data['credential']['password'] ?? null,
                'token' => $this->data['credential']['token'] ?? null,
            ],
            'contact' => [
                'first_name' => $this->data['contact']['first_name'] ?? null,
                'last_name' => $this->data['contact']['last_name'] ?? null,
                'middle_name' => $this->data['contact']['middle_name'] ?? null,
                'phone' => $this->data['contact']['phone'] ?? null,
            ],
            'address' => [
                'delivery_type' => $this->data['address']['delivery_type'] ?? null,
                'country' => [
                    'id' => $this->data['address']['country']['id'] ?? null,
                    'text' => $this->data['address']['country']['text'] ?? null,
                    'code' => $this->data['address']['country']['code'] ?? null,
                ],
                'region' => [
                    'text' => !empty($this->data['address']['country']['id']) && $this->data['address']['country']['id'] !== $this->options['country_id']['ua']
                        ? ($this->data['address']['region']['text'] ?? null)
                        : null,
                ],
                'city' => [
                    'id' => $this->data['address']['city']['id'] ?? null,
                    'text' => $this->data['address']['city']['text'] ?? null,
                ],
                'street' => [
                    'id' => $this->data['address']['street']['id'] ?? null,
                    'text' => $this->data['address']['street']['text'] ?? null,
                ],
                'building' => $this->data['address']['building'] ?? null,
                'flat' => $this->data['address']['flat'] ?? null,
                'postcode' => $this->data['address']['postcode'] ?? null,
                'branch' => [
                    'id' => $this->data['address']['branch']['id'] ?? null,
                    'text' => $this->data['address']['branch']['text'] ?? null,
                ],
            ],
            'shipping' => [
                'delivery_type' => $this->data['shipping']['delivery_type'] ?? null,
                'fixed_cost' => !empty($this->data['shipping']['fixed_cost']) ? $this->data['shipping']['fixed_cost'] : null,
                'calc_cost' => $this->data['shipping']['calc_cost'] ?? 0,
                'auto_cod' => $this->data['shipping']['auto_cod'] ?? 0,
                'branch_limits' => $this->data['shipping']['branch_limits'] ?? 0,
                'package' => $this->data['shipping']['package'] ?? 0,
                'send_email' => $this->data['shipping']['send_email'] ?? 0,
            ],
            'dictionary' => [
                'is_db' => $this->data['dictionary']['is_db'] ?? false,
                'auto_update' => $this->data['dictionary']['auto_update'] ?? false,
            ],
        ];

        if ($this->data['url'] !== $this->options['url']) {
            $data['url'] = $this->data['url'];
        }

        return $data;
    }
}
