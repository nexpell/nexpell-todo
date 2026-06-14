<?php

declare(strict_types=1);

if (!function_exists('safe_query')) {
    die('Access denied');
}

global $modulname, $version, $plugin;

$modulname = 'todo';
$version = isset($plugin['version']) ? (string)$plugin['version'] : ($version ?? '0.0.0');

if (!function_exists('todo_table_exists')) {
    function todo_table_exists(string $table): bool
    {
        $result = safe_query("SHOW TABLES LIKE '" . escape($table) . "'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('todo_column_exists')) {
    function todo_column_exists(string $table, string $column): bool
    {
        $tableEsc = str_replace('`', '``', $table);
        $result = safe_query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '" . escape($column) . "'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('todo_index_exists')) {
    function todo_index_exists(string $table, string $index): bool
    {
        $tableEsc = str_replace('`', '``', $table);
        $result = safe_query("SHOW INDEX FROM `{$tableEsc}` WHERE Key_name = '" . escape($index) . "'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

require __DIR__ . '/install.php';

if (!todo_table_exists('plugins_todo')) {
    return;
}

$columns = [
    'assigned_to' => "
        ALTER TABLE plugins_todo
        ADD COLUMN assigned_to INT(11) DEFAULT NULL AFTER userID
    ",
    'description' => "
        ALTER TABLE plugins_todo
        ADD COLUMN description TEXT DEFAULT NULL AFTER task
    ",
    'priority' => "
        ALTER TABLE plugins_todo
        ADD COLUMN priority ENUM('low','medium','high')
        DEFAULT 'medium' AFTER description
    ",
    'due_date' => "
        ALTER TABLE plugins_todo
        ADD COLUMN due_date DATETIME DEFAULT NULL AFTER priority
    ",
    'progress' => "
        ALTER TABLE plugins_todo
        ADD COLUMN progress TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER done
    ",
    'updated_at' => "
        ALTER TABLE plugins_todo
        ADD COLUMN updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER created_at
    ",
    'updated_by' => "
        ALTER TABLE plugins_todo
        ADD COLUMN updated_by INT(11) DEFAULT NULL AFTER updated_at
    "
];

foreach ($columns as $name => $sql) {
    if (!todo_column_exists('plugins_todo', $name)) {
        safe_query($sql);
    }
}

if (todo_column_exists('plugins_todo', 'assigned_to') && !todo_index_exists('plugins_todo', 'idx_todo_assigned_to')) {
    safe_query("CREATE INDEX idx_todo_assigned_to ON plugins_todo (assigned_to)");
}

if (todo_column_exists('plugins_todo', 'updated_by') && !todo_index_exists('plugins_todo', 'idx_todo_updated_by')) {
    safe_query("CREATE INDEX idx_todo_updated_by ON plugins_todo (updated_by)");
}
