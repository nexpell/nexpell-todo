<?php
/**
 * NEXPELL Todo Update
 * OLD → NEW STRUCTURE
 * FINAL – SAFE & IDEMPOTENT
 */

declare(strict_types=1);

echo "<div class='alert alert-info'><b>Todo Update gestartet…</b></div>";

/* =========================================================
   HELPER
========================================================= */
function todo_table_exists(string $table): bool {
    $r = safe_query("SHOW TABLES LIKE '".escape($table)."'");
    return $r && mysqli_num_rows($r) > 0;
}

function todo_column_exists(string $table, string $column): bool {
    $r = safe_query("SHOW COLUMNS FROM `$table` LIKE '".escape($column)."'");
    return $r && mysqli_num_rows($r) > 0;
}

function todo_index_exists(string $table, string $index): bool {
    $r = safe_query("SHOW INDEX FROM `$table` WHERE Key_name='".escape($index)."'");
    return $r && mysqli_num_rows($r) > 0;
}

/* =========================================================
   TABLE CHECK
========================================================= */
if (!todo_table_exists('plugins_todo')) {
    echo "<div class='alert alert-danger'>❌ Tabelle plugins_todo fehlt</div>";
    return;
}

/* =========================================================
   SPALTEN
========================================================= */

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
    ",

    'progress' => "
        ALTER TABLE plugins_todo
        ADD COLUMN progress TINYINT(3) UNSIGNED DEFAULT 0 AFTER updated_by
    ",
];

foreach ($columns as $name => $sql) {
    if (!todo_column_exists('plugins_todo', $name)) {
        safe_query($sql);
        echo "<div class='text-success'>✅ Spalte <b>$name</b> hinzugefügt</div>";
    } else {
        echo "<div class='text-muted'>ℹ️ Spalte <b>$name</b> existiert bereits</div>";
    }
}

/* =========================================================
   INDIZES (EMPFOHLEN)
========================================================= */
if (!todo_index_exists('plugins_todo', 'idx_todo_assigned_to')) {
    safe_query("CREATE INDEX idx_todo_assigned_to ON plugins_todo (assigned_to)");
    echo "<div class='text-success'>✅ Index assigned_to</div>";
}

if (!todo_index_exists('plugins_todo', 'idx_todo_updated_by')) {
    safe_query("CREATE INDEX idx_todo_updated_by ON plugins_todo (updated_by)");
    echo "<div class='text-success'>✅ Index updated_by</div>";
}

/* =========================================================
   VERSION UPDATE
========================================================= */
safe_query("
    UPDATE settings_plugins
    SET version = '0.3'
    WHERE modulname = 'todo'
");

safe_query("
    UPDATE settings_plugins_installed
    SET version = '0.3', installed_date = NOW()
    WHERE modulname = 'todo'
");

echo "<div class='alert alert-success'>
    <b>Todo Update abgeschlossen.</b>
</div>";
