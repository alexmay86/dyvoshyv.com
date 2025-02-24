<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;

defined( 'ABSPATH' ) || exit;

class Cron implements Module
{
    private $options;
    public $hook = 'meest_cron_update_dictionary';
    public $recurrence = 'daily';

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
    }

    public function init()
    {
        add_action($this->hook, [$this, 'run'], 10, 2);

        return $this;
    }

    public function run()
    {
        try {
            (new Dictionary($this->options))->init();
            error_log('Dictionary has been updated');
        } catch (\Exception $e) {
            error_log('Dictionary has not been updated');
        }
    }

    public function add()
    {
        if (!wp_next_scheduled($this->hook, [])) {
            $timestamp = strtotime(date('Y:m:d '. $this->options['dictionary']['cron_timestamp']));
            wp_schedule_event($timestamp, $this->recurrence, $this->hook, []);
        }
    }

    public function delete()
    {
        wp_clear_scheduled_hook($this->hook, []);
        wp_unschedule_event(wp_next_scheduled($this->hook), $this->hook);
        wp_unschedule_hook($this->hook);
    }
}
