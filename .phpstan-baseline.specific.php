<?php declare(strict_types = 1);

/*
 * The following error will occur only on specific PHP versions.
 * Unfortunately, it is not possible to ignore it using a @phpstan-ignore,
 * so we handle it here with a `reportUnmatched' => false` flag to ease the
 * results handling between different PHP versions.
 */

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 28,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 8,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 11,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 17,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 8,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 8,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DatabasesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/ProfilerSection.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Form/Clone/FormCloneHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 5,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket_Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonITILValidation\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 4,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];

$ignoreErrors = array_map(
    function (array $specs): array {
        $specs['reportUnmatched'] = false;
        return $specs;
    },
    $ignoreErrors
);

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
