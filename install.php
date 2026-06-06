<?php
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

## SYSTEM #####################################################################################################################################

safe_query("
    INSERT IGNORE INTO settings_plugins
        (pluginID, modulname, admin_file, activate, author, website, index_link, hiddenfiles, version, path, status_display, plugin_display, widget_display, delete_display, sidebar)
    VALUES
        ('', 'todo', 'admin_todo', 1, 'T-Seven', 'https://www.nexpell.de', 'todo', '', '0.1', 'includes/plugins/todo/', 1, 1, 1, 1, 'deactivated');
");

safe_query("
    INSERT IGNORE INTO settings_plugins_lang 
        (content_key, language, content, updated_at)
    VALUES
        ('plugin_name_todo', 'de', 'Todo', NOW()),
        ('plugin_name_todo', 'en', 'Todo', NOW()),
        ('plugin_name_todo', 'it', 'Todo', NOW()),

        ('plugin_info_todo', 'de', 'Dieses Widget zeigt allgemeine Informationen (kleiner Lebenslauf) über Sie auf Ihrer Webspell-RM-RM-Seite an.', NOW()),
        ('plugin_info_todo', 'en', 'This widget will show general information (small resume) todo You on your Webspell-RM-RM site.', NOW()),
        ('plugin_info_todo', 'it', 'Questo widget mostrerà informazioni generali (piccolo curriculum) su di te sul tuo sito Webspell-RM-RM.', NOW())
");

## NAVIGATION #####################################################################################################################################

safe_query("
    INSERT IGNORE INTO navigation_dashboard_links
        (catID, modulname, url, sort)
    VALUES
        (8, 'todo', 'admincenter.php?site=admin_todo', 1)
");
$linkID = mysqli_insert_id($_database);

safe_query("
    INSERT IGNORE INTO navigation_dashboard_lang
        (content_key, language, content, updated_at)
    VALUES
        ('nav_link_{$linkID}', 'de', 'Todo', NOW()),
        ('nav_link_{$linkID}', 'en', 'Todo', NOW()),
        ('nav_link_{$linkID}', 'it', 'Todo', NOW())
");

safe_query("
    INSERT IGNORE INTO navigation_website_sub
        (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
        (3, 'todo', 'index.php?site=todo', 1, 1, NOW())
");

$snavID = mysqli_insert_id($_database);

safe_query("
    INSERT IGNORE INTO navigation_website_lang
        (content_key, language, content, updated_at)
    VALUES
        ('nav_sub_{$snavID}', 'de', 'Todo', NOW()),
        ('nav_sub_{$snavID}', 'en', 'Todo', NOW()),
        ('nav_sub_{$snavID}', 'it', 'Todo', NOW())
");

#######################################################################################################################################
safe_query("
  INSERT IGNORE INTO user_role_admin_navi_rights (id, roleID, type, modulname)
  VALUES ('', 1, 'link', 'todo')
");
 ?>