<?php

if (!function_exists('safe_query')) {
    die('Access denied');
}

global $plugin;

PluginInstallerHelper::install([

    'modulname'  => 'todo',
    'name'       => 'Todo',
    'version'    => (string)($plugin['version'] ?? '0.0.0'),
    'author'     => 'T-Seven',
    'website'    => 'https://www.nexpell.de',
    'path'       => 'includes/plugins/todo/',

    'admin_file' => 'admin_todo',
    'index_link' => 'todo',
    'sidebar'    => 'deactivated',

    'languages' => [
        'plugin_info_todo' => [
            'de' => 'Mit diesem Plugin könnt ihr Aufgaben erstellen, zuweisen und den Fortschritt verwalten.',
            'en' => 'With this plugin you can create, assign, and manage todo tasks.',
            'it' => 'Con questo plugin puoi creare, assegnare e gestire le attività todo.'
        ]
    ],

    'permissions' => [
        'todo'
    ],

    'admin_navigation' => [
        [
            'url'   => 'admincenter.php?site=admin_todo',
            'catID' => 8,
            'sort'  => 1,
            'labels' => [
                'de' => 'Todo',
                'en' => 'Todo',
                'it' => 'Todo'
            ]
        ]
    ],

    'website_navigation' => [
        [
            'url'        => 'index.php?site=todo',
            'mnavID'     => 3,
            'sort'       => 1,
            'indropdown' => 1,
            'labels' => [
                'de' => 'Todo',
                'en' => 'Todo',
                'it' => 'Todo'
            ]
        ]
    ]

]);

safe_query("CREATE TABLE IF NOT EXISTS plugins_todo (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `userID` INT(11) NOT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `task` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `due_date` DATETIME DEFAULT NULL,
  `done` TINYINT(1) NOT NULL DEFAULT 0,
  `progress` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_todo_assigned_to` (`assigned_to`),
  KEY `idx_todo_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
