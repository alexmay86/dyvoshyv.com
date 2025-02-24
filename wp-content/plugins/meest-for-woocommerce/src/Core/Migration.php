<?php

namespace MeestShipping\Core;

use MeestShipping\Contracts\Module;

defined( 'ABSPATH' ) || exit;

class Migration implements Module
{
    private $db;
    private $migrations;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->migrations = $this->load();
    }

    public function init()
    {
        return $this;
    }

    public function create(array $tables)
    {
        $collate = $this->db->get_charset_collate();

        foreach ($this->migrations as $key => $sql) {
            if (in_array($key, $tables)) {
                try {
                    $this->db->query(strtr($sql, [
                        '{prefix}' => $this->db->prefix,
                        '{collate}' => $collate,
                    ]));
                } catch (\Throwable $e) {
                    error_log($e->getMessage());

                    throw $e;
                }
            }
        }
    }

    public function delete(array $tables)
    {
        foreach ($this->migrations as $key => $sql) {
            if (in_array($key, $tables)) {
                try {
                    $this->db->query("DROP TABLE {$this->db->prefix}meest_{$key}");
                } catch (\Throwable $e) {
                    error_log($e->getMessage());

                    throw $e;
                }
            }
        }
    }

    public function load(): array
    {
        return require_once MEEST_PLUGIN_PATH . '/migrations/main.php';
    }
}
