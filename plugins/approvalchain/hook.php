<?php

function plugin_approvalchain_install(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    $charset   = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $sign      = DBConnection::getDefaultPrimaryKeySignOption();
    $engine    = "ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC";

    $seed = false;

    // Chain definitions (templates)
    if (!$DB->tableExists('glpi_plugin_approvalchain_chains')) {
        $seed = true;
        $DB->doQuery("CREATE TABLE `glpi_plugin_approvalchain_chains` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `entities_id` int {$sign} NOT NULL DEFAULT 0,
            `is_recursive` tinyint NOT NULL DEFAULT 0,
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `is_active` tinyint NOT NULL DEFAULT 0,
            `itilcategories_id` int {$sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_active` (`is_active`),
            KEY `itilcategories_id` (`itilcategories_id`)
        ) {$engine}");
    }

    // Steps of a chain definition
    if (!$DB->tableExists('glpi_plugin_approvalchain_chainsteps')) {
        $DB->doQuery("CREATE TABLE `glpi_plugin_approvalchain_chainsteps` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `plugin_approvalchain_chains_id` int {$sign} NOT NULL DEFAULT 0,
            `position` int NOT NULL DEFAULT 1,
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `users_id` int {$sign} NOT NULL DEFAULT 0,
            `groups_id` int {$sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_approvalchain_chains_id` (`plugin_approvalchain_chains_id`),
            KEY `position` (`position`)
        ) {$engine}");
    }

    // A chain running on a ticket (snapshot of the definition)
    if (!$DB->tableExists('glpi_plugin_approvalchain_approvals')) {
        $DB->doQuery("CREATE TABLE `glpi_plugin_approvalchain_approvals` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `tickets_id` int {$sign} NOT NULL DEFAULT 0,
            `plugin_approvalchain_chains_id` int {$sign} NOT NULL DEFAULT 0,
            `name` varchar(255) DEFAULT NULL,
            `status` tinyint NOT NULL DEFAULT 1,
            `users_id` int {$sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `status` (`status`)
        ) {$engine}");
    }

    // Steps of a running chain
    if (!$DB->tableExists('glpi_plugin_approvalchain_approvalsteps')) {
        $DB->doQuery("CREATE TABLE `glpi_plugin_approvalchain_approvalsteps` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `plugin_approvalchain_approvals_id` int {$sign} NOT NULL DEFAULT 0,
            `position` int NOT NULL DEFAULT 1,
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `users_id` int {$sign} NOT NULL DEFAULT 0,
            `groups_id` int {$sign} NOT NULL DEFAULT 0,
            `status` tinyint NOT NULL DEFAULT 1,
            `users_id_decision` int {$sign} NOT NULL DEFAULT 0,
            `comment_decision` text,
            `date_decision` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_approvalchain_approvals_id` (`plugin_approvalchain_approvals_id`),
            KEY `status` (`status`),
            KEY `users_id` (`users_id`),
            KEY `groups_id` (`groups_id`)
        ) {$engine}");
    }

    // Example chain (inactive, approvers unassigned) so the UI is not empty
    if ($seed) {
        $now = date('Y-m-d H:i:s');
        $DB->insert('glpi_plugin_approvalchain_chains', [
            'entities_id'  => 0,
            'is_recursive' => 1,
            'name'         => 'Student device request (example)',
            'comment'      => 'Example chain. Assign an approver to every step, then activate it.',
            'is_active'    => 0,
            'date_creation' => $now,
            'date_mod'      => $now,
        ]);
        $chain_id = $DB->insertId();

        $steps = [
            ['Validate student record',  'Student is enrolled and in good standing.'],
            ['Validate device status',   'Device is available, functional and not already loaned.'],
            ['Department approval',      'Head of department authorises the loan.'],
            ['Deposit / fees check',     'Deposit paid or fees waived.'],
            ['Final IT release',         'IT hands over the device and records the asset.'],
        ];
        foreach ($steps as $i => [$name, $comment]) {
            $DB->insert('glpi_plugin_approvalchain_chainsteps', [
                'plugin_approvalchain_chains_id' => $chain_id,
                'position'      => $i + 1,
                'name'          => $name,
                'comment'       => $comment,
                'users_id'      => 0,
                'groups_id'     => 0,
                'date_creation' => $now,
                'date_mod'      => $now,
            ]);
        }
    }

    return true;
}

function plugin_approvalchain_uninstall(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    foreach (['approvalsteps', 'approvals', 'chainsteps', 'chains'] as $table) {
        $DB->doQuery("DROP TABLE IF EXISTS `glpi_plugin_approvalchain_{$table}`");
    }

    // '%' also matches the namespace backslashes
    $DB->delete('glpi_displaypreferences', ['itemtype' => ['LIKE', 'GlpiPlugin%Approvalchain%']]);
    $DB->delete('glpi_logs', ['itemtype' => ['LIKE', 'GlpiPlugin%Approvalchain%']]);

    return true;
}
