<?php

namespace MeestShipping\Repositories;

abstract class Repository
{
    protected static $instance;
    protected $locale;
    protected $meestLocale;
    protected $options;

    public function __construct()
    {
        $this->locale = substr(get_locale(), 0, 2);
        $this->meestLocale = $this->locale === 'uk' ? 'UA' : strtoupper($this->locale);
        $this->options = meest_init('Option')->all();
    }

    public static function instance(): Repository
    {
        return static::$instance ?? static::$instance = new static();
    }
}
