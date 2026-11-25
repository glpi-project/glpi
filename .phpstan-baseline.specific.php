<?php declare(strict_types = 1);

/*
 * The following error will occur only on specific PHP versions.
 * Unfortunately, it is not possible to ignore it using a @phpstan-ignore,
 * so we handle it here with a `reportUnmatched' => false` flag to ease the
 * results handling between different PHP versions.
 */

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$codepoint of function chr expects int\\<0, 255\\>, int\\<0, 510\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.20_to_10.0.21/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$codepoint of function chr expects int\\<0, 255\\>, int\\<0, 510\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_11.0.2_to_11.0.3/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$codepoint of function chr expects int\\<0, 255\\>, int\\<0, 510\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_11.0.x_to_12.0.0/notifications.php',
];
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
	'message' => '#^Parameter \\#3 \\$options of function setcookie expects array\\{expires\\?\\: int, path\\?\\: string, domain\\?\\: string, secure\\?\\: bool, httponly\\?\\: bool, samesite\\?\\: \'Lax\'\\|\'lax\'\\|\'None\'\\|\'none\'\\|\'Strict\'\\|\'strict\'\\}, array\\{expires\\: \\(float\\|int\\), path\\: string, domain\\: string, secure\\: bool, httponly\\: true, samesite\\: string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$options of function setcookie expects array\\{expires\\?\\: int, path\\?\\: string, domain\\?\\: string, secure\\?\\: bool, httponly\\?\\: bool, samesite\\?\\: \'Lax\'\\|\'lax\'\\|\'None\'\\|\'none\'\\|\'Strict\'\\|\'strict\', partitioned\\?\\: bool\\}, array\\{expires\\: \\(float\\|int\\), path\\: string, domain\\: string, secure\\: bool, httponly\\: true, samesite\\: string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
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
	'message' => '#^Method DbUtils\\:\\:getDbRelations\\(\\) should return array\\<string, array\\<string, list\\<array\\{string, string\\}\\|string\\>\\|string\\>\\> but returns array\\<array\\<list\\<array\\<mixed, mixed\\>\\|string\\>\\>\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getDbRelations\\(\\) should return array\\<string, array\\<string, list\\<array\\{string, string\\}\\|string\\>\\|string\\>\\> but returns array\\<array\\<list\\<array\\<mixed\\>\\|string\\>\\>\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 6,
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
	'count' => 5,
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
	'message' => '#^Access to property \\$data on an unknown class Dom\\\\Text\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$firstChild on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$nextSibling on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$nodeName on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$ownerDocument on an unknown class Dom\\\\Node\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method __construct\\(\\) of deprecated class Symfony\\\\Component\\\\HtmlSanitizer\\\\Parser\\\\MastermindsParser\\:
since Symfony 7\\.4, use `NativeParser` instead$#',
	'identifier' => 'method.deprecatedClass',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method createElement\\(\\) on an unknown class Dom\\\\HTMLDocument\\.$#',
	'identifier' => 'class.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method insertBefore\\(\\) on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method parse\\(\\) of deprecated class Symfony\\\\Component\\\\HtmlSanitizer\\\\Parser\\\\MastermindsParser\\:
since Symfony 7\\.4, use `NativeParser` instead$#',
	'identifier' => 'method.deprecatedClass',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method removeChild\\(\\) on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method replaceChild\\(\\) on an unknown class Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Dom\\\\HTMLElement not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Dom\\\\Text not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiation of deprecated class Symfony\\\\Component\\\\HtmlSanitizer\\\\Parser\\\\MastermindsParser\\:
since Symfony 7\\.4, use `NativeParser` instead$#',
	'identifier' => 'new.deprecatedClass',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\HtmlParser\\:\\:parse\\(\\) has invalid return type Dom\\\\Node\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$node of method Glpi\\\\RichText\\\\HtmlParser\\:\\:fixTableStructure\\(\\) expects Dom\\\\HTMLElement\\|DOMNode, Dom\\\\Node\\|DOMNode given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$document of method Glpi\\\\RichText\\\\HtmlParser\\:\\:fixTableStructure\\(\\) has invalid type Dom\\\\HTMLDocument\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$node of method Glpi\\\\RichText\\\\HtmlParser\\:\\:fixTableStructure\\(\\) has invalid type Dom\\\\HTMLElement\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\<" between int\\<80600, 80699\\> and 80400 is always false\\.$#',
	'identifier' => 'smaller.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\HtmlParser\\:\\:parse\\(\\) never returns DOMNode so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$document of method Glpi\\\\RichText\\\\HtmlParser\\:\\:fixTableStructure\\(\\) expects Dom\\\\HTMLDocument\\|DOMDocument, Dom\\\\Document given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$node of method Glpi\\\\RichText\\\\HtmlParser\\:\\:fixTableStructure\\(\\) expects Dom\\\\HTMLElement\\|DOMNode, Dom\\\\Node given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/HtmlParser.php',
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
    'identifier' => 'offsetAccess.invalidOffset',
    'count' => 2,
    'path' => __DIR__ . '/src/Item_Ola.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type int\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$interval of method DateTime\\:\\:add\\(\\) expects DateInterval, DateInterval\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
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
	'message' => '#^Parameter \\#1 \\$codepoint of function chr expects int\\<0, 255\\>, int\\<\\-25, 280\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
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
