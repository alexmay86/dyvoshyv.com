<?php

namespace MeestShipping\Core;

abstract class Model
{
    protected $db;
    protected $table;
    protected $key = 'id';
    protected $fields;
    protected $formats;
    protected $casts;
    public $attributes = [];
    public $oldAttributes = [];

    public function __construct($data = [])
    {
        global $wpdb;

        $this->db = $wpdb;

        $this->fill($data);
    }

    public function getTable($table = null): string
    {
        $table = $table ?? $this->table;

        return $this->db->prefix . $table;
    }

    public function __get($name)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->oldAttributes[$name] = $this->attributes[$name] ?? null;

        $this->attributes[$name] = $value;
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function getResults($query, $output = ARRAY_A)
    {
        return $this->db->get_results($query, $output);
    }

    public function fill($data = []): void
    {
        if (empty($data)) {
            foreach ($this->fields as $key => $value) {
                $this->$key = $value;
            }
        } else {
            foreach ($this->fields as $key => $value) {
                if (array_key_exists($key, $data)) {
                    if (isset($this->casts[$key])) {
                        if ($this->casts[$key] === 'array') {
                            if (!is_array($data[$key])) {
                                $data[$key] = @json_decode($data[$key], true) ?? $data[$key];
                            }
                        }
                    }

                    $this->$key = $data[$key];
                } elseif (!isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function save(): bool
    {
        $this->setDate('created_at');
        $this->setDate('updated_at');

        $columns = [];
        $formats = [];
        $values = [];
        foreach ($this->fields as $field => $value) {
            if ($this->$field === null) {
                continue;
            }

            $columns[] = $field;
            $formats[] = $this->formats[$field];
            $values[] = is_array($this->$field) ? json_encode($this->$field, JSON_UNESCAPED_UNICODE) : $this->$field;
        }

        $query = "INSERT INTO ".$this->getTable()." (".implode(', ', $columns).") VALUES (".implode(', ', $formats).")";

        if ($this->db->query($this->db->prepare($query, $values))) {
            $this->id = $this->db->insert_id;

            return true;
        }

        return false;
    }

    public function update($data = []): bool
    {
        if (!empty($data)) {
            $this->fill($data);
        }

        $this->setDate('updated_at');

        $columns = [];
        $values = [];
        foreach ($this->fields as $field => $value) {
            if ($field === $this->key) {
                continue;
            }

            if ($this->oldAttributes[$field] == $this->$field) {
                continue;
            }

            $columns[] = $field.' = '.$this->formats[$field];
            $values[] = is_array($this->$field) ? json_encode($this->$field, JSON_UNESCAPED_UNICODE) : $this->$field;
        }

        if (empty($columns)) {
            return true;
        }

        $query = "UPDATE ".$this->getTable()." SET ".implode(', ', $columns)." WHERE $this->key = ".$this->id;

        if ($this->db->query($this->db->prepare($query, $values))) {
            return true;
        }

        return false;
    }

    public function delete(): bool
    {
        $query = "DELETE FROM {$this->getTable()} WHERE $this->key = %d";

        return $this->db->query($this->db->prepare($query, [$this->id])) > 0;
    }

    public static function insert(array $columns, array $values): bool
    {
        $self = new static();

        $sql = "INSERT INTO {$self->getTable()} (".implode(', ', $columns).") VALUES ";

        $formats = array_intersect_key($self->formats, array_flip($columns));

        foreach ($values as $value) {
            $sqlValue = $self->db->prepare( '(' . implode(', ', $formats) . ')', $value);
            if (!empty($sqlValue)) {
                $sqlValues[] = $sqlValue;
            }
        }

        $sql .= implode(', ', $sqlValues);

        return $self->db->query($sql);
    }

    public static function truncate(): bool
    {
        $self = new static();

        return ($self)->db->query("TRUNCATE TABLE {$self->getTable()}");
    }

    public function sync($relation, $data): bool
    {
        $relation = new $relation();
        $key = array_key_first($relation->fields);
        $data[$key] = $this->id;

        $columns = [];
        $formats = [];
        $values = [];
        foreach ($relation->fields as $field => $value) {
            $columns[] = $field;
            $formats[] = $relation->formats[$field];
            $values[] = $data[$field] ?? null;
        }

        $query = "INSERT INTO ".$relation->getTable()." (".implode(', ', $columns).") VALUES (".implode(', ', $formats).")";

        return $this->db->query($this->db->prepare($query, $values)) > 0;
    }

    public function desync($relation, $data = []): bool
    {
        $relation = new $relation();
        $key = array_key_first($relation->fields);
        $data[$key] = $this->id;

        $where = [];
        $values = [];
        foreach ($relation->fields as $field => $value) {
            if (!empty($data[$field])) {
                $where[] = $field.'='.$relation->formats[$field];
                $values[] = $data[$field];
            }
        }

        $query = "DELETE FROM {$relation->getTable()} WHERE ".implode(' AND', $where);

        return $this->db->query($this->db->prepare($query, $values)) > 0;
    }

    public static function total($search)
    {
        $self = new static();

        $query = "SELECT * FROM ".$self->getTable();

        return $self->db->query($query);
    }

    public static function all($ids = [], $key = 'id', $select = '*')
    {
        $self = new static();

        $query = "SELECT $select FROM ".$self->getTable();

        if (!empty($ids)) {
            $query .= " WHERE $key IN (".implode(',', $ids).")";
        }

        return $self->db->get_results($query);
    }

    public static function find($id, $key = 'id')
    {
        $self = new static();

        $query = "SELECT * FROM {$self->getTable()} WHERE $key = $id";

        $data = $self->db->get_row($query, ARRAY_A);

        if ($data !== null) {
            $self->fill($data);

            return $self;
        }

        return null;
    }

    public static function findAll($ids, $key = 'id'): array
    {
        $self = new static();

        $query = "SELECT * FROM {$self->getTable()} WHERE $key IN (".implode(',', $ids).")";

        $results = $self->db->get_results($query, ARRAY_A);

        $objects = [];
        foreach ($results as $result) {
            $object = new static();
            $object->fill($result);
            $objects[] = $object;
        }

        return $objects;
    }

    public static function page($search, $orderby, $current_page, $per_page)
    {
        $self = new static();

        $query = "SELECT * FROM ".$self->getTable();

        if (!empty($orderby)) {
            $query.=' ORDER BY '.$orderby;
        }

        if (!empty($current_page) && !empty($per_page)) {
            $offset = ($current_page - 1) * $per_page;
            $query .= ' LIMIT '.(int) $offset.','.(int) $per_page;
        }

        return $self->db->get_results($query, ARRAY_A);
    }

    public function setDate($field)
    {
        $currentTime = current_time('mysql');

        if ($this->$field !== $currentTime) {
            $this->$field = current_time('mysql');
        }
    }
}
