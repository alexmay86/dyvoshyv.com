<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

abstract class Resource
{
    protected $options;
    protected $data;
    protected $args;

    public function __construct(array $data)
    {
        $this->options = meest_init('Option')->all();
        $this->data = $data;
    }

    abstract public function toArray();

    public static function make($data, ...$args): array
    {
        $self = new static($data);
        $self->args = $args;

        return $self->toArray();
    }
}
