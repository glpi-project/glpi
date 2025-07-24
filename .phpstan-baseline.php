<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Variable \\$itemtype in empty\\(\\) always exists and is not falsy\\.$#',
	'identifier' => 'empty.variable',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$tagged of method NetworkPort_Vlan\\:\\:assignVlan\\(\\) expects bool\\|int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkport_vlan.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDBTM\\:\\:add\\(\\) expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notepad.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
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
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$item_type_output_param of static method Html\\:\\:printPager\\(\\) expects int\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$item_type_output_param of static method Html\\:\\:printPager\\(\\) expects int\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of class mysqli constructor expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$expression of class Glpi\\\\DBAL\\\\QueryExpression constructor expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.16_to_10.0.17/tree_dropdowns.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$rights of method Migration\\:\\:addRight\\(\\) expects int, array\\<int, int\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0/impersonate_right.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Migration\\:\\:addCrontask\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$crit of method DBmysqlIterator\\:\\:analyseCrit\\(\\) expects array\\<string\\>, array\\<string, array\\<string, string\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.4.3_to_9.4.5.php',
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
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$appliance of static method Appliance_Item\\:\\:showItems\\(\\) expects Appliance, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Appliance_Item\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Appliance_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:connection_ldap\\(\\) never assigns null to &\\$error so it can be removed from the by\\-ref type\\.$#',
	'identifier' => 'parameterByRef.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on object\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$action of static method AuthLDAP\\:\\:ldapImportUserByServerId\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
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
	'message' => '#^Offset \'connect_string\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'id\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
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
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CartridgeItem_PrinterModel\\:\\:showForCartridgeItem\\(\\) expects CartridgeItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem_PrinterModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem_PrinterModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$certificate of static method Certificate_Item\\:\\:showForCertificate\\(\\) expects Certificate, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Certificate_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter &\\$item by\\-ref type of method CommonDBConnexity\\:\\:canConnexityItem\\(\\) expects CommonDBTM\\|null, CommonDBTM\\|false given\\.$#',
	'identifier' => 'parameterByRef.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$message_type of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects int, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function array_slice expects int\\|null, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_integer\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBRelation\\:\\:getOppositeItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>\\|null, class\\-string\\<CommonGLPI\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Unsafe call to private method CommonGLPI\\:\\:getTabIconClass\\(\\) through static\\:\\:\\.$#',
	'identifier' => 'staticClassAccess.privateMethod',
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
	'message' => '#^Parameter \\#1 \\$ID of method ITILSolution\\:\\:showForm\\(\\) expects int, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
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
	'message' => '#^Call to an undefined static method CommonDBRelation\\:\\:getLinkedTo\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
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
	'message' => '#^Method CommonITILValidation\\:\\:getItilObjectItemType\\(\\) invoked with 1 parameter, 0 required\\.$#',
	'identifier' => 'arguments.count',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidationCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonItilObject_Item\\:\\:showListForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$obj of static method CommonItilObject_Item\\:\\:showForObject\\(\\) expects CommonITILObject\\|TicketRecurrent, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDropdown\\:\\:showForm\\(\\) expects array, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$contact of static method Contact_Supplier\\:\\:showForContact\\(\\) expects Contact, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Contact_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$supplier of static method Contact_Supplier\\:\\:showForSupplier\\(\\) expects Supplier, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$contract of static method ContractCost\\:\\:showForContract\\(\\) expects Contract, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ContractCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Contract_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$first_connection on null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of method mysqli\\:\\:options\\(\\) expects int\\|string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of method mysqli\\:\\:real_connect\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function current expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function key expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$crit of method DBmysqlIterator\\:\\:analyseCrit\\(\\) expects array\\<string\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method DatabaseInstance\\:\\:showInstances\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Domain_Item\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Domain_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function implode expects array\\<string\\>, list\\<array\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$confirm of static method Html\\:\\:showSimpleForm\\(\\) expects string, array\\<int, string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method CommonDBTM\\:\\:getAdditionalField\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Mime\\\\Header\\\\HeaderInterface\\:\\:getAddresses\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$ln of method TCPDF\\:\\:Cell\\(\\) expects int, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$parent of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) expects DOMElement, \\(DOMNode\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Glpi\\\\Exception\\\\PasswordTooWeakException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getSearchOptionUniqID\\(\\) expects CommonDBTM, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
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
	'message' => '#^Parameter \\$full_schema of static method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getDropdownTypeSchema\\(\\) expects string\\|null, array\\<string, array\\<string, array\\>\\|string\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getSubitemLinkFields\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getSubitemSchemaFor\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$errors of static method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getInvalidParametersErrorResponse\\(\\) expects array\\{missing\\?\\: array\\<string\\>, invalid\\?\\: array\\<array\\{name\\: string, reason\\?\\: string\\}\\>\\}, array\\{invalid\\: array\\{name\\: \'field\'\\}\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression "\\$action\\(\\$input\\)" on a separate line does not do anything\\.$#',
	'identifier' => 'expr.resultUnused',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:resumeSession\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:translate\\(\\) expects CommonDBTM&Glpi\\\\ItemTranslation\\\\Context\\\\ProvideTranslationsInterface, CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/I18nExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method NetworkPort\\:\\:rawSearchOptionsToAdd\\(\\) expects null, class\\-string\\<Glpi\\\\Asset\\\\Asset\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasNetworkPortCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:canViewGroupObjects\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Plugin\\\\Acl\\:\\:canViewGroupObjects\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:shouldSetDBConfig\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$output of class Glpi\\\\Progress\\\\ConsoleProgressIndicator constructor expects Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutputInterface, Symfony\\\\Component\\\\Console\\\\Output\\\\OutputInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$output of class Glpi\\\\Progress\\\\ConsoleProgressIndicator constructor expects Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutputInterface, Symfony\\\\Component\\\\Console\\\\Output\\\\OutputInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Match expression does not handle remaining value\\: mixed$#',
	'identifier' => 'match.unhandled',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$output of class Glpi\\\\Progress\\\\ConsoleProgressIndicator constructor expects Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutputInterface, Symfony\\\\Component\\\\Console\\\\Output\\\\OutputInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginMigrationCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
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
	'message' => '#^Parameter \\#2 \\$replace of function str_replace expects array\\<string\\>\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Match expression does not handle remaining value\\: string$#',
	'identifier' => 'match.unhandled',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of static method AbstractRightsDropdown\\:\\:show\\(\\) expects string, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Static method Glpi\\\\Dashboard\\\\Provider\\:\\:getSearchOptionID\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$loader of class Symfony\\\\Component\\\\DependencyInjection\\\\Loader\\\\Configurator\\\\ContainerConfigurator constructor expects Symfony\\\\Component\\\\DependencyInjection\\\\Loader\\\\PhpFileLoader, Symfony\\\\Component\\\\Config\\\\Loader\\\\LoaderInterface\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$form of class Glpi\\\\Form\\\\Condition\\\\Engine constructor expects Glpi\\\\Form\\\\Form, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AnswersHandler/AnswersHandler.php',
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
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$form of method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:convertFieldConfig\\(\\) expects Glpi\\\\Form\\\\Form, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$composite of method Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogManager\\:\\:hasChildren\\(\\) expects Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogCompositeInterface\\|null, Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogItemInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/ServiceCatalogManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cartridgeblackmatte\'\\|\'cartridgeblackphoto\' on array\\{tonerblack\\: array\\{\'tonerblack2\'\\}, tonerblackmax\\: array\\{\'tonerblack2max\'\\}, tonerblackused\\: array\\{\'tonerblack2used\'\\}, tonerblackremaining\\: array\\{\'tonerblack2remaining\'\\}\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cartridgematteblack\'\\|\'cartridgephotoblack\' on array\\{tonerblack\\: array\\{\'tonerblack2\'\\}, tonerblackmax\\: array\\{\'tonerblack2max\'\\}, tonerblackused\\: array\\{\'tonerblack2used\'\\}, tonerblackremaining\\: array\\{\'tonerblack2remaining\'\\}\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function current expects array\\|object, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of method Glpi\\\\Inventory\\\\Conf\\:\\:importContentFile\\(\\) expects string, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$data of class Glpi\\\\Inventory\\\\MainAsset\\\\Itemtype constructor expects array\\|null, stdClass given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$rules_id of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:rulepassed\\(\\) expects int, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$replace of function str_replace expects array\\<string\\>\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
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
	'message' => '#^Parameter \\#1 \\$writer of class PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\Ods\\\\Mimetype constructor expects PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\Ods, PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\IWriter given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Ods.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{string, string, string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$time of static method SavedSearch\\:\\:updateExecutionTime\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$meta of static method Search\\:\\:addWhere\\(\\) expects int, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$entity_restrict of static method User\\:\\:getSqlSearchResult\\(\\) expects int, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of class HTMLTableSuperHeader constructor expects HTMLTableMain, \\$this\\(HTMLTableBase\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$father of class HTMLTableSuperHeader constructor expects HTMLTableSuperHeader\\|null, HTMLTableHeader\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$replace of function str_replace expects array\\<string\\>\\|string, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property HTMLTableGroup\\:\\:\\$new_headers is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$row of class HTMLTableCell constructor expects HTMLTableHeader, \\$this\\(HTMLTableRow\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
];
$ignoreErrors[] = [
	'message' => '#^Property HTMLTableSuperHeader\\:\\:\\$headerSets is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableSuperHeader.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with bool will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function str_pad expects string, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Toolbox\\:\\:getTimestampTimeUnits\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:prepareArrayForInput\\(\\) expects array, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of static method IPAddress\\:\\:addValueToAddress\\(\\) expects array\\<int\\>, array\\<int, float\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/IPNetwork.php',
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
	'message' => '#^Variable \\$total might not be defined\\.$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Infocom\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$asset of static method ItemAntivirus\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$asset of static method ItemVirtualMachine\\:\\:showForAsset\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$asset of static method ItemVirtualMachine\\:\\:showForVirtualMachine\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$camera of static method Item_DeviceCamera_ImageResolution\\:\\:showItems\\(\\) expects DeviceCamera, Item_DeviceCamera given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageResolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$rand of static method Html\\:\\:getCheckAllAsCheckbox\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_Environment\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_Line\\:\\:countSimcardLinesForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_Line\\:\\:showLinesForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_OperatingSystem\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_Process\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_RemoteManagement\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_SoftwareVersion\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_SoftwareVersion\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_Comment\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_Item\\:\\:getCountForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_Revision\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Revision.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method Link\\:\\:showAllLinksForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method User\\:\\:getNameForLog\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Log\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAddressList\\(\\) on array\\|ArrayIterator\\|Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method Link\\:\\:showAllLinksForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$onlyone of method DBmysql\\:\\:updateOrInsert\\(\\) expects bool, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Stencil\\:\\:getStencilFromItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipmentModelStencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$super of static method NetworkName\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method NetworkPort\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method NetworkPort\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$rand of static method Html\\:\\:getCheckAllAsCheckbox\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Notepad\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Notepad\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Notification\\:\\:itemMatchFilter\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{list\\<string\\>, list\\<\'"\'\\|\'\\\\\'\'\\>, list\\<numeric\\-string\\>, list\\<\'"\'\\|\'\\\\\'\'\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function implode expects array\\<string\\>, array\\<array\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function array_slice expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$side of static method PDU_Rack\\:\\:getForRackSide\\(\\) expects int, array\\<int, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$timestamp of function date expects int\\|null, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
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
	'message' => '#^Parameter \\#4 \\$checkitem of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function usort contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$callback of function usort expects callable\\(mixed, mixed\\)\\: int, array\\{\'SimplePie\\\\\\\\SimplePie\', \'sort_items\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getSystemSQLCriteria\\(\\) on \\(int\\|string\\)\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on \\(int\\|string\\)\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Reservation\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\*" between string and 3600 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getImpactName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getPriorityName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getStatus\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getUrgencyName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILValidation\\:\\:getStatus\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Ticket\\:\\:getTicketTypeName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Callable passed to call_user_func\\(\\) invoked with 1 parameter, 0 required\\.$#',
	'identifier' => 'arguments.count',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$maxtime of method RuleDictionnaryDropdownCollection\\:\\:replayRulesOnExistingDBForModel\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftwareCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Property RuleImportAsset\\:\\:\\$restrict_entity is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method RuleMatchedLog\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$search of static method SavedSearch_Alert\\:\\:showForSavedSearch\\(\\) expects SavedSearch, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$slaType of static method SlaLevel_Ticket\\:\\:doLevelForTicket\\(\\) expects 0\\|1, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$criteria of method DBmysql\\:\\:request\\(\\) expects array\\|Glpi\\\\DBAL\\\\QueryUnion, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method RuleCollection\\:\\:processAllRules\\(\\) expects array, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$output of method RuleCollection\\:\\:processAllRules\\(\\) expects array, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
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
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILSatisfaction\\:\\:displaySatisfaction\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$checkitem of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects CommonDBTM\\|null, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Static method Ticket\\:\\:getListForItemSearchOptionsCriteria\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'function\' on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: array\\<mixed\\>, object\\?\\: object\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$max_steps of method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:setMaxSteps\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$steps of method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:advance\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function Safe\\\\preg_replace expects array\\<string\\>\\|string, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Transfer\\:\\:addToAlreadyTransfer\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$max_steps of method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:setMaxSteps\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$steps of method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:advance\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Update\\:\\:\\$dbversion is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function str_replace expects array\\<string\\>\\|string, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user of static method AuthLDAP\\:\\:forceOneUserSynchronization\\(\\) expects User, CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function implode expects array\\<string\\>, list\\<array\\<string\\>\\|string\\> given\\.$#',
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
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
