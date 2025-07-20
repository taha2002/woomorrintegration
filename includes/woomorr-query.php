<?php

class Woomorr_Query {
    protected $table;
    protected $allowed_fields = []; // Security: Whitelist of queryable fields

    protected $wpdb;
    protected $where = [];
    protected $params = [];
    protected $order_by = ''; // Default to empty
    protected $order_dir = 'DESC';
    protected $limit = '';
    protected $offset = '';

    /**
     * @param string $table The table name (without prefix).
     * @param array $allowed_fields A whitelist of column names that can be used in queries.
     */
    public function __construct($table, array $allowed_fields) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . $table;
        $this->allowed_fields = $allowed_fields;

        // Set a default order_by if 'created_at' is a valid field
        if (in_array('created_at', $this->allowed_fields)) {
            $this->order_by = 'created_at';
        }
    }

    /**
     * Checks if a field is allowed to be queried.
     * @param string $field The field name to check.
     * @return bool
     */
    protected function is_allowed_field($field) {
        if (!in_array($field, $this->allowed_fields)) {
            // Optional: Log an error or throw an exception for debugging.
            // trigger_error("Attempted to query a non-whitelisted field: " . esc_html($field), E_USER_WARNING);
            return false;
        }
        return true;
    }

    public function where($field, $value, $operator = '=') {
        if (!$this->is_allowed_field($field)) {
            return $this; // Silently fail or throw exception
        }

        $allowed_operators = ['=', '!=', '<', '>', '<=', '>=', 'IN', 'NOT IN'];
        if (!in_array(strtoupper($operator), $allowed_operators)) {
            $operator = '=';
        }

        // Handle IN and NOT IN operators which require different placeholder formatting
        if (in_array(strtoupper($operator), ['IN', 'NOT IN']) && is_array($value)) {
            if (empty($value)) {
                // Avoid empty IN () clause which is a syntax error
                $this->where[] = "1 = 0"; // Always false
                return $this;
            }
            $placeholders = implode(', ', array_fill(0, count($value), '%s'));
            $this->where[] = "`{$field}` {$operator} ({$placeholders})";
            $this->params = array_merge($this->params, $value);
        } else {
            $this->where[] = "`{$field}` {$operator} %s";
            $this->params[] = $value;
        }

        return $this;
    }

    public function like($field, $value) {
        if (!$this->is_allowed_field($field)) {
            return $this;
        }
        $this->where[] = "`{$field}` LIKE %s";
        $this->params[] = '%' . $this->wpdb->esc_like($value) . '%';
        return $this;
    }

    public function where_date_range($field, $from, $to) {
        if (!$this->is_allowed_field($field)) {
            return $this;
        }
        if ($from) {
            $this->where[] = "`{$field}` >= %s";
            $this->params[] = $from;
        }
        if ($to) {
            $this->where[] = "`{$field}` <= %s";
            $this->params[] = $to;
        }
        return $this;
    }

    public function order_by($field, $direction = 'DESC') {
        // Use the whitelist instead of sanitize_sql_orderby for better security
        if (!$this->is_allowed_field($field)) {
            return $this;
        }
        
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        $this->order_by = $field;
        $this->order_dir = $direction;
        return $this;
    }

    public function paginate($page = 1, $per_page = 20) {
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        
        $offset = ($page - 1) * $per_page;
        $this->limit = $per_page;
        $this->offset = $offset;
        return $this;
    }

    public function get() {
        $where_sql = $this->where ? 'WHERE ' . implode(' AND ', $this->where) : '';
        
        $order_sql = '';
        if ($this->order_by) {
            // Backticks for safety, though already whitelisted.
             $order_sql = "ORDER BY `{$this->order_by}` {$this->order_dir}";
        }

        $limit_sql = '';
        if ($this->limit) {
            // LIMIT and OFFSET must be integers, which we enforce in paginate()
            $limit_sql = $this->wpdb->prepare("LIMIT %d OFFSET %d", $this->limit, $this->offset);
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$this->table}` {$where_sql} {$order_sql} {$limit_sql}";

        // *** THE MAJOR FIX IS HERE ***
        // Pass the params array directly, do not use the spread operator.
        $query = $this->wpdb->prepare($sql, $this->params);

        $results = $this->wpdb->get_results($query);
        $total = (int) $this->wpdb->get_var("SELECT FOUND_ROWS()");

        $per_page_val = $this->limit ?: $total; // Avoid division by zero if not paginated

        return [
            'data'        => $results,
            'total'       => $total,
            'per_page'    => $per_page_val > 0 ? (int) $per_page_val : 20,
            'total_pages' => $per_page_val > 0 ? ceil($total / $per_page_val) : 1,
        ];
    }
}