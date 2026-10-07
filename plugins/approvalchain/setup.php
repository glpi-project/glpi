<?php

/**
 * Approval Chain plugin for GLPI 11
 *
 * Sequential, multi-step approval workflows for tickets.
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Approvalchain\Approval;
use GlpiPlugin\Approvalchain\Chain;

define('PLUGIN_APPROVALCHAIN_VERSION', '1.0.0');

function plugin_version_approvalchain(): array
{
    return [
        'name'         => 'Approval Chain',
        'version'      => PLUGIN_APPROVALCHAIN_VERSION,
        'author'       => 'Your Organisation',
        'license'      => 'GPLv2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => ['min' => '11.0.0', 'max' => '12.0.0'],
            'php'  => ['min' => '8.2'],
        ],
    ];
}

function plugin_init_approvalchain(): void
{
    global $PLUGIN_HOOKS;

    if (!Plugin::isPluginActive('approvalchain')) {
        return;
    }

    // "Approval chain" tab on tickets
    Plugin::registerClass(Approval::class, ['addtabon' => ['Ticket']]);

    // Menu: Tools > Approval chains (+ "my pending approvals" shortcut)
    $PLUGIN_HOOKS[Hooks::MENU_TOADD]['approvalchain']   = ['tools' => Chain::class];
    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['approvalchain']  = 'front/chain.php';

    // Start a chain automatically when a ticket is created
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['approvalchain'] = [
        'Ticket' => [Approval::class, 'onTicketAdd'],
    ];

    // Block solving/closing while a chain is still running
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['approvalchain'] = [
        'Ticket' => [Approval::class, 'onTicketPreUpdate'],
    ];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['approvalchain'] = [
        'ITILSolution' => [Approval::class, 'onSolutionPreAdd'],
    ];

    // Clean up when a ticket is purged
    $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['approvalchain'] = [
        'Ticket' => [Approval::class, 'onTicketPurge'],
    ];
}
