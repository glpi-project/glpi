<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$tagged of method NetworkPort_Vlan\\:\\:assignVlan\\(\\) expects bool\\|int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkport_vlan.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:showList\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonGLPI\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.dynamic.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$token of method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getResourceOwner\\(\\) expects League\\\\OAuth2\\\\Client\\\\Token\\\\AccessToken, League\\\\OAuth2\\\\Client\\\\Token\\\\AccessTokenInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/smtp_oauth2_callback.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Migration\\:\\:addCrontask\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Migration\\:\\:addCrontask\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.4.x_to_9.5.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Migration\\:\\:addCrontask\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0/native_inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of function Safe\\\\ldap_get_entries expects LDAP\\\\Result, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$user_dn of static method AuthLDAP\\:\\:ldapAuth\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$calendar of static method CalendarSegment\\:\\:showForCalendar\\(\\) expects Calendar, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalendarSegment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$calendar of static method Calendar_Holiday\\:\\:showForCalendar\\(\\) expects Calendar, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar_Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CartridgeItem_PrinterModel\\:\\:showForCartridgeItem\\(\\) expects CartridgeItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem_PrinterModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBRelation\\:\\:getOppositeItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>\\|null, class\\-string\\<CommonGLPI\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonITILCost\\:\\:showForObject\\(\\) expects CommonITILObject\\|Project, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of method LevelAgreement\\:\\:addLevelToDo\\(\\) expects Ticket, \\$this\\(CommonITILObject\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of static method LevelAgreement\\:\\:deleteLevelsToDo\\(\\) expects Ticket, \\$this\\(CommonITILObject\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Html\\:\\:convDateTime\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of method CommonITILObject\\:\\:getTemplateFieldName\\(\\) expects string\\|null, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itil of method CommonITILValidation\\:\\:showSummary\\(\\) expects CommonITILObject, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$contract of static method ContractCost\\:\\:showForContract\\(\\) expects Contract, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ContractCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datacenter of static method DCRoom\\:\\:showForDatacenter\\(\\) expects Datacenter, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$instance of static method Database\\:\\:showForInstance\\(\\) expects DatabaseInstance, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of static method Toolbox\\:\\:str_pad\\(\\) expects string, \\(float\\|int\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$toobserve of static method Ajax\\:\\:updateItem\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$confirm of static method Html\\:\\:showSimpleForm\\(\\) expects string, array\\<int, string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$parent of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) expects DOMElement, \\(DOMNode\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects string, list\\<array\\<string, mixed\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects string, list\\<array\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user_id of method Glpi\\\\Api\\\\API\\:\\:userPicture\\(\\) expects bool\\|int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$middleware of method Glpi\\\\Api\\\\HL\\\\Router\\:\\:registerRequestMiddleware\\(\\) expects Glpi\\\\Api\\\\HL\\\\Middleware\\\\RequestMiddlewareInterface, Glpi\\\\Api\\\\HL\\\\Middleware\\\\AbstractMiddleware given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$middleware of method Glpi\\\\Api\\\\HL\\\\Router\\:\\:registerResponseMiddleware\\(\\) expects Glpi\\\\Api\\\\HL\\\\Middleware\\\\ResponseMiddlewareInterface, Glpi\\\\Api\\\\HL\\\\Middleware\\\\AbstractMiddleware given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$autoload of function Safe\\\\class_implements expects bool, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$condition of method Glpi\\\\Api\\\\HL\\\\Router\\:\\:registerAuthMiddleware\\(\\) expects \\(callable\\(Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\)\\: bool\\)\\|null, Closure\\(Glpi\\\\Api\\\\HL\\\\RoutePath\\)\\: false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$condition of method Glpi\\\\Api\\\\HL\\\\Router\\:\\:registerRequestMiddleware\\(\\) expects \\(callable\\(Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\)\\: bool\\)\\|null, Closure\\(Glpi\\\\Api\\\\HL\\\\RoutePath\\)\\: bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$condition of method Glpi\\\\Api\\\\HL\\\\Router\\:\\:registerResponseMiddleware\\(\\) expects \\(callable\\(Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\)\\: bool\\)\\|null, Closure\\(Glpi\\\\Api\\\\HL\\\\RoutePath\\)\\: false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$callback of function array_filter expects \\(callable\\(int\\|string\\)\\: bool\\)\\|null, Closure\\(mixed\\)\\: \\(0\\|1\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method NetworkPort\\:\\:rawSearchOptionsToAdd\\(\\) expects null, class\\-string\\<Glpi\\\\Asset\\\\Asset\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasNetworkPortCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$entity of method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:copyTilesFromParentEntity\\(\\) expects Entity, CommonDBTM&Glpi\\\\Helpdesk\\\\Tile\\\\LinkableToTilesInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Config/Helpdesk/CopyParentEntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$form_translation of method Glpi\\\\Controller\\\\Form\\\\Translation\\\\UpdateFormTranslationController\\:\\:updateTranslation\\(\\) expects Glpi\\\\Form\\\\FormTranslation, Glpi\\\\ItemTranslation\\\\ItemTranslation given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/UpdateFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$start_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime\\|null, DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$end_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime, DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$start_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime\\|null, DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExportComparison.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$end_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime, DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExportComparison.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of static method AbstractRightsDropdown\\:\\:show\\(\\) expects string, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$callback of function array_filter expects \\(callable\\(int\\|string\\)\\: bool\\)\\|null, Closure\\(mixed\\)\\: \\(0\\|1\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/EndUserInputNameProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/FormTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$source_itemtype of method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:mapItem\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$form of method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:convertFieldConfig\\(\\) expects Glpi\\\\Form\\\\Form, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$composite of method Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogManager\\:\\:hasChildren\\(\\) expects Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogCompositeInterface\\|null, Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogItemInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/ServiceCatalogManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of method Glpi\\\\Inventory\\\\Conf\\:\\:importContentFile\\(\\) expects string, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$associative of function Safe\\\\json_decode expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, array\\{class\\-string\\<CommonDBTM\\>, \'getDefaultSearchReq…\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$odd of static method Glpi\\\\Search\\\\Output\\\\ExportSearchOutput\\:\\:showNewLine\\(\\) expects bool, int\\<0, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/ExportSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function str_pad expects string, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$port of static method IPNetwork_Vlan\\:\\:showForIPNetwork\\(\\) expects IPNetwork, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork_Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tt of static method ITILTemplateField\\:\\:showForITILTemplate\\(\\) expects ITILTemplate, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$camera of static method Item_DeviceCamera_ImageResolution\\:\\:showItems\\(\\) expects DeviceCamera, Item_DeviceCamera given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageResolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$delete_all_column of method Item_Devices\\:\\:getTableGroup\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$common_column of method Item_Devices\\:\\:getTableGroup\\(\\) expects HTMLTableSuperHeader, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$specific_column of method Item_Devices\\:\\:getTableGroup\\(\\) expects HTMLTableSuperHeader, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$delete_column of method Item_Devices\\:\\:getTableGroup\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<0, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, KnowbaseItem_Revision given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$slm of static method LevelAgreement\\:\\:showForSLM\\(\\) expects SLM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$olaType\\|slaType of method OlaLevel_Ticket\\:\\:getFromDBForTicket\\(\\) expects 0\\|1, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$email of static method Supplier\\:\\:getSuppliersByEmail\\(\\) expects bool, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$part of method MailCollector\\:\\:getDecodedContent\\(\\) expects Laminas\\\\Mail\\\\Storage\\\\Message, Laminas\\\\Mail\\\\Storage\\\\Part given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$onlyone of method DBmysql\\:\\:updateOrInsert\\(\\) expects bool, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$super of static method NetworkName\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$display of static method Planning\\:\\:dropdownState\\(\\) expects bool, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$btname of static method Html\\:\\:getSimpleForm\\(\\) expects string, array\\<string, string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$callback of function usort expects callable\\(mixed, mixed\\)\\: int, array\\{\'SimplePie\\\\\\\\SimplePie\', \'sort_items\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$search of static method SavedSearch_Alert\\:\\:showForSavedSearch\\(\\) expects SavedSearch, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$slaType of static method SlaLevel_Ticket\\:\\:doLevelForTicket\\(\\) expects 0\\|1, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Stat\\:\\:constructEntryValues\\(\\) expects class\\-string\\<CommonITILObject\\>, class\\-string\\<CommonGLPI\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$odd of static method Glpi\\\\Search\\\\Output\\\\AbstractSearchOutput\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Glpi\\\\Search\\\\Output\\\\AbstractSearchOutput\\:\\:showItem\\(\\) expects string\\|null, \\(float\\|int\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itil_type of static method Stat\\:\\:getAssetsWithITIL\\(\\) expects class\\-string\\<CommonITILObject\\>, class\\-string\\<CommonDBTM\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Transfer\\:\\:addToAlreadyTransfer\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user of static method AuthLDAP\\:\\:forceOneUserSynchronization\\(\\) expects User, CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 12,
	'path' => __DIR__ . '/src/User.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
