<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of method Glpi\\\\Security\\\\TOTPManager\\:\\:regenerateBackupCodes\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/2fa.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/actorinformation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/actorinformation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'_glpi_tab\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'_itemtype\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'withtemplate\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonGLPI\\:\\:displayStandardTab\\(\\) expects CommonGLPI, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$options of static method CommonGLPI\\:\\:displayStandardTab\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownConnectNetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'toupdate\' might not exist on array\\{name\\: non\\-falsy\\-string, entity\\: array\\<int\\>\\|int, rand\\: int\\<0, max\\>, to_update\\: null, toupdate\\?\\: array\\{value_fieldname\\: \'value\', to_update\\: non\\-falsy\\-string, url\\: non\\-falsy\\-string, moreparams\\: array\\{value\\: \'__VALUE__\', allow_email\\: bool, field\\: non\\-falsy\\-string, typefield\\: \'supplier\', use_notification\\: mixed\\}\\}\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownItilActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownLocation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownMassiveActionField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownMassiveActionField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTextual\\(\\) on IPAddress\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/dropdownShowIPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTextual\\(\\) on IPNetmask\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dropdownShowIPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTextual\\(\\) on array\\|IPAddress\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/dropdownShowIPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\|false\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownConnect.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\|false\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownFindNum.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownMyDevices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownNumber.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\|false\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownUsers.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\(array\\|string\\|false\\) of echo cannot be converted to string\\.$#',
	'identifier' => 'echo.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getDropdownValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getMapPoint.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on bool\\|ImpactItem\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:buildGraph\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:displayListView\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:prepareParams\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ImpactItem\\:\\:findForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/itilfollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$parents_itemtype \\(class\\-string\\<CommonITILObject\\>\\|CommonITILObject\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/itilfollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$parents_itemtype \\(class\\-string\\<CommonITILObject\\>\\|CommonITILObject\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/itilvalidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canCreate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method restore\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canCreate\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canDelete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canPurge\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canView\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kblink.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kblink.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, CommonDBTM\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kblink.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/pin_savedsearches.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$urgency of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/priority.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$impact of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/priority.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$post of static method ReservationItem\\:\\:ajaxDropdown\\(\\) expects array\\{idtable\\: string, name\\: string\\}, array\\<mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/reservable_items.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$event of static method Reservation\\:\\:updateEvent\\(\\) expects array\\{id\\: int, start\\: string, end\\: string\\}, non\\-empty\\-array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/reservations.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$options of static method RuleAction\\:\\:dropdownActions\\(\\) expects array\\{subtype\\: class\\-string\\<Rule\\>, name\\: string, field\\?\\: string, value\\?\\: string, alreadyused\\: bool, display\\?\\: bool\\}, array\\{subtype\\: class\\-string, name\\: \'action_type\', field\\: mixed, value\\: mixed, alreadyused\\: bool\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/ruleaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$sub_type of static method Rule\\:\\:getActionsByType\\(\\) expects class\\-string\\<Rule\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/ruleaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$sub_type of method RuleAction\\:\\:getAlreadyUsedForRuleID\\(\\) expects class\\-string\\<Rule\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/ruleaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/savedsearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/ajax/solution.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$parents_itemtype \\(class\\-string\\<CommonITILObject\\>\\|CommonITILObject\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/solution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/ajax/task.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$parents_itemtype \\(class\\-string\\<CommonITILObject\\>\\|CommonITILObject\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/ajax/task.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonITILObject\\:\\:showSubForm\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method rawSearchOptions\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/treebrowse.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/updateTranslationFields.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method DropdownTranslation\\:\\:dropdownFields\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/updateTranslationFields.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAdditionalField\\(\\) on CommonDropdown\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/updateTranslationValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDropdown\\>\\|CommonDropdown given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/updateTranslationValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/ajax/webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Webhook\\:\\:getApiPath\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/appliance_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/appliance_item_relation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id_peripheral\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset_peripheralasset.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype_peripheral\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset_peripheralasset.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset_peripheralasset.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'system_name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/assetdefinition.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/assetdefinition.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/customfielddefinition.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'calendars_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/calendar_holiday.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/calendar_holiday.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'calendars_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/calendarsegment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/calendarsegment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cartridgeitems_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/cartridgeitem_printermodel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/cartridgeitem_printermodel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'certificates_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/certificate_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/certificate_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:enforceReadonlyFields\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/change.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/change.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'changes_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/change_problem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/change_problem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'changes_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/change_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/change_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method check\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilobject_commonitilobject.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilobject_commonitilobject.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilobject_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilobject_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitiltask.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:displayFullPageForItem\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/config.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/consumableitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/consumableitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'contracts_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/contract_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/contract_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'contracts_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/contractcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/contractcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPath\\(\\) on Glpi\\\\UI\\\\Theme\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/css.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/database.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/database.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/databaseinstance.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/datacenter.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/datacenter.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/dcroom.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/dcroom.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/defaultfilter.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/defaultfilter.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/document.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$mime of static method Toolbox\\:\\:getFileAsResponse\\(\\) expects string\\|null, string\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/document.send.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'documents_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/document_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/document_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'domains_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/domain.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domain.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domain.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$values of method Domain_Item\\:\\:addItem\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domain.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domainrecord.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domainrecord.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'system_name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown/dropdowndefinition.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown/dropdowndefinition.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:createConfigFromUserInput\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/form/access_control.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/group.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/group.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'groups_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/group_user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/group_user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/impactcsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of class Glpi\\\\Csv\\\\ImpactCsvExport constructor expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/impactcsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'clusters_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_cluster.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_cluster.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/item_device.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_device.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'enclosures_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_enclosure.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_enclosure.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'lines_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_line.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_line.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFormURLWithID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/front/item_operatingsystem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_operatingsystem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_operatingsystem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_operatingsystem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getFormURLWithID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/front/item_plug.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_plug.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_plug.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_plug.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projects_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'id\' might not exist on array\\{id\\: mixed\\}\\|array\\{racks_id\\: mixed, orientation\\: mixed, position\\: mixed, _onlypdu\\?\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'racks_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_softwareversion.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$msg of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_softwareversion.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/itemantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/itemantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itemantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itemantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itemantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFormURLWithID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itemvirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projects_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itil_project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itil_project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itilfollowup.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/itilfollowup.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itilfollowup.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itilsolution.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method KnowbaseItem\\:\\:showForm\\(\\) expects int, array\\|float\\|int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'knowbaseitems_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'links_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/front/link_itemtype.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/link_itemtype.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/front/lock.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/lock.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method restore\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/lock.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/front/manuallink.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/front/manuallink.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/manuallink.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/manuallink.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/networkalias.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkalias.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/networkequipment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkequipment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkname.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/networkport.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkport.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notepad.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notepad.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notification.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notification.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notification_notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:displayFullPageForItem\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationajaxsetting.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:displayFullPageForItem\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of static method NotificationTarget\\<CommonGLPI\\>\\:\\:updateTargets\\(\\) expects array\\{itemtype\\: class\\-string\\<CommonDBTM\\>, notifications_id\\?\\: int, _targets\\?\\: array\\<string\\>, _exclusions\\?\\: array\\<string\\>\\}, array\\<mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtarget.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'language\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplatetranslation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplatetranslation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplatetranslation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/oauthclient.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'olas_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevelaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevelcriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getKey\\(\\) on Glpi\\\\UI\\\\Theme\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/palette_preview.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'racks_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/pdu_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/pdu_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/planningcsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$who of class Glpi\\\\Csv\\\\PlanningCsv constructor expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/planningcsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/planningexternalevent.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of method Glpi\\\\Security\\\\TOTPManager\\:\\:disable2FAForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/preference.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$printer of class Glpi\\\\Csv\\\\PrinterLogCsvExport constructor expects Printer, Printer\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/printerlogcsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:enforceReadonlyFields\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/problem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'problems_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/problem_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/problem_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/profile.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/profile.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'users_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/profile_user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/profile_user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projects_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/projectcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projectcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttask.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projecttasks_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttask_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttask_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projecttasks_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttaskteam.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttaskteam.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projects_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/projectteam.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projectteam.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/reminder.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reminder.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/front/report.dynamic.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/report.dynamic.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$by_itemtype of static method Report\\:\\:showNetworkReport\\(\\) expects \'Glpi\\\\\\\\Socket\'\\|\'Location\'\\|\'NetworkEquioment\'\\|null, \'Glpi\\\\\\\\Socket\'\\|\'Location\'\\|\'NetworkEquipment\'\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.networking.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between array\\|int\\|string and \'\\?\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function parse_str expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of function Safe\\\\parse_url expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/reservationitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/reservationitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reservationitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rssfeed.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rssfeed.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rssfeed.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFormURLWithID\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method redirectToList\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method displayFullPageForItem\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method initRules\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.common.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ruleaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rulecriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method checkGlobal\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rulesengine.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isRuleRecursive\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rulesengine.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setEntity\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rulesengine.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method showRulesEnginePreviewCriteriasForm\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rulesengine.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method showRulesEnginePreviewResultsForm\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/rulesengine.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'savedsearches_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/savedsearch_alert.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/savedsearch_alert.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'slas_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevelaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevelcriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/snmpcredential.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/snmpcredential.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/socket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/socket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/socket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'softwares_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/softwarelicense.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/softwarelicense.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'softwares_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/softwareversion.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/softwareversion.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.global.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Stat\\:\\:getItems\\(\\) expects class\\-string\\<CommonGLPI\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Stat\\:\\:showTable\\(\\) expects class\\-string\\<CommonITILObject\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on Stencil\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/stencil.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method check\\(\\) on Stencil\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/front/stencil.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:enforceReadonlyFields\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticket_contract.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'tickets_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/ticketcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticketcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method ValidatorSubstitute\\:\\:updateSubstitutes\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/validatorsubstitute.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\.\\.\\.\\$arg1 of function max expects non\\-empty\\-array, list given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_array\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.20_to_10.0.21/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.20_to_10.0.21/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_11.0.2_to_11.0.3/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_11.0.2_to_11.0.3/tokens.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Non_unique\' might not exist on array\\<string\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:fetchAssoc\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.4.x_to_9.5.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.4.x_to_9.5.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0/domains.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_all\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0/native_inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'dolog_method\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method AbstractITILChildTemplate\\:\\:validateContentInput\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/AbstractITILChildTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/AbstractRightsDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/AbstractRightsDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addVolume\\(\\) on object\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:getLinkedItem\\(\\) should return CommonDBTM but returns CommonDBTM\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Static property Agent\\:\\:\\$found_address \\(bool\\) does not accept string\\|true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset numeric\\-string might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Html\\:\\:cleanId\\(\\) expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Alert\\:\\:dropdownIntegerNever\\(\\) should return string\\|void but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Alert\\:\\:dropdownYesNo\\(\\) should return string\\|void but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Appliance\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'appliances_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Appliance_Item\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getIcon\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'dn\' on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'dn\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'host\' on array\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'scheme\' on array\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:connection_ldap\\(\\) should return array\\|false but returns array\\|true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:dropdown\\(\\) should return string\\|void but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:dropdownCasVersion\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$hash of static method Auth\\:\\:checkPassword\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$options of function setcookie expects array\\{expires\\?\\: int, path\\?\\: string, domain\\?\\: string, secure\\?\\: bool, httponly\\?\\: bool, samesite\\?\\: \'Lax\'\\|\'lax\'\\|\'None\'\\|\'none\'\\|\'Strict\'\\|\'strict\'\\}, array\\{expires\\: \\(float\\|int\\), path\\: string, domain\\: string, secure\\: bool, httponly\\: true, samesite\\: string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$password of static method AuthLDAP\\:\\:connectToServer\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_type \\(int\\) does not accept int\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$auth_succeded\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$fields\\.$#',
	'identifier' => 'property.notFound',
	'count' => 17,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$user_found\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$user_ldap_error\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getLdapIdentifierToUse\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:isSyncFieldEnabled\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'cn\' on array\\|bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'dn\' on array\\|bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'dn\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset mixed on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on User\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:dropdownGroupSearchType\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$auth of static method AuthLDAP\\:\\:ldapAuth\\(\\) expects Auth, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, array\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method User\\:\\:manageDeletedUserInLdap\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$elements of static method Dropdown\\:\\:showFromArray\\(\\) expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of function Safe\\\\ldap_get_entries expects LDAP\\\\Result, array\\|LDAP\\\\Result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of function ldap_parse_result expects LDAP\\\\Result, array\\|LDAP\\\\Result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects LDAP\\\\Result, array\\|LDAP\\\\Result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$password of static method AuthLDAP\\:\\:connectToServer\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$auth_succeded\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$authtypes\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$extauth\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$user\\.$#',
	'identifier' => 'property.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$user_present\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:connection_imap\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Blacklist\\:\\:dropdownType\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Blacklist\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlacklistedMailContent\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/BlacklistedMailContent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/BlacklistedMailContent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access an offset on Glpi\\\\DBAL\\\\QueryExpression\\|list\\<string\\>\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Budget\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cable\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cable\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset int on array\\|bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 8,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$TAB of function exportArrayToDB expects \'\'\\|array, array\\|bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_sum expects array, array\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalendarSegment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar_Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cartridge\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CartridgeItem\\:\\:dropdownForPrinter\\(\\) should return bool\\|string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CartridgeItem\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem_PrinterModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Central.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Certificate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'certificates_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of method Certificate_Item\\:\\:getFromDBbyCertificatesAndItem\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Change\\:\\:displayTabContentForItem\\(\\) should return bool but returns false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method CommonITILValidation\\:\\:getTargetCriteriaForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ChangeTask\\:\\:displayPlanningItem\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ChangeTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ChangeTask\\:\\:populatePlanning\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ChangeTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ChangeTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ChangeTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ChangeTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$max of static method CleanSoftwareCron\\:\\:deleteItems\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CleanSoftwareCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cluster\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$history of method CommonDBTM\\:\\:post_updateItem\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$criteria of method DBmysql\\:\\:request\\(\\) expects array\\|Glpi\\\\DBAL\\\\QueryUnion, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeTemplate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getIndexName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getItemField\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getOppositeItemtype\\(\\) should return class\\-string\\<CommonDBTM\\>\\|null but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, array\\{string\\|null, \'getTypeName\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$class of function class_exists expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$criteria of method DBmysql\\:\\:request\\(\\) expects array\\|Glpi\\\\DBAL\\\\QueryUnion, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:getTypeItemsQueryParams_Select\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item1 of method CommonDBRelation\\:\\:getFromDBForItems\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 9,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonDBConnexity\\:\\:getItemsForLog\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$class of function is_a expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$class of function is_subclass_of expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item2 of method CommonDBRelation\\:\\:getFromDBForItems\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 9,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method CommonDBConnexity\\:\\:getItemsForLog\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 22,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of static method CommonDBConnexity\\:\\:canConnexity\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$items_id of static method CommonDBConnexity\\:\\:canConnexity\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$itemtype of method CommonDBConnexity\\:\\:canConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$items_id of method CommonDBConnexity\\:\\:canConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type list\\<list\\<string\\>\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_restore\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset mixed on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset string on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canView\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canViewItem\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getComments\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getValueToSelect\\(\\) should return string\\|false but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 11,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'id\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of function getUserName expects int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of function getUserName expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$force of method CommonDBTM\\:\\:deleteFromDB\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$integer of static method Toolbox\\:\\:cleanInteger\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Lockedfield\\:\\:getLockedValues\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Lockedfield\\:\\:setLastValue\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method Item_Devices\\:\\:cleanItemDeviceDBOnItemDelete\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$fields \\(array\\<string, mixed\\>\\) does not accept array\\<int\\|string, mixed\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(list\\<string\\>\\) does not accept array\\<int\\<0, max\\>, string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(list\\<string\\>\\) does not accept non\\-empty\\-array\\<int\\<0, max\\>, string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDCModelDropdown\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of static method Html\\:\\:image\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDevice\\:\\:getDeviceTypes\\(\\) should return array\\<array\\<class\\-string\\<CommonDevice\\>\\>\\|class\\-string\\<CommonDevice\\>\\> but returns array\\<int\\<0, max\\>\\|string, array\\<int\\<0, max\\>, class\\-string\\<CommonDevice\\>\\|CommonDevice\\>\\|class\\-string\\<CommonDevice\\>\\|CommonDevice\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDevice\\:\\:getItem_DeviceType\\(\\) should return class\\-string\\<Item_Devices\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDevice\\:\\:import\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDeviceModel\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDeviceModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDeviceModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type list\\<list\\<string\\>\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDropdown\\:\\:importExternal\\(\\) should return int but returns bool\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between int\\|string and 1 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between int\\|string\\|false and 1 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\-" between int\\|string and 1 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'path\' on array\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$typeform of static method CommonGLPI\\:\\:getOtherTabs\\(\\) expects class\\-string\\<CommonGLPI\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'type\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$fkname of function getItemForForeignKeyField expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of method CommonITILActor\\:\\:getActors\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method CommonITILObject\\|Project\\:\\:getClosedStatusArray\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method CommonITILObject\\|Project\\:\\:getSolvedStatusArray\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILCost\\:\\:getItilObjectItemType\\(\\) should return class\\-string\\<CommonDBTM\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'parent\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonITILValidation\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'status\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonITILTask\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ITILCategory\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access static property \\$rightname on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticProperty.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canUpdateItem\\(\\) on CommonITILTask\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canViewItem\\(\\) on CommonITILTask\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getCriterias\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDBByCrit\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getRawCompleteName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method post_getFromDB\\(\\) on CommonITILTask\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canView\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getType\\(\\) on CommonITILTask\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getType\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:computeCloseDelayStat\\(\\) should return int but returns float\\|int\\<0, max\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:computeSolveDelayStat\\(\\) should return int but returns float\\|int\\<0, max\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:cronInfo\\(\\) should return array\\{description\\: string, parameter\\?\\: string\\} but returns array\\{\\}\\|array\\{description\\: string\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:dropdownImpact\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:dropdownPriority\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:dropdownUrgency\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getAdditionalMenuOptions\\(\\) should return array\\<string, array\\{title\\: string, page\\: string, icon\\?\\: string, links\\?\\: array\\{search\\: string, add\\?\\: string, template\\?\\: string\\}\\}\\>\\|false but returns non\\-empty\\-array\\<class\\-string\\<ITILTemplate\\>, array\\{links\\: array\\{search\\?\\: string, add\\: string\\}\\}\\|array\\{title\\: string, page\\: string, icon\\: string, links\\: non\\-empty\\-array\\{search\\?\\: string, add\\?\\: string\\}\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getDefaultActor\\(\\) should return int but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTaskClass\\(\\) should return class\\-string\\<CommonITILTask\\>\\|null but returns class\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTemplateClass\\(\\) should return class\\-string\\<ITILTemplate\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getValidationClassName\\(\\) should return class\\-string\\<CommonITILValidation\\>\\|null but returns class\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getValidationStepClassName\\(\\) should return class\\-string\\<ITIL_ValidationStep\\>\\|null but returns class\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:updateActionTime\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'status\' might not exist on array\\{\\}\\|array\\{status\\: non\\-empty\\-array\\<array\\{id\\: \\(int\\|string\\), name\\: string, color_class\\: non\\-falsy\\-string, header_color\\: \'var\\(\\-\\-status\\-color\\)\', drop_only\\: bool\\}\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:handleValidationStepThresholdInput\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:isStatusComputationBlocked\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:isTakeIntoAccountComputationBlocked\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:manageITILObjectLinkInput\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:manageValidationAdd\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of method CommonITILActor\\:\\:getActors\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonITILObject\\:\\:getKanbanPluginFilters\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonITILObject_CommonITILObject\\:\\:countLinksByStatus\\(\\) expects class\\-string\\<CommonITILObject\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$old of static method CommonITILObject\\:\\:isAllowedStatus\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin_key of static method Plugin\\:\\:doOneHook\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin_key of static method Plugin\\:\\:isPluginActive\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$status of static method CommonITILObject\\:\\:isStatusExists\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string1 of function strcmp expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of method CommonITILObject\\:\\:getTemplateFieldName\\(\\) expects int\\|null, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$urgency of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of method CommonITILObject\\:\\:isUserValidationRequested\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$impact of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of closure expects int, float\\|int\\|string\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDBTM\\:\\:showForm\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string2 of function strcmp expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 11,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(list\\<string\\>\\) does not accept array\\<int\\<0, max\\>, string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 14,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonITILObject_CommonITILObject\\:\\:getLinkedTo\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonITILObject_CommonITILObject\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "%%" between int\\|string and 86400 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/" between int\\|string and 3600 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/" between int\\|string and 86400 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function Safe\\\\strtotime expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function Safe\\\\strtotime expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$delay of method Calendar\\:\\:computeEndDate\\(\\) expects int, int\\<min, 86399\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$timestamp of function date expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$specifictime of static method Html\\:\\:computeGenericDateTimeSearch\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_job\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$DTSTAMP on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$LAST\\-MODIFIED on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$RRULE on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$SUMMARY on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$URL on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ChangeTask\\|ProblemTask\\|TicketTask\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:genericPopulateNotPlanned\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getGroupItemsAsVCalendars\\(\\) should return array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\> but returns array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\>\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getItilObjectItemType\\(\\) should return class\\-string\\<CommonITILObject\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getUserItemsAsVCalendars\\(\\) should return array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\> but returns array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\>\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Sabre\\\\VObject\\\\Component\\:\\:remove\\(\\) expects Sabre\\\\VObject\\\\Component\\|Sabre\\\\VObject\\\\Property\\|string, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vcomponent of method CommonITILTask\\:\\:getCommonInputFromVcomponent\\(\\) expects Sabre\\\\VObject\\\\Component, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method PlanningRecall\\:\\:getFromDBForItemAndUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAchievements\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDBByCrit\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getStatus\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on class\\-string\\<ITIL_ValidationStep\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getValidationStatusForITIL\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:getItilObjectItemType\\(\\) should return class\\-string\\<CommonITILObject\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:getValidationStepClassName\\(\\) should return class\\-string\\<ITIL_ValidationStep\\>\\|null but returns class\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, ITIL_ValidationStep\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method CommonITILValidation\\:\\:getTargetCriteriaForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item of static method NotificationEvent\\:\\:raiseEvent\\(\\) expects CommonGLPI, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of static method CommonITILObject\\:\\:getTimelinePosition\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item of static method NotificationEvent\\:\\:raiseEvent\\(\\) expects CommonGLPI, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidationCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset string on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonImplicitTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property CommonITILObject\\|CommonITILRecurrent\\:\\:\\$userlinkclass\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method CommonITILObject\\|CommonITILRecurrent\\:\\:getClosedStatusArray\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant READALL on string\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access static property \\$rightname on string\\|null\\.$#',
	'identifier' => 'staticProperty.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canUpdateItem\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeLocated\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method canView\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getAllTypesForHelpdesk\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getCommonCriteria\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getListForItemRestrict\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonItilObject_Item\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:delete\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_subclass_of expects object\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of function getItemForTable expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$class of function is_a expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$entity_restrict of static method CommonItilObject_Item\\:\\:getLinkedItemsToComputers\\(\\) expects int, array\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:show\\(\\) expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonType\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonType.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-empty\\-array\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string and \'/pics/icones\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'endpoint\' on array\\{api_version\\: \'1\', version\\: string, description\\?\\: string, endpoint\\: string\\}\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'endpoint\' on array\\{api_version\\: string, version\\: \'2\\.2\\.0\', description\\?\\: string, endpoint\\: string\\}\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 3,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbdefault on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbhost on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbpassword on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbuser on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, non\\-falsy\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 10,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Consumable\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'date_out\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'id\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ConsumableItem\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contact\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method Contact\\:\\:managePictures\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Contact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Contact_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'contracts_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getIndexName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract_Item\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|CommonDBTM\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 3,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'contracts_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method CommonDBConnexity\\:\\:getConnexityItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "&" between int\\|string and 2 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between int and 604800\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:register\\(\\) should return bool but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CronTask\\:\\:\\$startlog \\(int\\) does not accept int\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTaskLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbhost on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'line\' might not exist on array\\<string\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'value\' might not exist on array\\<string\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, bool\\|mysqli_result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:result\\(\\) expects mysqli_result, bool\\|mysqli_result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:result\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$start of function mb_substr expects int, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function mb_substr expects int\\|null, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'version\\(\\)\' on array\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_array\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_row\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:affectedRows\\(\\) should return int but returns int\\<\\-1, max\\>\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:fetchArray\\(\\) should return array\\<string\\>\\|null but returns array\\|object\\|false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:fetchAssoc\\(\\) should return array\\<string\\>\\|null but returns array\\|object\\|false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:fetchObject\\(\\) should return object\\|null but returns array\\|object\\|false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:numrows\\(\\) should return int but returns int\\<0, max\\>\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'alias\' might not exist on array\\{0\\?\\: string, name\\?\\: non\\-falsy\\-string, 1\\?\\: non\\-falsy\\-string, alias\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\{0\\?\\: string, name\\?\\: non\\-falsy\\-string, 1\\?\\: non\\-falsy\\-string, alias\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'table\' might not exist on array\\{0\\?\\: string, 1\\?\\: non\\-falsy\\-string, 2\\?\\: string, 3\\?\\: string, 4\\?\\: non\\-empty\\-string, table\\?\\: non\\-empty\\-string, 5\\?\\: non\\-empty\\-string, 6\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:fetchAssoc\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:fetchRow\\(\\) expects mysqli_result, bool\\|mysqli_result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$length of function Safe\\\\fread expects int\\<1, max\\>, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method dataSeek\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetchAssoc\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_fields\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method freeResult\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method numrows\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:getSql\\(\\) should return string but returns array\\<string\\>\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of static method DBmysql\\:\\:quoteName\\(\\) expects Glpi\\\\DBAL\\\\QueryExpression\\|string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method DBmysql\\:\\:doQuery\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function Safe\\\\preg_replace expects array\\<string\\>\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DatabaseInstance\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method Datacenter\\:\\:managePictures\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Datacenter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset non\\-falsy\\-string on iterable\\<string, mixed\\>\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'COUNT\' to array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'ORDER\' to array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:formatUserName\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getItemTypeForTable\\(\\) should return class\\-string\\<CommonDBTM\\>\\|null but returns class\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method DbUtils\\:\\:getItemForItemtype\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method DbUtils\\:\\:getTableForItemType\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Session\\:\\:haveTranslations\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:fetchAssoc\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of static method Toolbox\\:\\:strlen\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of static method Toolbox\\:\\:substr\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of method DbUtils\\:\\:getEntitiesRestrictCriteria\\(\\) expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of method DbUtils\\:\\:getItemForTable\\(\\) expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\.\\.\\.\\$arrays of function array_merge expects array, array\\<mixed\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method DbUtils\\:\\:getAncestorsOf\\(\\) expects array\\<int\\>\\|int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$arrays of function array_merge expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$offset of function substr_replace expects array\\|int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DefaultFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceBattery.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceBattery.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceCamera.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceCamera.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceFirmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceFirmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceFirmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGeneric.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGeneric.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGeneric.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMotherboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceNetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceNetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/DeviceNetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DevicePowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DevicePowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DevicePowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceProcessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceSensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceSoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceSoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceSoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DisplayPreference\\:\\:getTabNameForItem\\(\\) should return array\\<string\\>\\|string but returns array\\<int, string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method DisplayPreference\\:\\:showFormHelpdesk\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getDefaultToView\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method DisplayPreference\\:\\:showForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$user_id of static method DisplayPreference\\:\\:getForTypeUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_job\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'dirname\' might not exist on array\\{dirname\\?\\: string, basename\\: string, extension\\?\\: string, filename\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'extension\' might not exist on array\\{dirname\\?\\: string, basename\\: string, extension\\?\\: string, filename\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Document\\:\\:canViewFileFromItem\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Document\\:\\:getTreeCategoryList\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getDatas\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslatedValue\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DocumentType\\:\\:getSpecificValueToSelect\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DocumentType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'items_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method CommonDBRelation\\:\\:getTypeItems\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Domain\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'domainrelations_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'domains_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$criteria of method CommonDBTM\\:\\:getFromDBByCrit\\(\\) expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DomainRecord.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_column expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'count\' on array\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'results\' on array\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset class\\-string\\<CommonDevice\\> to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeTemplate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:showItemType\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function asort expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function asort expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Session\\:\\:haveTranslations\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$elements of static method Dropdown\\:\\:showFromArray\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DropdownTranslation\\:\\:dropdownFields\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslatedValue\\(\\) expects string, class\\-string\\<CommonTreeDropdown\\>\\|CommonTreeDropdown given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslationID\\(\\) expects string, class\\-string\\<CommonTreeDropdown\\>\\|CommonTreeDropdown given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Enclosure\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$fields\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getLinkURL\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'entities_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getRuleClass\\(\\) on RuleCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:getSpecificValueToSelect\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$replace of function str_replace expects array\\<string\\>\\|string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(list\\<string\\>\\) does not accept array\\<int\\<0, max\\>, string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method FQDN\\:\\:prepareInputForAdd\\(\\) should return array\\<string, mixed\\>\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDN.php',
];
$ignoreErrors[] = [
	'message' => '#^Method FQDN\\:\\:prepareInputForUpdate\\(\\) should return array\\<string, mixed\\>\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDN.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method FQDN\\:\\:prepareInput\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/FQDN.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function reset expects array\\|object, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDNLabel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBChild\\:\\:prepareInputForAdd\\(\\) expects array\\<string, mixed\\>, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDNLabel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBChild\\:\\:prepareInputForUpdate\\(\\) expects array\\<string, mixed\\>, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDNLabel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDNLabel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FQDNLabel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method FieldUnicity\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSearchOptionByField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValueToDisplay\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Fieldblacklist\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Fieldblacklist\\:\\:getSpecificValueToSelect\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:migrateConfigsInDb\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:migrateFieldsInDb\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of method GLPIKey\\:\\:encrypt\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getBodyAsString\\(\\) on Symfony\\\\Component\\\\Mime\\\\Header\\\\HeaderInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getDebug\\(\\) on Symfony\\\\Component\\\\Mailer\\\\SentMessage\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function rawurlencode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$name of class Symfony\\\\Component\\\\Mime\\\\Address constructor expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getRegistrationKey\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$display\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$filesize\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$prefix\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'disabled\' on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'message\' on array\\<string, mixed\\>\\|DOMDocument\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset string on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$documentElement on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createAttribute\\(\\) on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createCDATASection\\(\\) on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createElement\\(\\) on array\\<string, mixed\\>\\|DOMDocument\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method saveXML\\(\\) on array\\<string, mixed\\>\\|DOMDocument\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getResponse\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$parent of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) expects DOMElement, DOMElement\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function trim expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Altcha\\\\AltchaManager\\:\\:computeHmacKey\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Altcha/AltchaManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Anonymous function should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\.\\=" between array\\|string and non\\-falsy\\-string results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'endpoint\' on array\\{api_version\\: string, version\\: string, description\\?\\: string, endpoint\\: string\\}\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'_keys_names\' to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEmpty\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isDynamic\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isTemplate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method mapCurrentToDeprecatedFields\\(\\) on Glpi\\\\Api\\\\Deprecated\\\\DeprecatedInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method mapCurrentToDeprecatedHateoas\\(\\) on Glpi\\\\Api\\\\Deprecated\\\\DeprecatedInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method mapCurrentToDeprecatedSearchOptions\\(\\) on Glpi\\\\Api\\\\Deprecated\\\\DeprecatedInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method mapDeprecatedToCurrentCriteria\\(\\) on Glpi\\\\Api\\\\Deprecated\\\\DeprecatedInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method mapDeprecatedToCurrentFields\\(\\) on Glpi\\\\Api\\\\Deprecated\\\\DeprecatedInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method useDeletedToLockIfDynamic\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method unsetUndisclosedFields\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getItem\\(\\) should return array but returns array\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$data of method Glpi\\\\Api\\\\API\\:\\:getFriendlyNames\\(\\) expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_starts_with expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Glpi\\\\Api\\\\API\\:\\:getMassiveActionsForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Notepad\\:\\:getAllForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getSearchOptionUniqID\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDefaultJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDefaultWhereCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getDatas\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of function array_key_exists expects int\\|string, int\\<0, max\\>\\|string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects array\\|string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ref_table of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDefaultJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Api\\\\API\\:\\:\\$apiclients_id \\(int\\) does not accept int\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Api\\\\API\\:\\:\\$ipnum \\(string\\) does not accept int\\|string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:getItemtype\\(\\) should return class\\-string\\<CommonDBTM\\>\\|false but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'totalcount\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:can\\(\\) expects int, int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:applyMassiveAction\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:createItems\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getItem\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getItems\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getMassiveActionParameters\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getMassiveActions\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:listSearchOptions\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:searchItems\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:updateItems\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function trim expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of method Glpi\\\\Api\\\\API\\:\\:getItem\\(\\) expects int, int\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\ComputerAntivirus\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\ComputerAntivirus\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\ComputerVirtualMachine\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\ComputerVirtualMachine\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/ComputerVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_Item\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_Item\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_SoftwareLicense\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_SoftwareLicense\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_SoftwareVersion\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Computer_SoftwareVersion\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Computer_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Netpoint.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Pdu_Plug\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Pdu_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\Pdu_Plug\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Pdu_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/Pdu_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\TicketFollowup\\:\\:mapCurrentToDeprecatedFields\\(\\) should return array but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/TicketFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\Deprecated\\\\TicketFollowup\\:\\:mapDeprecatedToCurrentFields\\(\\) should return object but returns array\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/TicketFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/Deprecated/TicketFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getErrorResponseBody\\(\\) should return array\\{status\\: string, title\\: string, detail\\: string\\|null, additional_messages\\?\\: array\\<array\\{priority\\: string, message\\: string\\}\\>\\} but returns array\\{status\\: \'ERROR\'\\|\'ERROR_ALREADY_EXISTS\'\\|\'ERROR_BAD_ARRAY\'\\|\'ERROR_INVALID…\'\\|\'ERROR_ITEM_NOT_FOUND\'\\|\'ERROR_METHOD_NOT…\'\\|\'ERROR_RIGHT_MISSING\'\\|\'ERROR_SESSION_TOKEN…\', title\\: string, detail\\: array\\|string\\|null, additional_messages\\?\\: non\\-empty\\-array\\<array\\{priority\\: string, message\\: string\\}\\>\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AbstractController.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\.\\=" between 0\\|0\\.0\\|array\\{\\}\\|string\\|false and non\\-falsy\\-string results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getEmailDataForUser\\(\\) should return array\\<array\\{id\\: int, email\\: string, is_default\\: int, _links\\: array\\{self\\: array\\{href\\: non\\-empty\\-string\\}\\}\\}\\> but returns list\\<array\\{id\\: int, email\\: string, is_default\\: int, _links\\: array\\{self\\: array\\{href\\: string\\}\\}\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getIDForOtherUniqueFieldBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 11,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getUsedOrManagedItems\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$headers of class Glpi\\\\Http\\\\Response constructor expects array\\<array\\<string\\>\\|string\\>, array\\<string, list\\<string\\|null\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$body of class Glpi\\\\Http\\\\Response constructor expects Psr\\\\Http\\\\Message\\\\StreamInterface\\|resource\\|string\\|null, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$itemtypes of static method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getUnionSchemaForItemtypes\\(\\) expects non\\-empty\\-array\\<string, class\\-string\\<CommonGLPI\\>\\>, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 17,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 17,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 16,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 16,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 17,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schemas of static method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getUnionSchema\\(\\) expects non\\-empty\\-array\\<string, array\\{x\\-itemtype\\: string, properties\\: mixed\\}\\>, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'properties\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'x\\-itemtype\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$identifier of method Glpi\\\\OAuth\\\\User\\:\\:setIdentifier\\(\\) expects non\\-empty\\-string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CustomAssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CustomAssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CustomAssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CustomAssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CustomAssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getSubitemType\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getKnownSchema\\(\\) expects string, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getInputParamsBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/KnowbaseController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$class of static method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getDropdownTypeSchema\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$headers of class Glpi\\\\Http\\\\Response constructor expects array\\<array\\<string\\>\\|string\\>, array\\<string, list\\<string\\|null\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$body of class Glpi\\\\Http\\\\Response constructor expects Psr\\\\Http\\\\Message\\\\StreamInterface\\|resource\\|string\\|null, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/NotepadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/NotepadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/NotepadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/NotepadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/NotepadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset mixed on array\\|void\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset mixed to array\\|void\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|void given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_sum expects array, array\\|void given\\.$#',
	'identifier' => 'argument.type',
	'count' => 24,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_values expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 10,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Stat\\:\\:getITILStatFields\\(\\) expects class\\-string\\<CommonITILObject\\>, \\(int\\|string\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function parse_str expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$criterion of static method RuleCriteria\\:\\:getConditions\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$haystack of function in_array expects array, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/SetupController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/SetupController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/SetupController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/SetupController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/SetupController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:createBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ToolController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:deleteBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ToolController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getOneBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ToolController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:searchBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ToolController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of static method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:updateBySchema\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ToolController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getProperties\\(\\) should return array\\<string, array\\{type\\: string, format\\?\\: string, properties\\?\\: array, items\\?\\: array\\}\\> but returns array\\<string, array\\<string, mixed\\>\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'deprecated\' might not exist on array\\{introduced\\: string, deprecated\\?\\: string, removed\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'removed\' might not exist on array\\{introduced\\: string, deprecated\\?\\: string, removed\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schemas of static method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getUnionSchema\\(\\) expects non\\-empty\\-array\\<string, array\\{x\\-itemtype\\: string, properties\\: mixed\\}\\>, array\\<string, non\\-empty\\-array\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of static method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getDefaultFormatForType\\(\\) expects \'array\'\\|\'boolean\'\\|\'integer\'\\|\'number\'\\|\'object\'\\|\'string\', string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function substr expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Api\\\\HL\\\\GraphQL\\:\\:hideOrRemoveProperty\\(\\) expects class\\-string\\<CommonDBTM\\>\\|null, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/GraphQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_subclass_of expects object\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/GraphQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method withHeader\\(\\) on Glpi\\\\Http\\\\Response\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/DebugResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\ResultFormatterMiddleware\\:\\:formatXML\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/ResultFormatterMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method withHeader\\(\\) on Glpi\\\\Http\\\\Response\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/SecurityResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#/components…\' and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\|Glpi\\\\Api\\\\HL\\\\Doc\\\\SchemaReference\\:\\:toArray\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getRequestBodySchema\\(\\) should return array\\{content\\: array\\{\'application/json\'\\: array\\{schema\\: array\\{type\\: string, format\\?\\: string, pattern\\?\\: string, properties\\?\\: array\\<string, array\\{type\\: string, format\\?\\: string\\}\\>\\}\\}\\}\\}\\|null but returns array\\{content\\: array\\{\'application/json\'\\: array\\{schema\\: array\\|null\\}\\}\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getRouteParamSchema\\(\\) should return array\\{type\\: string, format\\?\\: string, pattern\\?\\: string, properties\\?\\: array\\<string, array\\{type\\: string, format\\?\\: string\\}\\>\\} but returns array\\<string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getSchema\\(\\) should return array\\{openapi\\: string, info\\: array\\{title\\: string, version\\: string, license\\: array\\{name\\: string, url\\: string\\}\\}, servers\\: array\\<array\\{url\\: string, description\\: string\\}\\>, components\\: array\\{securitySchemes\\: array\\<string, array\\{type\\: string, schema\\?\\: string, name\\?\\: string, in\\?\\: string\\}\\>\\}, paths\\: array\\<string, array\\<string, array\\{tags\\: array\\<string\\>, responses\\: array\\<int\\|string, array\\{description\\: string\\}\\>, description\\?\\: string, parameters\\: array\\<array\\{name\\: string, in\\: string, description\\: string, required\\: bool, schema\\?\\: mixed\\}\\>, requestBody\\?\\: array\\{content\\: array\\{\'application/json\'\\: array\\{schema\\: array\\{type\\: string, format\\?\\: string, pattern\\?\\: string, properties\\?\\: array\\<string, array\\{type\\: string, format\\?\\: string\\}\\>\\}\\}\\}\\}\\}\\>\\>\\} but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'ref\' might not exist on Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\|Glpi\\\\Api\\\\HL\\\\Doc\\\\SchemaReference\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'schema\' might not exist on array\\{name\\: string, in\\: string, description\\: string, required\\: bool, schema\\?\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getComponentReference\\(\\) expects string, array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\>\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getComponentReference\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function Safe\\\\preg_replace expects array\\<string\\>\\|string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'field\' might not exist on array\\{operator\\: mixed, value_expected\\?\\: mixed, property\\: mixed, field\\?\\: string\\|null\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'operator\' might not exist on array\\{operator\\?\\: mixed, value_expected\\?\\: mixed, property\\: mixed, field\\?\\: string\\|null\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'property\' might not exist on array\\{\\}\\|array\\{operator\\?\\: mixed, value_expected\\?\\: mixed, property\\?\\: mixed, field\\?\\: string\\|null\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'value_expected\' might not exist on array\\{operator\\: mixed, value_expected\\?\\: mixed, property\\: mixed, field\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function array_map expects \\(callable\\(string\\|null\\)\\: mixed\\)\\|null, \'stripslashes\' given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:delete\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$path of static method Glpi\\\\Toolbox\\\\ArrayPathAccessor\\:\\:getElementByArrayPath\\(\\) expects string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$path of static method Glpi\\\\Toolbox\\\\ArrayPathAccessor\\:\\:hasElementByArrayPath\\(\\) expects string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$middlewares on Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$path on Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$priority on Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$requirements on Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$tags on Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAttributes\\(\\) on ReflectionClass\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on ReflectionClass\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method newInstance\\(\\) on ReflectionClass\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getCachedRouteHint\\(\\) should return array\\{key\\: string, path\\: string, compiled_path\\: string, methods\\: array\\<string\\>, priority\\: int, security\\: int\\} but returns array\\{key\\: string, path\\: string, compiled_path\\: string\\|null, methods\\: array, priority\\: int, security\\: int\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getCompiledPath\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getMethod\\(\\) should return ReflectionMethod but returns ReflectionMethod\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getRoute\\(\\) should return Glpi\\\\Api\\\\HL\\\\Route but returns Glpi\\\\Api\\\\HL\\\\Route\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getRouteRequirements\\(\\) should return array\\<string, string\\> but returns array\\<string, array\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method withBody\\(\\) on Glpi\\\\Http\\\\Response\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:handleRequest\\(\\) should return Glpi\\\\Http\\\\Response but returns Glpi\\\\Http\\\\Response\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$class of class Glpi\\\\Api\\\\HL\\\\RoutePath constructor expects class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Api\\\\HL\\\\Router\\:\\:\\$current_client \\(array\\{client_id\\: string, user_id\\: string, scopes\\: array\\}\\|null\\) does not accept array\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method hydrate\\(\\) on Glpi\\\\Api\\\\HL\\\\Search\\\\RecordSet\\|int\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getSearchCriteria\\(\\) should return array\\<array\\> but returns array\\<array\\|int\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getSearchResultsBySchema\\(\\) should return array\\{results\\: array, start\\: int, limit\\: int, total\\: int\\} but returns array\\{results\\: list, start\\: 0\\|array, limit\\: array\\|int\\<0, max\\>, total\\: Glpi\\\\Api\\\\HL\\\\Search\\\\RecordSet\\|int\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function substr expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\{ids\\: non\\-empty\\-list\\<string\\>\\}\\|array\\{table\\: string, itemtype\\: class\\-string\\<CommonDBTM\\>\\|null, ids\\: non\\-empty\\-list\\<string\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'table\' might not exist on array\\{ids\\: non\\-empty\\-list\\<string\\>\\}\\|array\\{table\\: string, itemtype\\: class\\-string\\<CommonDBTM\\>\\|null, ids\\: non\\-empty\\-list\\<string\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$alias of class Glpi\\\\DBAL\\\\QueryExpression constructor expects string\\|null, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of method Glpi\\\\Api\\\\HL\\\\Search\\\\RecordSet\\:\\:getHydrationCriteria\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function substr expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function str_replace expects array\\<string\\>\\|string, array\\<string\\>\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$alias of static method Glpi\\\\DBAL\\\\QueryFunction\\:\\:ifnull\\(\\) expects string\\|null, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/RecordSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function substr expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\Environment\\:\\:get\\(\\) should return Glpi\\\\Application\\\\Environment but returns Glpi\\\\Application\\\\Environment\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method get\\(\\) on Psr\\\\SimpleCache\\\\CacheInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method set\\(\\) on Psr\\\\SimpleCache\\\\CacheInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ImportMapGenerator\\:\\:generateVersionParam\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|false supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ResourcesChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function file_exists expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ResourcesChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function filemtime expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ResourcesChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/ResourcesChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$size of static method Toolbox\\:\\:getSize\\(\\) expects int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/DataHelpersExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Html\\:\\:timestampToString\\(\\) expects float\\|int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/DataHelpersExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'/\' and array\\|int\\|string\\|null results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'/front/css\\.php\\?file\\=\' and array\\|int\\|string\\|null results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\.\\=" between non\\-empty\\-array\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string and \'&debug\\=1\'\\|\'debug\\=1\' results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method tableExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$file of static method Html\\:\\:getScssCompilePath\\(\\) expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, array\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_starts_with expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of method Glpi\\\\UI\\\\ThemeManager\\:\\:getTheme\\(\\) expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$version of static method Glpi\\\\Toolbox\\\\FrontEnd\\:\\:getVersionCacheKey\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\I18nExtension\\:\\:getCurrentLocale\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/I18nExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\ItemtypeExtension\\:\\:getItemtypeDropdown\\(\\) should return string\\|null but returns int\\|string\\|false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/ItemtypeExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$nb of static method CommonGLPI\\:\\:getTypeName\\(\\) expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/ItemtypeExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$trait of static method Toolbox\\:\\:hasTrait\\(\\) expects class\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/PhpExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\PluginExtension\\:\\:getPluginsCssFiles\\(\\) should return array\\<int, array\\{path\\: string, options\\: array\\{version\\: string\\}\\}\\> but returns list\\<array\\{path\\: non\\-falsy\\-string, options\\: array\\{version\\: string\\|null\\}\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/PluginExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\PluginExtension\\:\\:getPluginsJsModulesFiles\\(\\) should return array\\<int, array\\{path\\: string, options\\: array\\{version\\: string\\}\\}\\> but returns list\\<array\\{path\\: non\\-falsy\\-string, options\\: array\\{version\\: string\\|null\\}\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/PluginExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\PluginExtension\\:\\:getPluginsJsScriptsFiles\\(\\) should return array\\<int, array\\{path\\: string, options\\: array\\{version\\: string\\}\\}\\> but returns list\\<array\\{path\\: non\\-falsy\\-string, options\\: array\\{version\\: string\\|null\\}\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/PluginExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Search\\\\Output\\\\HTMLSearchOutput\\:\\:showItem\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/SearchExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\SecurityExtension\\:\\:decrypt\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/SecurityExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of method Twig\\\\Loader\\\\FilesystemLoader\\:\\:addPath\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset non\\-falsy\\-string on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'table\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method Glpi\\\\Asset\\\\Asset\\:\\:handleReadonlyFieldUpdate\\(\\) expects array, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetModelClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\AssetModel\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetModelDictionaryClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\RuleDictionaryModel\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetModelDictionaryCollectionClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\RuleDictionaryModelCollection\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetTypeClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\AssetType\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetTypeDictionaryClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\RuleDictionaryType\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetTypeDictionaryCollectionClassName\\(\\) should return class\\-string\\<Glpi\\\\Asset\\\\RuleDictionaryTypeCollection\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of class Glpi\\\\Asset\\\\Capacity constructor expects string, class\\-string\\<Glpi\\\\Asset\\\\Capacity\\\\CapacityInterface\\>\\|Glpi\\\\Asset\\\\Capacity\\\\CapacityInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<Glpi\\\\Asset\\\\Capacity\\\\CapacityInterface\\>\\|Glpi\\\\Asset\\\\Capacity\\\\CapacityInterface\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinitionManager\\:\\:getCustomFieldTypes\\(\\) should return array\\<class\\-string\\<Glpi\\\\Asset\\\\CustomFieldType\\\\TypeInterface\\>\\> but returns array\\<class\\-string\\<Glpi\\\\Asset\\\\CustomFieldType\\\\TypeInterface\\>\\>\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinitionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getNameField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:getTypeItemsQueryParams_Select\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, iterable given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Item_Disk\\:\\:rawSearchOptionsToAdd\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasVolumesCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Rack\\:\\:rawSearchOptionsToAdd\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/IsRackableCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$definition of method Glpi\\\\CustomObject\\\\AbstractDefinitionManager\\<Glpi\\\\Asset\\\\AssetDefinition\\>\\:\\:registerDefinition\\(\\) expects Glpi\\\\Asset\\\\AssetDefinition, Glpi\\\\Asset\\\\AssetDefinition\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between 0\\|0\\.0\\|array\\{\\}\\|string\\|false and \'translations\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between 0\\|0\\.0\\|array\\{\\}\\|string\\|false and string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getKnownContexts\\(\\) should return array\\<string\\> but returns array\\<int, int\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'scheme\' might not exist on array\\{0\\?\\: string, scheme\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$dsn of static method Symfony\\\\Component\\\\Cache\\\\Adapter\\\\RedisAdapter\\:\\:createConnection\\(\\) expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset string on iterable\\<string, mixed\\>\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/SimpleCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_map expects array, iterable\\<string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Cache/SimpleCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$LAST\\-MODIFIED on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$UID on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$name on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:storeVCalendarData\\(\\) should return bool but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'desc\' might not exist on array\\{color\\: mixed\\}\\|array\\{key\\: mixed, uri\\: mixed, principaluri\\: string\\|null, name\\: string, desc\\: string, color\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\{color\\: mixed\\}\\|array\\{key\\: mixed, uri\\: mixed, principaluri\\: string\\|null, name\\: string, desc\\: string, color\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'principaluri\' might not exist on array\\{color\\: mixed\\}\\|array\\{key\\: mixed, uri\\: mixed, principaluri\\: string\\|null, name\\: string, desc\\: string, color\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'uri\' might not exist on array\\{color\\: mixed\\}\\|array\\{key\\: mixed, uri\\: mixed, principaluri\\: string\\|null, name\\: string, desc\\: string, color\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method User\\:\\:getFromDBbyName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getPrincipalByPath\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:canViewGroupObjects\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method User\\:\\:getFromDBbyName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$username of method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:canViewUserObjects\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Plugin\\\\Acl\\:\\:canViewGroupObjects\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method User\\:\\:getFromDBbyName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$username of method Glpi\\\\CalDAV\\\\Plugin\\\\Acl\\:\\:canViewUserObjects\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method User\\:\\:getFromDBbyName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\CalDAV\\:\\:getCalendarHomeForPrincipal\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method User\\:\\:getFromDBbyName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Config\\\\ProxyExclusions\\:\\:\\$exclusions \\(array\\<class\\-string\\<CommonGLPI\\>, Glpi\\\\Config\\\\ProxyExclusion\\>\\) does not accept array\\<string, Glpi\\\\Config\\\\ProxyExclusion\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Config/ProxyExclusions.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$format of static method Symfony\\\\Component\\\\Console\\\\Helper\\\\ProgressBar\\:\\:setFormatDefinition\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$dispatcher of method Symfony\\\\Component\\\\Console\\\\Application\\:\\:setDispatcher\\(\\) expects Symfony\\\\Contracts\\\\EventDispatcher\\\\EventDispatcherInterface, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access an offset on float\\|int\\|list\\<string\\>\\|string\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPhpDir\\(\\) on Plugin\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:getCommands\\(\\) should return array\\<Symfony\\\\Component\\\\Console\\\\Command\\\\Command\\> but returns array\\<Symfony\\\\Component\\\\Console\\\\Command\\\\Command\\>\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0 on array\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_array\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/CheckSchemaIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTzIncompatibleTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Requirement\\\\DbTimezones constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0 on array\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbssl on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbsslca on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbsslcacipher on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbsslcapath on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbsslcert on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbsslkey on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method connect\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_array\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbdefault on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbhost on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbuser on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method disableTableCaching\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'hash\' might not exist on array\\{0\\?\\: string, version\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, hash\\?\\: non\\-empty\\-string, 2\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'version\' might not exist on array\\{0\\?\\: string, version\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, hash\\?\\: non\\-empty\\-string, 2\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$DB of class Update constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Requirement\\\\DatabaseTablesEngine constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:fixOneItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access an offset on float\\|int\\|list\\<mixed\\>\\|string\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'action\' on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method Symfony\\\\Component\\\\Console\\\\Command\\\\Command\\:\\:setName\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginMigrationCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fieldExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method tableExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fieldExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method tableExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbdefault on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DatabasesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DomainsPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getMyIsamTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method listTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\Form\\\\Migration\\\\FormMigration constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/FormCreatorPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\Migration\\\\GenericobjectPluginMigration constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/GenericobjectPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on Symfony\\\\Component\\\\Console\\\\Application\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MigrateAllCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getMyIsamTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method tableExists\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:createDatacenter\\(\\) should return int\\|null but returns int\\|true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$new_id of method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:addElementToMapping\\(\\) expects int, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbdefault on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$use_timezones on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method escape\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTzIncompatibleTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteValue\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Requirement\\\\DbTimezones constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method errno\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method error\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeysContraints\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSignedKeysColumns\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteValue\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$directory of method Plugin\\:\\:getInformationsFromDirectory\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$use_utf8mb4 on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method doQuery\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getMyIsamTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNonUtf8mb4Tables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method listTables\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$db of class Glpi\\\\System\\\\Requirement\\\\DbConfiguration constructor expects DBmysql, DBmysql\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ActivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/DeactivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/UninstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ProcessSoftwareCategoryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method quoteName\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method quoteValue\\(\\) on DBmysql\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/Parameters/UserParameters.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSystemSQLCriteria\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/Parameters/UserParameters.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getById\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/Parameters/UserParameters.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$dashboard_key of class Glpi\\\\Dashboard\\\\Grid constructor expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/CentralController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$where of static method Toolbox\\:\\:computeRedirect\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/CentralController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Config/Helpdesk/CopyParentEntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'designation\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, array\\{CommonDropdown, non\\-falsy\\-string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on Glpi\\\\Form\\\\Condition\\\\QuestionData\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EditorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$uuid of method Glpi\\\\Form\\\\Condition\\\\EditorManager\\:\\:getValueOperatorForValidationDropdownValues\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EditorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$item_uuid of class Glpi\\\\Form\\\\Condition\\\\ConditionData constructor expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EditorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$value_operator of class Glpi\\\\Form\\\\Condition\\\\ConditionData constructor expects string\\|null, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EditorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, float\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/DelegationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Glpi\\\\Form\\\\Destination\\\\FormDestination\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/AddDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:delete\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/PurgeDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/UpdateDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Import\\\\Step2PreviewController\\:\\:getJsonFormFromRequest\\(\\) should return string but returns bool\\|float\\|int\\|string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step2PreviewController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset bool\\|float\\|int\\|string\\|null might not exist on array\\<int, array\\<Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\>\\>\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step3ResolveIssuesController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$json of method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:listIssues\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step3ResolveIssuesController.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type bool\\|float\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step3ResolveIssuesController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importFormsFromJson\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step4ExecuteController.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Controller\\\\Form\\\\RendererController\\:\\:\\$interface \\(string\\) does not accept string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/RendererController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method Glpi\\\\Form\\\\AnswersHandler\\\\AnswersHandler\\:\\:saveAnswers\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/SubmitAnswerController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Form\\\\Translation\\\\AddNewFormTranslationController\\:\\:getRedirectUrl\\(\\) expects string\\|null, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/AddNewFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Translation\\\\AbstractTranslationController\\:\\:createInitialTranslation\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/AddNewFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Translation\\\\AbstractTranslationController\\:\\:validateLanguage\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/AddNewFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:can\\(\\) expects int, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method CommonDBTM\\:\\:isNewID\\(\\) expects int, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:delete\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:restore\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of static method Glpi\\\\Event\\:\\:log\\(\\) expects int\\|string, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$action_success of static method CommonDBTM\\:\\:getPostFormAction\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getCurrentEntityId\\(\\) on Glpi\\\\Session\\\\SessionInfo\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getUserId\\(\\) on Glpi\\\\Session\\\\SessionInfo\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$session_info of method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:getVisibleTilesForSession\\(\\) expects Glpi\\\\Session\\\\SessionInfo, Glpi\\\\Session\\\\SessionInfo\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Helpdesk\\\\Translation\\\\AddNewHelpdeskTranslationController\\:\\:getRedirectUrl\\(\\) expects string\\|null, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/Translation/AddNewHelpdeskTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Translation\\\\AbstractTranslationController\\:\\:createInitialTranslation\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/Translation/AddNewHelpdeskTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of method Glpi\\\\Controller\\\\Translation\\\\AbstractTranslationController\\:\\:validateLanguage\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Helpdesk/Translation/AddNewHelpdeskTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$files of method Glpi\\\\Inventory\\\\Conf\\:\\:importFiles\\(\\) expects array\\{filename\\: string, filepath\\: string\\}, array\\<string, string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InventoryController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$connect_string of static method AuthMail\\:\\:testAuth\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/AuthMailFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/AuthMailFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$login of static method AuthMail\\:\\:testAuth\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/AuthMailFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$password of static method AuthMail\\:\\:testAuth\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/AuthMailFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:check\\(\\) expects int, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/MailCollectorFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$mailgateID of method MailCollector\\:\\:collect\\(\\) expects int, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/MailCollectorFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/SavedSearchFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Knowbase/AddCommentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$status of class Symfony\\\\Component\\\\HttpFoundation\\\\Response constructor expects int, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$code of method Glpi\\\\Security\\\\TOTPManager\\:\\:verifyBackupCodeForUser\\(\\) expects string, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Security/MFAController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Symfony\\\\Component\\\\HttpFoundation\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Security/MFAController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$secret of method Glpi\\\\Security\\\\TOTPManager\\:\\:verifyCodeForSecret\\(\\) expects string, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Security/MFAController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$algorithm of method Glpi\\\\Security\\\\TOTPManager\\:\\:setSecretForUser\\(\\) expects string\\|null, string\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Security/MFAController.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Controller\\\\ServiceCatalog\\\\IndexController\\:\\:\\$interface \\(string\\) does not accept string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getCurrentEntityId\\(\\) on Glpi\\\\Session\\\\SessionInfo\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/ItemsController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, float\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/ItemsController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$category_id of class Glpi\\\\Form\\\\ServiceCatalog\\\\ItemRequest constructor expects int\\|null, float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/ItemsController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$sort_strategy of class Glpi\\\\Form\\\\ServiceCatalog\\\\ItemRequest constructor expects Glpi\\\\Form\\\\ServiceCatalog\\\\SortStrategy\\\\SortStrategyEnum, Glpi\\\\Form\\\\ServiceCatalog\\\\SortStrategy\\\\SortStrategyEnum\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/ItemsController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of class Glpi\\\\Http\\\\RedirectResponse constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Session/ChangeEntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Session/ChangeProfileController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Translation/AbstractTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:update\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Translation/AbstractTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ImpactContext\\:\\:findForImpactItem\\(\\) expects ImpactItem, bool\\|ImpactItem given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/ImpactCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/PlanningCsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/PlanningCsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$end_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime, Safe\\\\DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$end_date of static method PrinterLog\\:\\:getMetrics\\(\\) expects Safe\\\\DateTime, Safe\\\\DateTime\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/PrinterLogCsvExportComparison.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$categories on Gettext\\\\Languages\\\\Language\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$formula on Gettext\\\\Languages\\\\Language\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getCustomObjectClassName\\(\\) should return class\\-string\\<ConcreteClass of CommonDBTM\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:prepareInputForAdd\\(\\) should return array\\<string, mixed\\>\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:prepareInputForUpdate\\(\\) should return array\\<string, mixed\\>\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Gettext\\\\Languages\\\\Language\\:\\:getById\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function Safe\\\\json_decode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$raw_rights of static method Glpi\\\\Dashboard\\\\Dashboard\\:\\:convertRights\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_map expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Dashboard\\\\Dashboard\\:\\:\\$key \\(string\\) does not accept int\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getIcon\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getSearchURL\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Filters\\\\AbstractFilter\\:\\:getSearchOptionID\\(\\) should return int but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getOptions\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$class of static method Toolbox\\:\\:hasTrait\\(\\) expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractGroupFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canViewCurrent\\(\\) on Glpi\\\\Dashboard\\\\Dashboard\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTitle\\(\\) on Glpi\\\\Dashboard\\\\Dashboard\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:dropdownDashboard\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:getDashboard\\(\\) should return Glpi\\\\Dashboard\\\\Dashboard but returns Glpi\\\\Dashboard\\\\Dashboard\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func_array expects callable\\(\\)\\: mixed, non\\-falsy\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSystemSQLCriteria\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeTemplate\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method rawSearchOptions\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getFormURLWithID\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getIcon\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getIndexName\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getSearchURL\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getSystemSQLCriteria\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_values expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Dashboard\\\\Provider\\:\\:nbItemByFk\\(\\) expects CommonDBTM\\|null, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method CommonITILValidation\\:\\:getTargetCriteriaForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$fk_item of static method Glpi\\\\Dashboard\\\\Provider\\:\\:nbItemByFk\\(\\) expects CommonDBTM\\|null, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$condition of static method Glpi\\\\DBAL\\\\QueryFunction\\:\\:if\\(\\) expects array\\|Glpi\\\\DBAL\\\\QueryExpression\\|string, Glpi\\\\DBAL\\\\QueryExpression\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function array_splice expects int\\|null, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Debug\\\\Profile\\:\\:getDebugInfo\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Debug\\\\ProfilerSection\\:\\:getEnd\\(\\) should return int but returns int\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/ProfilerSection.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Debug\\\\ProfilerSection\\:\\:\\$pauses \\(array\\<array\\{start\\: int, end\\?\\: int\\}\\>\\) does not accept non\\-empty\\-array\\<array\\{start\\: float\\}\\|array\\{start\\: int, end\\?\\: int\\}\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/ProfilerSection.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Debug\\\\ProfilerSection\\:\\:\\$pauses \\(array\\<array\\{start\\: int, end\\?\\: int\\}\\>\\) does not accept non\\-empty\\-array\\<array\\{start\\: int, end\\?\\: float\\|int\\}\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/ProfilerSection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method get\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getParameter\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method has\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method hasParameter\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method initialized\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method set\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setParameter\\(\\) on Symfony\\\\Component\\\\DependencyInjection\\\\ContainerInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$path of class Symfony\\\\Component\\\\DependencyInjection\\\\Loader\\\\Configurator\\\\ContainerConfigurator constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$file of class Symfony\\\\Component\\\\DependencyInjection\\\\Loader\\\\Configurator\\\\ContainerConfigurator constructor expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method writeln\\(\\) on Symfony\\\\Component\\\\Console\\\\Output\\\\OutputInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Error/ErrorDisplayHandler/ConsoleErrorDisplayHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$level of static method Monolog\\\\Logger\\:\\:toMonologLevel\\(\\) expects 100\\|200\\|250\\|300\\|400\\|500\\|550\\|600\\|\'ALERT\'\\|\'alert\'\\|\'CRITICAL\'\\|\'critical\'\\|\'DEBUG\'\\|\'debug\'\\|\'EMERGENCY\'\\|\'emergency\'\\|\'ERROR\'\\|\'error\'\\|\'INFO\'\\|\'info\'\\|\'NOTICE\'\\|\'notice\'\\|\'WARNING\'\\|\'warning\'\\|Monolog\\\\Level, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Error/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Static property Glpi\\\\Error\\\\ErrorHandler\\:\\:\\$buffered_messages \\(list\\<array\\{error_label\\: string, message\\: string, log_level\\: string\\}\\>\\) does not accept array\\<int\\<0, max\\>, array\\{error_label\\: string, message\\: string, log_level\\: string\\}\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Error/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Event\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'service\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'type\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, CommonDBTM\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Features\\\\CloneMapper\\:\\:\\$mapped_ids \\(array\\<class\\-string\\<CommonDBTM\\>, array\\<int, int\\>\\>\\) does not accept array\\<string, array\\<int, int\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Features/CloneMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Features\\\\CloneMapper\\:\\:\\$mapped_ids \\(array\\<class\\-string\\<CommonDBTM\\>, array\\<int, int\\>\\>\\) does not accept array\\<string, array\\<int\\|string, int\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Features/CloneMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$session_info of method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\AllowList\\:\\:isUserAllowedByGroup\\(\\) expects Glpi\\\\Session\\\\SessionInfo, Glpi\\\\Session\\\\SessionInfo\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/AllowList.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$session_info of method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\AllowList\\:\\:isUserAllowedByProfile\\(\\) expects Glpi\\\\Session\\\\SessionInfo, Glpi\\\\Session\\\\SessionInfo\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/AllowList.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$session_info of method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\AllowList\\:\\:isUserDirectlyAllowed\\(\\) expects Glpi\\\\Session\\\\SessionInfo, Glpi\\\\Session\\\\SessionInfo\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/AllowList.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isAllowedForUnauthenticatedAccess\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/DirectAccess.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'forms_forms_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'strategy\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method prepareEndUserAnswer\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AnswersHandler/AnswersHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$message of method Glpi\\\\Form\\\\ValidationResult\\:\\:addError\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AnswersHandler/AnswersHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|int supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Clone/FormCloneHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \\(int\\|string\\) on non\\-empty\\-array\\|int\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Clone/FormCloneHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method Glpi\\\\Form\\\\Clone\\\\FormCloneHelper\\:\\:getMappedDestinationId\\(\\) expects int, array\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Clone/FormCloneHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method Glpi\\\\Form\\\\Clone\\\\FormCloneHelper\\:\\:getMappedQuestionId\\(\\) expects int, array\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Clone/FormCloneHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Glpi\\\\Form\\\\Section\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$history of method Glpi\\\\Form\\\\Comment\\:\\:logUpdateInParentForm\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getExtraDataConfig\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionData.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getQuestionType\\(\\) on Glpi\\\\Form\\\\Question\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\SingleChoiceFromValuesConditionHandler\\:\\:convertConditionValue\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionHandler/SingleChoiceFromValuesConditionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\UserDevicesConditionHandler\\:\\:getSupportedDeviceTypes\\(\\) should return array\\<class\\-string\\<CommonDBTM\\>\\> but returns array\\<int\\<0, max\\>, class\\-string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionHandler/UserDevicesConditionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on Glpi\\\\Form\\\\Condition\\\\CommentData\\|Glpi\\\\Form\\\\Condition\\\\QuestionData\\|Glpi\\\\Form\\\\Condition\\\\SectionData\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/EditorManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getConditionHandlers\\(\\) on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Condition\\\\CommentData\\|Glpi\\\\Form\\\\Condition\\\\QuestionData\\|Glpi\\\\Form\\\\Condition\\\\SectionData\\|Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/EditorManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\EditorManager\\:\\:getHandlerForCondition\\(\\) should return Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\ConditionHandlerInterface but returns Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\ConditionHandlerInterface\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/EditorManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$value on Glpi\\\\Form\\\\Condition\\\\ValueOperator\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getConditionHandlers\\(\\) on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getExtraDataConfig\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Glpi\\\\Form\\\\Question\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getQuestionType\\(\\) on Glpi\\\\Form\\\\Question\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getUUID\\(\\) on Glpi\\\\Form\\\\Comment\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getUUID\\(\\) on Glpi\\\\Form\\\\Question\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getUUID\\(\\) on Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isAllowedForUnauthenticatedAccess\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$operator of method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\ConditionHandlerInterface\\:\\:applyValueOperator\\(\\) expects Glpi\\\\Form\\\\Condition\\\\ValueOperator, Glpi\\\\Form\\\\Condition\\\\ValueOperator\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$strategies of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssigneeFieldConfig constructor expects array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\>, array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssigneeFieldConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\:\\:fromItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$strategies of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsFieldConfig constructor expects array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsFieldStrategy\\>, array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsFieldStrategy\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsFieldConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\EntityFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityFieldStrategy.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\:\\:fromItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, \\(int\\|string\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\|string\\|true\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorFieldStrategy.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorFieldStrategy.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getITILCategory\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILCategoryFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILCategoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getITILFollowupTemplatesIDs\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILFollowupFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILFollowupField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTaskTemplatesIDs\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILTaskFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILTaskField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsFieldConfig\\:\\:getStrategies\\(\\) should return array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsFieldStrategy\\> but returns array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsFieldStrategy\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsFieldConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLocationID\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LocationFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LocationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$strategies of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ObserverFieldConfig constructor expects array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\>, array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ObserverFieldConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getRequestSource\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestSourceFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestSourceField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getRequestType\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestTypeFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestTypeField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$strategies of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequesterFieldConfig constructor expects array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\>, array\\<Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorFieldStrategy\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequesterFieldConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\SLATTRField\\:\\:getTimeDefinitionFromLegacy\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLATTRField.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\{itemtype\\: class\\-string\\<CommonDBTM\\>, items_id\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLATTRField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getDateSLM\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\SLMFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLMField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSLMID\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\SLMFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLMField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTemplateID\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TemplateFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method computeUrgency\\(\\) on Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\UrgencyFieldStrategy\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/UrgencyField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$index of method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationFieldConfig\\:\\:getStrategyConfigByIndex\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\:\\:fromItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, \\(int\\|string\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$source_items_id of method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:getMappedItemTarget\\(\\) expects int, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$specific_question_ids of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationFieldStrategyConfig constructor expects array\\<int\\>, array\\<float\\|int\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$specific_validationtemplate_ids of class Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationFieldStrategyConfig constructor expects array\\<int\\>, array\\<float\\|int\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method exportDynamicConfig\\(\\) on Glpi\\\\Form\\\\Destination\\\\FormDestinationInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Dropdown/FormActorsDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset int\\|string might not exist on array\\<string, int\\>\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$name of method Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:contextExist\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:\\$values \\(array\\<string, array\\<string, int\\>\\>\\) does not accept array\\<string, array\\<int\\|string, int\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$value on Glpi\\\\Form\\\\Condition\\\\ValueOperator\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$class of method Glpi\\\\Asset\\\\AssetDefinitionManager\\:\\:isCustomAsset\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$class of method Glpi\\\\Dropdown\\\\DropdownDefinitionManager\\:\\:isCustomDropdown\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function Safe\\\\file_get_contents expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function Safe\\\\md5_file expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\:\\:fromItem\\(\\) expects CommonDBTM, Entity\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of class Glpi\\\\Form\\\\Export\\\\Specification\\\\CustomTypeRequirementSpecification constructor expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of static method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:prepareDynamicImportData\\(\\) expects Glpi\\\\Form\\\\Destination\\\\FormDestinationInterface, Glpi\\\\Form\\\\Destination\\\\FormDestinationInterface\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$id of method Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:addMappedItem\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_found_comments\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_found_questions\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_found_sections\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:prepareInputForUpdate\\(\\) should return array\\<string, mixed\\> but returns array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Glpi\\\\ItemTranslation\\\\ItemTranslation\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/FormTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function array_map expects \\(callable\\(Glpi\\\\ItemTranslation\\\\ItemTranslation\\|false\\)\\: mixed\\)\\|null, Closure\\(Glpi\\\\ItemTranslation\\\\ItemTranslation\\)\\: mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/FormTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslationsForItem\\(\\) expects CommonDBTM, Glpi\\\\Form\\\\Form\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/FormTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to list\\<array\\<string, mixed\\>\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForm\\(\\) on Glpi\\\\Form\\\\Section\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Destination\\\\FormDestination\\|Glpi\\\\Form\\\\Form\\|Glpi\\\\Form\\\\Question\\|Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on Glpi\\\\Form\\\\Form\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Destination\\\\FormDestination\\|Glpi\\\\Form\\\\Form\\|Glpi\\\\Form\\\\Question\\|Glpi\\\\Form\\\\Section\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$destinationClass of method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfITILActorsFields\\(\\) expects class\\-string\\<Glpi\\\\Form\\\\Destination\\\\AbstractCommonITILFormDestination\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:importItem\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$destinationClass of method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfDestination\\(\\) expects class\\-string\\<Glpi\\\\Form\\\\Destination\\\\AbstractCommonITILFormDestination\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$value of method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:addValidationCondition\\(\\) expects string, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Glpi\\\\Form\\\\Section\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method exportDynamicDefaultValue\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method exportDynamicExtraData\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method formatPredefinedValue\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getExtraDataConfig\\(\\) on Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$history of method Glpi\\\\Form\\\\Question\\:\\:logUpdateInParentForm\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:isValidQuestionType\\(\\) expects string, class\\-string\\<Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface\\>\\|Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getById\\(\\) on class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function Safe\\\\json_decode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, Glpi\\\\DBAL\\\\JsonFieldInterface\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between int\\|string\\|false and 1 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeSelectable.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Group\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeAssignee.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Document\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeFile.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'items_id\' on array\\|bool\\|float\\|int\\|string\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeActive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItem\\:\\:getSubTypes\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\<0, max\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Form\\\\Export\\\\Specification\\\\DataRequirementSpecification\\:\\:fromItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of class Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\ItemConditionHandler constructor expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonTreeDropdown\\>\\|CommonTreeDropdown\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDefaultValueConfig\\:\\:getItemsId\\(\\) should return int\\|null but returns int\\|string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDefaultValueConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Group\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeObserver.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeRequestType\\:\\:formatRawAnswer\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeRequestType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Group\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeRequester.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'id\' might not exist on array\\{0\\?\\: string, itemtype\\: class\\-string\\<CommonDBTM\\>, 1\\?\\: non\\-empty\\-string, id\\?\\: numeric\\-string, 2\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\{0\\?\\: string, itemtype\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, id\\?\\: numeric\\-string, 2\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$userID of static method CommonItilObject_Item\\:\\:getMyDevices\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access an offset on Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Question\\|list\\<Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Question\\>\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:getQuestions\\(\\) should return array\\<Glpi\\\\Form\\\\Question\\> but returns array\\<Glpi\\\\Form\\\\Question\\>\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getActiveEntitiesIds\\(\\) on Glpi\\\\Session\\\\SessionInfo\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/Provider/FormProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getChildrenItemRequest\\(\\) on Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogCompositeInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/ServiceCatalogManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Tag\\\\AnswerTagProvider\\:\\:getTagContentForValue\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Tag/AnswerTagProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/FuzzyMatcher/FuzzyMatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_split expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/FuzzyMatcher/FuzzyMatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function Safe\\\\preg_replace expects array\\<string\\>\\|string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/FuzzyMatcher/FuzzyMatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Glpi\\\\Form\\\\Destination\\\\FormDestination\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on Glpi\\\\Form\\\\Destination\\\\FormDestination\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:addTile\\(\\) expects CommonDBTM&Glpi\\\\Helpdesk\\\\Tile\\\\LinkableToTilesInterface, Entity\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Laminas\\\\I18n\\\\Translator\\\\Translator\\:\\:translate\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$section of method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:addQuestion\\(\\) expects Glpi\\\\Form\\\\Section, Glpi\\\\Form\\\\Section\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 16,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function md5 expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$lang of method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:applyTranslation\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method listTranslationsHandlers\\(\\) on Entity\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/HelpdeskTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslationsForItem\\(\\) expects CommonDBTM, Entity\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/HelpdeskTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$entity of method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:getTilesForEntityRecursive\\(\\) expects Entity, Entity\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:getTilesForItem\\(\\) expects CommonDBTM&Glpi\\\\Helpdesk\\\\Tile\\\\LinkableToTilesInterface, Profile\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$antivirus_version\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$date_expiration\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_active\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_uptodate\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$items_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$itemtype\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$signature_version\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Antivirus\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$capacity\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicebatterytypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturing_date\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$voltage\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Battery\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Battery.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicecameramodels_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Camera.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Camera.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Camera.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Camera.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Camera\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Camera.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Cartridge\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicecontrolmodels_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$interfacetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset mixed on object\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Controller\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$databaseinstancetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\DatabaseInstance\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Device.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Device.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$val of method Glpi\\\\Inventory\\\\Asset\\\\Device\\:\\:itemdeviceAdded\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Device.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$interfacetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Drive\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$data of method Glpi\\\\Inventory\\\\Asset\\\\Drive\\:\\:isDrive\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$val\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$value\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Environment\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Firmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicefirmwaretypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Firmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Firmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Firmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Firmware\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Firmware.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/GraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/GraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\GraphicCard\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/GraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$capacity\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$deviceharddrivetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$interfacetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\HardDrive\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/HardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isGlobal\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isNewItem\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:getMainAsset\\(\\) should return Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset but returns Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Lockedfield\\:\\:getLockedNames\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:importExternal\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$extra_data \\(array\\<string, object\\|null\\>\\) does not accept array\\<string, array\\<object\\|string\\>\\|object\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$known_links \\(array\\<string, int\\>\\) does not accept array\\<string, bool\\|int\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$raw_links \\(array\\<string, int\\|string\\>\\) does not accept array\\<string, int\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$busID\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicememorymodels_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicememorytypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$frequence\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$size\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Memory\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$comment\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_recursive\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$monitormodels_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Monitor\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$dhcpserver\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$gateway\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifinternalstatus\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifstatus\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ip\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ipaddress\\.$#',
	'identifier' => 'property.notFound',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$logical_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$mac\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$mac_default\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$netmask\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$speed\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ssid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$subnet\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$virtualdev\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$wwn\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$cnt on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$items_id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$num_rows on mysqli_result\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_object\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkCard\\:\\:addNetworkName\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkCard\\:\\:addNetworkPort\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkCard\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifdescr\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifinbytes\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifnumber\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ifoutbytes\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ipaddress\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$logical_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$portduplex\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$trunk\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$cnt on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$ifdescr on stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$ifnumber on stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$ip on stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$items_id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$mac on stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$model on stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$num_rows on mysqli_result\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_object\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getManagementPorts\\(\\) on object\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getStackId\\(\\) on object\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isStackedSwitch\\(\\) on object\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addNetworkName\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addNetworkPort\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addPortsWiring\\(\\) should return bool but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:getPart\\(\\) should return array\\<int, mixed\\>\\|void but returns array\\<int\\|string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of method RuleMatchedLog\\:\\:cleanOlddata\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$port of method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:prepareConnections\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$netports_id_2 of method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addPortsWiring\\(\\) expects int, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$netports_id_2 of method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addPortsWiring\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:\\$connection_ports \\(array\\<string, array\\<int, int\\>\\>\\) does not accept array\\<string, array\\<int, bool\\|int\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:\\$current_connection \\(stdClass\\) does not accept stdClass\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$operatingsystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\OperatingSystem\\:\\:getId\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$productname\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$power\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\PowerSupply\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/PowerSupply.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$autoupdatesystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$have_usb\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_deleted\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_recursive\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$last_inventory_update\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Printer\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$cmd\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$cpuusage\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$memusage\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$pid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$started\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$tty\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$user\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$virtualmemory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Process\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$frequence\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$frequency\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$frequency_default\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$internalid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$nbcores\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$nbthreads\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Processor\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$items_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$itemtype\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$remoteid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\RemoteManagement\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Sensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$devicesensortypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Sensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Sensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Sensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Sensor\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Sensor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$msin\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Simcard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Simcard\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Simcard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$_system_category\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$arch\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$comment\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$date_install\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_deleted_item\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_recursive\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_template_item\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$operatingsystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$softwarecategories_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$version\\.$#',
	'identifier' => 'property.notFound',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_object\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function mb_strtolower expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$types of method mysqli_stmt\\:\\:bind_param\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:getFullCompareKey\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:getOsForKey\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:getSimpleCompareKey\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:getVersionKey\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$known_links \\(array\\<string, int\\>\\) does not accept array\\<string, bool\\|int\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$comment\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$designation\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\SoundCard\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/SoundCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$autoupdatesystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$computertypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_deleted\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$last_inventory_update\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$locations_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$logical_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ram\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$uuid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$virtualmachinestates_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$virtualmachinesystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$virtualmachinetypes_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$cnt on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$comment on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$cpus on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$items_id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$memories on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$networks on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$num_rows on mysqli_result\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$ram on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$uuid on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$vcpu on array\\<string\\>\\|float\\|stdClass\\|string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_object\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:addNetworkName\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:addNetworkPort\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$agent of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:setAgent\\(\\) expects Agent, Agent\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function property_exists expects object\\|string, array\\<string\\>\\|float\\|stdClass\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vm of method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:getExistingVMAsComputer\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:handlePorts\\(\\) expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$device\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$encryption_algorithm\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$encryption_status\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$encryption_tool\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$encryption_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$filesystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$freesize\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$mountpoint\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$totalsize\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Volume\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$raw_data of method Glpi\\\\Inventory\\\\Asset\\\\Volume\\:\\:isNetworkDrive\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$raw_data of method Glpi\\\\Inventory\\\\Asset\\\\Volume\\:\\:isRemovableDrive\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:defineTabs\\(\\) should return array\\<string, string\\> but returns array\\<string, bool\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleContentType\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 38,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$properties\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$action on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$content on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$deviceid on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$enabled\\-tasks on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$installed\\-tasks on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$itemtype on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$name on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$partial on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$tag on stdClass\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method checkConf\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getItem\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method handle\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isNew\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setAssets\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setExtraData\\(\\) on Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:getMainAsset\\(\\) should return Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset but returns Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$data of class Glpi\\\\Inventory\\\\MainAsset\\\\Itemtype constructor expects stdClass, stdClass\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function Safe\\\\json_decode expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$mainasset of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:setMainAsset\\(\\) expects Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset, Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function property_exists expects object\\|string, stdClass\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\>\\|Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$data \\(array\\<string, object\\>\\) does not accept array\\<string, array\\<object\\>\\|object\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$item \\(CommonDBTM\\) does not accept CommonDBTM\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareAllRulesInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Itemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$comment\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$company\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$domains_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$license_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$licenseid\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$logical_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$owner\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$states_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$users_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0 on int\\|string\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$cnt on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$items_id on stdClass\\|false\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$num_rows on mysqli_result\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_object\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAllActions\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:addNetworkName\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:addNetworkPort\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$agent of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:setAgent\\(\\) expects Agent, Agent\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items of method Transfer\\:\\:moveItems\\(\\) expects array\\<class\\-string\\<CommonDBTM\\>, array\\<int\\>\\>, array\\<string, array\\<int, int\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:importExternal\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:isAccessPoint\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareAllRulesInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareEntitiesRulesInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:rulepassed\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$val of static method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:needToBeUpdatedFromDiscovery\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:\\$hardware \\(stdClass\\) does not accept object\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:\\$ruleentity_data \\(array\\<string, mixed\\>\\) does not accept array\\<int\\|string, mixed\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ap_port\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$description\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$firmware\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$index\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ips\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_ap\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$mac\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$memory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$model\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$networkequipmentmodels_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ram\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$serial\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$stack_number\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:getAccessPoints\\(\\) should return array\\<int, stdClass\\> but returns array\\<object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns list\\<object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$agent of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:setAgent\\(\\) expects Agent, Agent\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$components of method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:getStackComponentName\\(\\) expects array\\<stdClass\\>, array\\|object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:\\$hardware \\(stdClass\\) does not accept object\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$autoupdatesystems_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$bw_copies\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$bw_pages\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$bw_prints\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$color_copies\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$color_pages\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$color_prints\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$copies\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$faxed\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$have_ethernet\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$have_usb\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ipaddress\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$last_pages_counter\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$manufacturers_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$memory_size\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$prints\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$rv_pages\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$scanned\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$snmpcredentials_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$total_pages\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\Printer\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\MainAsset\\\\Printer\\:\\:\\$counters \\(stdClass\\) does not accept object\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$entities_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$ips\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$is_dynamic\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$mac\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$states_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type object supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\Unmanaged\\:\\:prepare\\(\\) should return array\\<int, stdClass\\> but returns array\\<int, object\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items of method Transfer\\:\\:moveItems\\(\\) expects array\\<class\\-string\\<CommonDBTM\\>, array\\<int\\>\\>, array\\<string, array\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:isAccessPoint\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\Unmanaged\\:\\:prepareForNetworkDevice\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of method RuleMatchedLog\\:\\:cleanOlddata\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$format of method Glpi\\\\Inventory\\\\Inventory\\:\\:setData\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function md5 expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$language of static method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getForItemKeyAndLanguage\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method send\\(\\) on Symfony\\\\Component\\\\HttpFoundation\\\\Response\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Kernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$strategy on Glpi\\\\Security\\\\Attribute\\\\SecurityStrategy\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/ControllerListener/FirewallStrategyListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$strategy of method Glpi\\\\Http\\\\Firewall\\:\\:applyStrategy\\(\\) expects \'admin_access\'\\|\'authenticated\'\\|\'central_access\'\\|\'faq_access\'\\|\'helpdesk_access\'\\|\'no_check\', string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/ControllerListener/FirewallStrategyListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function rawurlencode expects string, float\\|int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/ExceptionListener/AccessErrorListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findDeviceClass\\(\\) should return class\\-string\\<CommonDevice\\>\\|null but returns class\\-string\\<CommonDevice\\>\\|class\\-string\\<CommonDeviceModel\\>\\|class\\-string\\<CommonDeviceType\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\{0\\?\\: string, 1\\?\\: non\\-falsy\\-string, plugin\\?\\: non\\-empty\\-string, 2\\?\\: non\\-empty\\-string, itemtype\\?\\: non\\-empty\\-string, 3\\?\\: non\\-empty\\-string, form\\?\\: non\\-falsy\\-string, 4\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin\' might not exist on array\\{0\\?\\: string, 1\\?\\: non\\-falsy\\-string, plugin\\?\\: non\\-empty\\-string, 2\\?\\: non\\-empty\\-string, itemtype\\?\\: non\\-empty\\-string, 3\\?\\: non\\-empty\\-string, form\\?\\: non\\-falsy\\-string, 4\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method CommonDBTM\\:\\:isNewID\\(\\) expects int, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Glpi\\\\Asset\\\\Asset\\:\\:getById\\(\\) expects int\\|null, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Glpi\\\\Asset\\\\AssetModel\\:\\:getById\\(\\) expects int\\|null, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Glpi\\\\Asset\\\\AssetType\\:\\:getById\\(\\) expects int\\|null, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method Glpi\\\\Dropdown\\\\Dropdown\\:\\:getById\\(\\) expects int\\|null, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, bool\\|float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) should return callable\\(\\)\\: mixed but returns \\(callable\\(\\)\\: mixed\\)\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) should return callable\\(\\)\\: mixed but returns array\\{object, mixed\\}\\|object\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$level of method Monolog\\\\Handler\\\\StreamHandler\\:\\:__construct\\(\\) expects 100\\|200\\|250\\|300\\|400\\|500\\|550\\|600\\|\'ALERT\'\\|\'alert\'\\|\'CRITICAL\'\\|\'critical\'\\|\'DEBUG\'\\|\'debug\'\\|\'EMERGENCY\'\\|\'emergency\'\\|\'ERROR\'\\|\'error\'\\|\'INFO\'\\|\'info\'\\|\'NOTICE\'\\|\'notice\'\\|\'WARNING\'\\|\'warning\'\\|Monolog\\\\Level, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Log/AbstractLogHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPathInfo\\(\\) on Symfony\\\\Component\\\\HttpFoundation\\\\Request\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Log/AccessLogLineFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getQueryString\\(\\) on Symfony\\\\Component\\\\HttpFoundation\\\\Request\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Log/AccessLogLineFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function rawurlencode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Mail/SMTP/OauthConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method request\\(\\) on GuzzleHttp\\\\Client\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of static method GuzzleHttp\\\\Psr7\\\\Message\\:\\:toString\\(\\) expects Psr\\\\Http\\\\Message\\\\MessageInterface, Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Plugin\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addVolume\\(\\) on CronTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method log\\(\\) on CronTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, array\\{Plugin, string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$originalFileName of static method wapmorgan\\\\UnifiedArchive\\\\Formats\\:\\:detectArchiveFormat\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of function basename expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$dest of method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:downloadArchive\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/NotificationTargetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/NotificationTargetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/NotificationTargetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAPI\\(\\) on Glpi\\\\Marketplace\\\\Controller\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method hasWriteAccess\\(\\) on Glpi\\\\Marketplace\\\\Controller\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method requiresHigherOffer\\(\\) on Glpi\\\\Marketplace\\\\Controller\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function implode expects array, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:\\$target_items_mapping \\(array\\<class\\-string\\<CommonDBTM\\>, array\\<int, array\\{itemtype\\: class\\-string\\<CommonDBTM\\>, items_id\\: int\\}\\>\\>\\) does not accept non\\-empty\\-array\\<string, array\\<int, array\\{itemtype\\: class\\-string\\<CommonDBTM\\>, items_id\\: int\\}\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getCustomFieldSpecs\\(\\) should return array\\{system_name\\: string, label\\: string, type\\: class\\-string\\<Glpi\\\\Asset\\\\CustomFieldType\\\\AbstractType\\>, itemtype\\?\\: class\\-string\\<CommonDBTM\\>, options\\?\\: array\\{min\\?\\: int, max\\?\\: int, step\\?\\: int\\}\\} but returns array\\{system_name\\: string, label\\: string, type\\: \'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\BooleanType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\DateTimeType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\DateType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\NumberType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\StringType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\TextType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\URLType\', options\\?\\: array\\{step\\: \'any\'\\}, translations\\?\\: array\\{\\}\\}\\|array\\{system_name\\: string\\|null, label\\: string, type\\: \'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\BooleanType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\DateTimeType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\DateType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\DropdownType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\NumberType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\StringType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\TextType\'\\|\'Glpi\\\\\\\\Asset\\\\\\\\CustomFieldType\\\\\\\\URLType\', itemtype\\?\\: class\\-string\\<CommonDBTM\\>, options\\?\\: array\\<\'max\'\\|\'min\'\\|\'step\', int\\>, translations\\?\\: array\\{\\}\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype_chunk\' might not exist on array\\{0\\?\\: string, itemtype_chunk\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$field of function isForeignKeyField expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getCustomFieldSpecs\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getTargetField\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:isACustomField\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$source_itemtype of method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:updateItemtypeReferences\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$identifier of method Glpi\\\\OAuth\\\\AccessToken\\:\\:setUserIdentifier\\(\\) expects non\\-empty\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/AccessTokenRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$identifier of method Glpi\\\\OAuth\\\\Client\\:\\:setIdentifier\\(\\) expects non\\-empty\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/ClientRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$identifier of method Glpi\\\\OAuth\\\\Scope\\:\\:setIdentifier\\(\\) expects non\\-empty\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/ScopeRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$encryptionKey of class League\\\\OAuth2\\\\Server\\\\AuthorizationServer constructor expects Defuse\\\\Crypto\\\\Key\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method save\\(\\) on Glpi\\\\Progress\\\\ProgressStorage\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/StoredProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\StoredProgressIndicator\\:\\:jsonSerialize\\(\\) should return array\\{storage_key\\: string, started_at\\: string, updated_at\\: string, ended_at\\: string\\|null, failed\\: bool, current_step\\: int, max_steps\\: int, progress_bar_message\\: string, \\.\\.\\.\\} but returns array\\{storage_key\\: string, started_at\\: non\\-falsy\\-string, updated_at\\: non\\-falsy\\-string, ended_at\\: non\\-falsy\\-string\\|null, failed\\: bool, current_step\\: int, max_steps\\: int, progress_bar_message\\: string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/StoredProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<SimpleXMLElement\\>\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Profile\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$items_id of method CommonITILActor\\:\\:getActors\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item of static method NotificationEvent\\:\\:raiseEvent\\(\\) expects CommonGLPI, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method count\\(\\) on Symfony\\\\Component\\\\Routing\\\\RouteCollection\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Routing/PluginRoutesLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot clone non\\-object variable \\$plugin_routes of type Symfony\\\\Component\\\\Routing\\\\RouteCollection\\|null\\.$#',
	'identifier' => 'clone.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Routing/PluginRoutesLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:getDefaultCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'defaultfilter\' on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'savedsearches_id\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \\(int\\|string\\) on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValueToSelect\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getActionsFor\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Toolbox\\:\\:getNormalizedItemtype\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$params of static method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:cleanParams\\(\\) expects array, array\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function parse_str expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\.\\.\\.\\$arrays of function array_merge expects array, array\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/ExportSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/ExportSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/HTMLSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/HTMLSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Spreadsheet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$count of class GLPIPDF@anonymous/src/Glpi/Search/Output/Tcpdf\\.php\\:46 constructor expects int\\|null, bool\\|float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Tcpdf.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<string\\>\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type list\\<array\\<int\\>\\|int\\|string\\|null\\>\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string and \' \\<\\> \' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string and \' \\= \' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on non\\-empty\\-array\\<non\\-empty\\-string, string\\|null\\>\\|int\\<1, max\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0 on array\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 1 on array\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 2 on array\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 3 on array\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset non\\-empty\\-string on non\\-empty\\-array\\<non\\-empty\\-string, string\\|null\\>\\|int\\<1, max\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 8,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 11,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'count\' to array\\<array\\<string, string\\|null\\>\\|int\\<1, max\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'count\' to array\\<array\\<string, string\\|null\\>\\|int\\<1, max\\>\\|string\\>\\|string\\|null\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'displayname\' to array\\<array\\<string, string\\|null\\>\\|int\\<1, max\\>\\|string\\>\\|string\\|null\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityId\\(\\) on CommonITILObject\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$expression of class Glpi\\\\DBAL\\\\QueryExpression constructor expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$expression of static method Glpi\\\\DBAL\\\\QueryFunction\\:\\:convert\\(\\) expects Glpi\\\\DBAL\\\\QueryExpression\\|string, array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$field of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:makeTextCriteria\\(\\) expects string, array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function strpos expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionNumber\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Session\\:\\:haveTranslations\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$num of function abs expects float\\|int, int\\<min, \\-1\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$parent_itemtype of static method Glpi\\\\Search\\\\SearchEngine\\:\\:isPossibleMetaSubitemOf\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:dataSeek\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:fetchAssoc\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:numrows\\(\\) expects mysqli_result, mysqli_result\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$result of method DBmysql\\:\\:result\\(\\) expects mysqli_result, bool\\|mysqli_result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$separator of function explode expects non\\-empty\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$size of static method Toolbox\\:\\:getMioSizeFromString\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:makeTextSearchValue\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of static method Html\\:\\:computeGenericDateTimeSearch\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method DBmysqlIterator\\:\\:isOperator\\(\\) expects string, array\\<int\\>\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$IDf of function getSonsOf expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$child_itemtype of static method Glpi\\\\Search\\\\SearchEngine\\:\\:isPossibleMetaSubitemOf\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$val of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:makeTextCriteria\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDropdownTranslationJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$date_computation \\(array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string\\|null\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$tocompute \\(array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$token \\(array\\<int\\>\\|int\\|string\\|null\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\<string\\>\\|Glpi\\\\DBAL\\\\QueryExpression\\|string\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchEngine\\:\\:getMetaReferenceItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getCleanedOptions\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getDefaultToView\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonGLPI\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$user_id of static method DisplayPreference\\:\\:getForTypeUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$interface of static method DisplayPreference\\:\\:getForTypeUser\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset int\\|string might not exist on array\\{id\\: int, name\\: string, field\\: string, table\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Search\\\\SearchOption\\:\\:\\$search_opt_array \\(array\\{id\\: int, name\\: string, field\\: string, table\\: string\\}\\) does not accept array\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Search\\\\SearchOption\\:\\:\\$search_opt_array \\(array\\{id\\: int, name\\: string, field\\: string, table\\: string\\}\\) does not accept array\\{id\\?\\: int, name\\?\\: string, field\\?\\: string, table\\?\\: string\\}\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Search\\\\SearchOption\\:\\:\\$search_opt_array \\(array\\{id\\: int, name\\: string, field\\: string, table\\: string\\}\\) does not accept non\\-empty\\-array\\<int\\|string, mixed\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:getGracePeriodDaysLeft\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'secret\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Profile\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Session/SessionInfo.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method cleanProfile\\(\\) on Profile\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Session/SessionInfo.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Socket\\:\\:dropdownWiringSide\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Socket\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataSatisfactionSurvey.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataTicketAverageTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataTicketAverageTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataTicketNumber.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Graph/StatDataTicketNumber.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataAverageSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataTicketAverageTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataTicketAverageTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataTicketNumber.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Stat/Data/Sglobal/StatDataTicketNumber.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_all\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of function array_key_exists expects int\\|string, float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\|string\\|null\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseKeysChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseKeysChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_subclass_of expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaConsistencyChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'Create Table\' on array\\<string, float\\|int\\|string\\|null\\>\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on mysqli_result\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:getEffectiveCreateTableSql\\(\\) should return string but returns float\\|int\\|string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$version of static method Glpi\\\\Toolbox\\\\DatabaseSchema\\:\\:getEmptySchemaPath\\(\\) expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$version of static method Glpi\\\\Toolbox\\\\VersionParser\\:\\:isStableRelease\\(\\) expects string, array\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function Safe\\\\preg_replace expects array\\<string\\>\\|string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$offset of function array_splice expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'innodb_page_size\' on array\\<string, float\\|int\\|string\\|null\\>\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/DbConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/DbConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$size of static method Toolbox\\:\\:getSize\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/MemoryLimit.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_pop expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SeLinux.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function ucfirst expects string, int\\<min, \\-1\\>\\|int\\<2, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SeLinux.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SeLinux.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'status\' on array\\{\\}\\|Countable\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Status/StatusChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dbhost on DBmysql\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/System/Status/StatusChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$password of static method AuthLDAP\\:\\:tryToConnectToServer\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Status/StatusChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$separator of function explode expects non\\-empty\\-string, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Toolbox/ArrayPathAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property DOMNameSpaceNode\\|DOMNode\\:\\:\\$textContent\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DataExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type DOMNodeList\\<DOMNameSpaceNode\\|DOMNode\\>\\|false supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DataExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method removeChild\\(\\) on DOMNode\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DataExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$content of static method Glpi\\\\RichText\\\\RichText\\:\\:getTextFromHtml\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DataExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of static method Glpi\\\\Toolbox\\\\Filesystem\\:\\:normalizePath\\(\\) expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of function Safe\\\\parse_url expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'major\' might not exist on array\\{0\\?\\: string, major\\?\\: numeric\\-string, 1\\?\\: numeric\\-string, minor\\?\\: numeric\\-string, 2\\?\\: numeric\\-string, 3\\?\\: string, bugfix\\?\\: \'\'\\|numeric\\-string, 4\\?\\: \'\'\\|numeric\\-string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'minor\' might not exist on array\\{0\\?\\: string, major\\?\\: numeric\\-string, 1\\?\\: numeric\\-string, minor\\?\\: numeric\\-string, 2\\?\\: numeric\\-string, 3\\?\\: string, bugfix\\?\\: \'\'\\|numeric\\-string, 4\\?\\: \'\'\\|numeric\\-string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:getIconsDefinitions\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Group\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Group\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group of static method Group_User\\:\\:getDataForGroup\\(\\) expects Group, Group\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group of static method Group_User\\:\\:getParentsMembers\\(\\) expects Group, Group\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$content of class HTMLTableSubHeader constructor expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$content of class HTMLTableSuperHeader constructor expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$start of method HTMLTableCell\\:\\:computeStartEnd\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$totalNumberOflines of static method HTMLTableCell\\:\\:updateCellSteps\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<HTMLTableHeader\\>\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$headers of method HTMLTableRow\\:\\:displayRow\\(\\) expects array\\<HTMLTableHeader\\>, array\\<HTMLTableHeader\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method HTMLTableMain\\:\\:addItemType\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableHeader.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$content of class HTMLTableCell constructor expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'begin_date\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'end_date\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type list\\<string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'page\' to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:timestampToRelativeStr\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'checked\' might not exist on array\\{total\\: int\\<1, max\\>, checked\\?\\: int\\<0, max\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'language\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_key\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plugin_resource\' might not exist on array\\{0\\?\\: string, plugin_key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, plugin_resource\\?\\: string, 2\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'region\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$dest of static method Html\\:\\:redirect\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$hour of function Safe\\\\mktime expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:showOutputFormat\\(\\) expects class\\-string\\<CommonDBTM\\>\\|null, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$search of function str_replace expects array\\<string\\>\\|string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function parse_str expects string, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of static method Html\\:\\:addConfirmationOnAction\\(\\) expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\.\\.\\.\\$arrays of function array_merge expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of function Safe\\\\mktime expects int\\|null, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$offset of function array_splice expects int, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$timestamp of function date expects int\\|null, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 10,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$url of static method Ajax\\:\\:createIframeModalWindow\\(\\) expects string, string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of static method Toolbox\\:\\:substr\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$name \\(array\\|string\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 1\\|2\\|3 on array\\<int\\>\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getGroup\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromBinary\\(\\) expects array\\<int\\>, array\\<float\\|int\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromBinary\\(\\) expects array\\<int\\>, array\\<int\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromBinary\\(\\) expects array\\<int\\>, array\\<int\\>\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromString\\(\\) expects string, array\\<int\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBChild\\:\\:prepareInputForAdd\\(\\) expects array\\<string, mixed\\>, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBChild\\:\\:prepareInputForUpdate\\(\\) expects array\\<string, mixed\\>, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\<int\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$nb of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPAddress\\:\\:\\$binary \\(array\\<int\\>\\|string\\) does not accept array\\<float\\|int\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|false supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress_IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromBinary\\(\\) expects array\\<int\\>, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$netmask of method IPNetmask\\:\\:setNetmaskFromString\\(\\) expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$num of function decbin expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$times of function str_repeat expects int, float\\|int\\<min, 128\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPAddress\\:\\:\\$binary \\(array\\<int\\>\\|string\\) does not accept array\\<float\\|int\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPAddress\\:\\:\\$version \\(4\\|6\\|\'\'\\) does not accept int\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|false supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "&" between int\\|non\\-empty\\-string and int\\|non\\-empty\\-string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 3,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0\\|1\\|2\\|3 on array\\<int\\>\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 8,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0\\|1\\|2\\|3 on array\\|IPAddress\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset int\\<0, 3\\> on array\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 4,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getGroup\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setArrayFromAddress\\(\\) on array\\|IPAddress\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of method IPAddress\\:\\:setAddressFromBinary\\(\\) expects array\\<int\\>, array\\<float\\|int\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of static method IPNetwork\\:\\:computeNetworkRangeFromAdressAndNetmask\\(\\) expects array\\|IPAddress, IPAddress\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ipaddress of method IPAddress\\:\\:equals\\(\\) expects array\\<int\\>\\|IPAddress\\|string, array\\|IPAddress\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$netmask of static method IPNetwork\\:\\:computeNetworkRangeFromAdressAndNetmask\\(\\) expects array\\|IPNetmask, IPNetmask\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$networkAddress of static method IPNetwork\\:\\:checkIPFromNetwork\\(\\) expects array\\<int\\>\\|IPAddress, array\\|IPAddress\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$version of class IPNetmask constructor expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$version of method IPNetmask\\:\\:setNetmaskFromString\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method IPNetwork_Vlan\\:\\:assignVlan\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork_Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork_Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'code\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_job\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'items_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonITILObject\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access static property \\$rightname on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'staticProperty.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonITILObject\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canAddFollowups\\(\\) on CommonITILObject\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canReopen\\(\\) on CommonITILObject\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getClosedStatusArray\\(\\) on CommonITILObject\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on CommonDBTM\\|false\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILFollowup\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$input of method ITILFollowup\\:\\:updateParentStatus\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILFollowupTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowupTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowupTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_job\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maySolve\\(\\) on CommonITILObject\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILSolution\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplate\\:\\:getITILObjectClass\\(\\) should return class\\-string\\<CommonITILObject\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonGLPI\\:\\:addStandardTab\\(\\) expects class\\-string\\<CommonGLPI\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplateField\\:\\:getFieldNum\\(\\) should return int\\|false but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateHiddenField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateMandatoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateReadonlyField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILValidationTemplate\\:\\:displayValidatorField\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILValidationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILValidationTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILValidationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILValidationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on ITIL_ValidationStep\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ITIL_ValidationStep.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ImpactItem\\|true\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 9,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on bool\\|ImpactItem\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on ImpactItem\\|true\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:buildGraph\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:displayGraphView\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:displayListView\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Impact\\:\\:prepareParams\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ImpactContext\\:\\:findForImpactItem\\(\\) expects ImpactItem, bool\\|ImpactItem given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$objectOrClass of class ReflectionClass constructor expects class\\-string\\<T of object\\>\\|T of object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$key of static method Impact\\:\\:addEdge\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between float\\|int\\|string and float\\|int results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between float\\|int\\|string\\|null and float\\|int results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+" between float\\|int\\|string\\|null and int\\<1, max\\> results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\+\\=" between float\\|int\\|string\\|null and 12 results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to list\\<string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Infocom\\:\\:showForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$values of static method Infocom\\:\\:mapOldAmortiseFormat\\(\\) expects array, array\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_map expects array, list\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function implode expects array, list\\<string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$fiscaldate of static method Infocom\\:\\:linearAmortise\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$buydate of static method Infocom\\:\\:linearAmortise\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function Safe\\\\mktime expects int\\|null, float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function Safe\\\\mktime expects int\\|null, float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$usedate of static method Infocom\\:\\:linearAmortise\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, float\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 8,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\<0, max\\>\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type float\\|int\\<1, max\\>\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 5,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/InterfaceType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/InterfaceType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/InterfaceType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceSimcard.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addHeader\\(\\) on bool\\|HTMLTableGroup\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 7,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createRow\\(\\) on bool\\|HTMLTableGroup\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEmpty\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getNameField\\(\\) on class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:getDeviceType\\(\\) should return class\\-string\\<CommonDevice\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$header of method HTMLTableRow\\:\\:addCell\\(\\) expects HTMLTableHeader, HTMLTableSuperHeader\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$base of static method CommonDevice\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableBase, bool\\|HTMLTableGroup given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemFromArray\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$item of method HTMLTableRow\\:\\:addCell\\(\\) expects CommonDBTM\\|null, CommonDBTM\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#8 \\$dynamic_column of method Item_Devices\\:\\:getTableGroup\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableSuperHeader\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Disk\\:\\:getEncryptionStatusDropdown\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method dropdown\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 5,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \\(int\\|string\\) might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \\(int\\|string\\) might not exist on non\\-empty\\-array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset int\\<1, max\\> might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_column expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_filter expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_splice expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$oldstate of method Glpi\\\\Features\\\\KanbanInterface\\:\\:prepareKanbanStateForUpdate\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$state of static method Item_Kanban\\:\\:saveStateForItem\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method Glpi\\\\Features\\\\KanbanInterface\\:\\:prepareKanbanStateForUpdate\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$sort of static method Item_OperatingSystem\\:\\:getFromItem\\(\\) expects string\\|null, float\\|int\\|string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$fields\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getTypeName\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getModelClassInstance\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method dropdown\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Item_Rack\\:\\:getItemIcon\\(\\) expects string, class\\-string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$value of function getEntitiesRestrictCriteria expects \'\'\\|array\\<int\\>\\|int, int\\<min, \\-2\\>\\|int\\<0, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_SoftwareVersion\\:\\:updateDatasForItem\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$nb of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$numrows of static method Html\\:\\:printAjaxPager\\(\\) expects int, float\\|int\\<1, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$value of function getEntitiesRestrictCriteria expects \'\'\\|array\\<int\\>\\|int, array\\<int\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$itemtype \\(class\\-string\\<CommonDBTM\\>\\|CommonDBTM\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method KnowbaseItem\\:\\:getTreeCategoryList\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getDatas\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslatedValue\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'parent\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem_Item\\:\\:dropdownAllTypes\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem_Item\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem_Revision\\:\\:createNew\\(\\) should return int\\|false but returns bool\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Revision.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method clone\\(\\) on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method deleteByCriteria\\(\\) on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method shouldUseWorkInDayMode\\(\\) on LevelAgreementLevel\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:computeDate\\(\\) should return string\\|null but returns bool\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:computeExecutionDate\\(\\) should return string\\|null but returns bool\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:getLevelClass\\(\\) should return class\\-string\\<LevelAgreementLevel\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:getLevelTicketClass\\(\\) should return class\\-string\\<CommonDBTM\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:getTypeDropdown\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method CommonGLPI\\:\\:addStandardTab\\(\\) expects class\\-string\\<CommonGLPI\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$options of method CommonDBTM\\:\\:showFormButtons\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$options of method CommonDBTM\\:\\:showFormHeader\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$start of method Calendar\\:\\:computeEndDate\\(\\) expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Static property LevelAgreement\\:\\:\\$itemtype \\(string\\) does not accept class\\-string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'force_actions\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 7,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreementLevel\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'execution_time\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'mcc\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/LineOperator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$get_ip of static method Link\\:\\:getIPAndMACForItem\\(\\) expects bool, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$get_mac of static method Link\\:\\:getIPAndMACForItem\\(\\) expects bool, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$value of function getEntitiesRestrictCriteria expects \'\'\\|array\\<int\\>\\|int, array\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|CommonDBTM\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSystemSQLCriteria\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeDeleted\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeLocated\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Location\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getErrorMessage\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:can\\(\\) expects int, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_starts_with expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$specific_itemtype of method Lockedfield\\:\\:getFieldsToLock\\(\\) expects string\\|null, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$baseitemtype of static method Lock\\:\\:getLocksQueryInfosByItemType\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on class\\-string\\<CommonDBTM\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Lockedfield.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Lockedfield\\:\\:itemDeleted\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lockedfield.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Lockedfield\\:\\:setLastValue\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Lockedfield.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Log\\:\\:constructHistory\\(\\) should return bool but returns bool\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Log\\:\\:getLinkedActionLabel\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'key\' might not exist on array\\{0\\?\\: string, key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, operator\\?\\: string, 2\\?\\: string, values\\?\\: non\\-empty\\-string, 3\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'operator\' might not exist on array\\{0\\?\\: string, key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, operator\\?\\: string, 2\\?\\: string, values\\?\\: non\\-empty\\-string, 3\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'values\' might not exist on array\\{0\\?\\: string, key\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, operator\\?\\: string, 2\\?\\: string, values\\?\\: non\\-empty\\-string, 3\\?\\: non\\-empty\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method User\\:\\:getNameForLog\\(\\) expects int, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method User\\:\\:getNameForLog\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$params of method DBmysql\\:\\:buildInsert\\(\\) expects array\\<string, mixed\\>\\|Glpi\\\\DBAL\\\\QuerySubQuery, array\\<int\\|string, Glpi\\\\DBAL\\\\QueryParam\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method countMessages\\(\\) on Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFieldValue\\(\\) on Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFieldValue\\(\\) on array\\|ArrayIterator\\|Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 10,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNumberByUniqueId\\(\\) on Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method removeMessage\\(\\) on Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getAttached\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) should return string but returns array\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'event\' might not exist on array\\{0\\?\\: string, uuid\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, 2\\?\\: string, itemtype\\?\\: string, 3\\?\\: string, items_id\\?\\: \'\'\\|numeric\\-string, 4\\?\\: \'\'\\|numeric\\-string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'uuid\' might not exist on array\\{0\\?\\: string, uuid\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, 2\\?\\: string, itemtype\\?\\: string, 3\\?\\: string, items_id\\?\\: \'\'\\|numeric\\-string, 4\\?\\: \'\'\\|numeric\\-string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'uuid\' might not exist on array\\{0\\?\\: string, uuid\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, 2\\?\\: string, itemtype\\?\\: string, 3\\?\\: string, items_id\\?\\: numeric\\-string, 4\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function Safe\\\\strtotime expects string, array\\|ArrayIterator\\|Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$email of static method User\\:\\:getOrImportByEmail\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of static method Document\\:\\:isValidDoc\\(\\) expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of static method Toolbox\\:\\:filename\\(\\) expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$to of method MailCollector\\:\\:sendMailRefusedResponse\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$email of method CommonITILActor\\:\\:isAlternateEmailForITILObject\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$email of method Supplier_Ticket\\:\\:isSupplierEmail\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$folder of method Laminas\\\\Mail\\\\Storage\\\\Writable\\\\WritableInterface\\:\\:moveMessage\\(\\) expects Laminas\\\\Mail\\\\Storage\\\\Folder\\|string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$haystack of function in_array expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$subject of method MailCollector\\:\\:getRecursiveAttached\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$subpart of method MailCollector\\:\\:getRecursiveAttached\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Manufacturer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Manufacturer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Manufacturer\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Manufacturer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Manufacturer.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|false supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:getAction\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin_key of static method Plugin\\:\\:doOneHook\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin_key of static method Plugin\\:\\:isPluginActive\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\.\\.\\.\\$arrays of function array_merge expects array, array\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Lock\\:\\:getMassiveActionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$check_item \\(CommonDBTM\\|null\\) does not accept CommonDBTM\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$current_itemtype \\(class\\-string\\<CommonDBTM\\>\\|null\\) does not accept string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$redirect \\(string\\) does not accept string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and 0\\|0\\.0\\|\'\'\\|\'0\'\\|\'NULL \'\\|array\\{\\}\\|false\\|null results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and 0\\|0\\.0\\|array\\{\\}\\|string\\|false\\|null results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$default_value of method Migration\\:\\:fieldFormat\\(\\) expects string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getGroup\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:show\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipmentModelStencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipmentModelStencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createAnotherRow\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method createRow\\(\\) on bool\\|HTMLTableGroup\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getGroup\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$base of static method NetworkName\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableBase, bool\\|HTMLTableGroup given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_no_history\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:prepareInputForUpdate\\(\\) should return array\\<string, mixed\\>\\|false but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'file\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'line\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$asset of method NetworkPort\\:\\:getAssetLink\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBChild\\:\\:prepareInputForAdd\\(\\) expects array\\<string, mixed\\>, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method NetworkPort\\:\\:getIpsForPort\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$user_id of static method DisplayPreference\\:\\:getForTypeUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\<string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$origin of method NetworkPortInstantiation\\:\\:showNetworkPortSelector\\(\\) expects \'NetworkPortAggregate\'\\|\'NetworkPortAlias\', class\\-string\\<static\\(NetworkPortAggregate\\)\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortAggregate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$origin of method NetworkPortInstantiation\\:\\:showNetworkPortSelector\\(\\) expects \'NetworkPortAggregate\'\\|\'NetworkPortAlias\', class\\-string\\<static\\(NetworkPortAlias\\)\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortConnectionLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortConnectionLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "%%" between int\\|string and 100 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "%%" between int\\|string and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\*" between float\\|int\\|string\\|null and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/" between int\\|string and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/\\=" between int\\<1001, max\\>\\|string and 100 results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortEthernet\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "%%" between int\\|string and 100 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "%%" between int\\|string and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\*" between float\\|int\\|string\\|null and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/" between int\\|string and 1000 results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "/\\=" between int\\<1001, max\\>\\|string and 100 results in an error\\.$#',
	'identifier' => 'assignOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getPortSpeed\\(\\) should return array\\|string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$elements of static method Dropdown\\:\\:showFromArray\\(\\) expects array, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method can\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method canEdit\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isRecursive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isTemplate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortInstantiation\\:\\:dropdownConnect\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortWifi\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortWifi.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort_NetworkPort\\:\\:createHub\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort_NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ports_id of method NetworkPort_NetworkPort\\:\\:disconnectFrom\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort_NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort_Vlan\\:\\:displayTabContentForItem\\(\\) should return bool but returns bool\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort_Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotImportedEmail\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotImportedEmail.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$nb of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ong of method CommonGLPI\\:\\:addStandardTab\\(\\) expects array\\<string, bool\\|string\\>, array\\<string, bool\\|string\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationEvent\\:\\:dropdownEvents\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method NotificationTarget\\<CommonGLPI\\>\\:\\:getMessageIdForEvent\\(\\) expects string\\|null, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$error of static method NotificationEventMailing\\:\\:handleFailedSend\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$width of static method Document\\:\\:getResizedImagePath\\(\\) expects int, float\\|int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method NotificationTarget\\<CommonGLPI\\>\\:\\:shouldNotificationBeSentImmediately\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#\\#\' and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#\\#FOREACH FIRST …\' and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#\\#FOREACH LAST …\' and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#\\#FOREACH\' and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\#\\#lang\\.\' and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and non\\-empty\\-array\\|non\\-falsy\\-string\\|true results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset non\\-falsy\\-string to array\\<array\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDBForTarget\\(\\) on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getAdminData\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getEntityAdminsData\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTargetField\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getInstanceByType\\(\\) should return NotificationTarget\\<Item of CommonGLPI\\>\\|false but returns NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getInstanceClass\\(\\) should return class\\-string\\<NotificationTarget\\<Item of CommonGLPI\\>\\> but returns non\\-falsy\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getMode\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getTags\\(\\) should return array\\<array\\<string\\>\\|string\\>\\|void but returns array\\<string, array\\<array\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$authtype of static method Auth\\:\\:isAlternateAuth\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$event of method NotificationTarget\\<T of CommonGLPI\\>\\:\\:addDataForTemplate\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$event of class NotificationTarget constructor expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<T of CommonGLPI\\>\\:\\:\\$mode \\(\'ajax\'\\|\'irc\'\\|\'mailing\'\\|\'sms\'\\|\'websocket\'\\|\'xmpp\'\\|null\\) does not accept string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<T of CommonGLPI\\>\\:\\:\\$notification_targets_labels \\(array\\<1\\|2\\|3\\|4\\|5\\|6, array\\<int, string\\>\\>\\) does not accept non\\-empty\\-array\\<1\\|2\\|3\\|4\\|5\\|6, array\\<\'\'\\|int, string\\>\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<T of CommonGLPI\\>\\:\\:\\$obj \\(\\(T of CommonGLPI\\)\\|null\\) does not accept CommonGLPI\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<T of CommonGLPI\\>\\:\\:\\$target \\(array\\<string, array\\{language\\: string, additionnaloption\\: array, username\\: string\\}\\>\\) does not accept non\\-empty\\-array\\<non\\-empty\\-array\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<T of CommonGLPI\\>\\:\\:\\$target_object \\(array\\<CommonGLPI\\>\\) does not accept array\\<CommonGLPI\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Certificate\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Certificate\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isNewItem\\(\\) on Certificate\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Certificate\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCertificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonITILValidation\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$grouplinkclass on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$supplierlinkclass on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$userlinkclass on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method countSuppliers\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAllStatusArray\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonITILValidation\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValidationClassInstance\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getSatisfactionClassInstance\\(\\) on \\(T of CommonITILObject\\)\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonITILValidation\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'type\' might not exist on array\\{type\\?\\: 1\\|2\\|3, use_notification\\?\\: bool, users_id\\?\\: int, alternative_email\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'use_notification\' might not exist on array\\{type\\: 2, use_notification\\?\\: bool, users_id\\?\\: int, alternative_email\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'users_id\' might not exist on array\\{type\\: 2, use_notification\\: true, users_id\\?\\: int, alternative_email\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method NotificationTargetCommonITILObject\\<T of CommonITILObject\\>\\:\\:getDataForObject\\(\\) expects CommonITILObject, \\(T of CommonITILObject\\)\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, class\\-string\\<T of CommonITILObject\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Html\\:\\:convDateTime\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetContract.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetContract.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetContract.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetDBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetDBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Domain\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/NotificationTargetDomain.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isNewItem\\(\\) on Domain\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetDomain.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Domain\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetDomain.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetFieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTargetFieldUnicity\\:\\:getTags\\(\\) should return array\\<array\\<string\\>\\|string\\>\\|void but returns array\\<string, array\\<array\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetFieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetFieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<FieldUnicity\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetFieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetInfocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetInfocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetInfocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Entity\\|Group\\|Profile\\|User\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on KnowbaseItemCategory\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on KnowbaseItem\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on KnowbaseItem\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Entity\\|Group\\|Profile\\|User\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on KnowbaseItem\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on Entity\\|Group\\|Profile\\|User\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetKnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTargetMailCollector\\:\\:getTags\\(\\) should return array\\<array\\<string\\>\\|string\\>\\|void but returns array\\<string, array\\<array\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ObjectLock\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<ObjectLock\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonITILTask\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<CommonITILTask\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTargetPlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Project\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on Project\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 26,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Project\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Log\\:\\:getHistoryData\\(\\) expects CommonDBTM, Project\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Project\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<array\\<string, mixed\\>\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Project\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Project\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\|float\\|int\\|string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProject.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on ProjectTask\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on ProjectTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 26,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on ProjectTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Log\\:\\:getHistoryData\\(\\) expects CommonDBTM, ProjectTask\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<ProjectTask\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetReservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetReservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on Reservation\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 6,
	'path' => __DIR__ . '/src/NotificationTargetReservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetReservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<Reservation\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetReservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on SavedSearch_Alert\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign new offset to array\\<array\\<string, mixed\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on Ticket\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on User\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getDefaultEmail\\(\\) on User\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on User\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on User\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPasswordExpirationTime\\(\\) on User\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method hasPasswordExpired\\(\\) on User\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTargetUser\\:\\:getTags\\(\\) should return array\\<array\\<string\\>\\|string\\>\\|void but returns array\\<string, array\\<array\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NotificationTarget\\<User\\>\\:\\:\\$data \\(array\\<string, array\\<string\\>\\|string\\>\\) does not accept array\\<string, array\\<string\\>\\|string\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 4,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:getDataForHtml\\(\\) should return array\\<string, array\\<string\\>\\|string\\> but returns array\\<string, array\\<array\\<string\\>\\|string\\>\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\<string\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 might not exist on array\\<string\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<CommonGLPI\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function property_exists expects object\\|string, CommonGLPI\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$search of function str_replace expects array\\<string\\>\\|string, array\\<string\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<array\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$tag_descriptions on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'type\' to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTags\\(\\) on NotificationTarget\\<CommonDBTM\\>\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'from\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'notifications_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OAuthClient\\:\\:defineTabs\\(\\) should return array\\<string, string\\> but returns array\\<string, bool\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/OAuthClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PDU\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$side of static method PDU_Rack\\:\\:getOtherSide\\(\\) expects int, array\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PassiveDCEquipment\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PendingReason\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'followup_frequency\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'followups_before_resolution\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$fields\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getID\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method object\\:\\:getType\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on PendingReason_Item\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itil_item of method AbstractITILChildTemplate\\:\\:getRenderedContent\\(\\) expects CommonITILObject, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item of static method NotificationEvent\\:\\:raiseEvent\\(\\) expects CommonGLPI, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReasonCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_job\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 8,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'_status\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on PendingReason\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getType\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PendingReason_Item\\:\\:createForItem\\(\\) should return bool but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PendingReason_Item\\:\\:handleTimelineEdits\\(\\) should return array but returns array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$interval of method DateTime\\:\\:add\\(\\) expects DateInterval, DateInterval\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Phone\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonITILTask\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on class\\-string\\<CommonITILTask\\>\\|CommonITILTask\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDBByCrit\\(\\) on CommonITILTask\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:getAdditionalMenuOptions\\(\\) should return array\\<string, array\\{title\\: string, page\\: string, icon\\?\\: string, links\\?\\: array\\{search\\: string, add\\?\\: string, template\\?\\: string\\}\\}\\>\\|false but returns array\\{Glpi\\\\Features\\\\PlanningEvent\\: array\\{title\\: string, page\\: string, links\\: non\\-empty\\-array\\}\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<CommonDBTM\\>\\|CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function method_exists expects object\\|string, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item2 of method CommonDBRelation\\:\\:getFromDBForItems\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|CommonDBTM\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Static call to instance method stdClass\\:\\:displayPlanningItem\\(\\)\\.$#',
	'identifier' => 'method.staticCall',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Static call to instance method stdClass\\:\\:populateNotPlanned\\(\\)\\.$#',
	'identifier' => 'method.staticCall',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Sabre\\\\VObject\\\\Component\\:\\:remove\\(\\) expects Sabre\\\\VObject\\\\Component\\|Sabre\\\\VObject\\\\Property\\|string, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vcomponent of method PlanningExternalEvent\\:\\:getCommonInputFromVcomponent\\(\\) expects Sabre\\\\VObject\\\\Component, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method PlanningRecall\\:\\:getFromDBForItemAndUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method PlanningRecall\\:\\:getFromDBForItemAndUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addVolume\\(\\) on CronTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningRecall\\:\\:managePlanningUpdates\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'field\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method PlanningRecall\\:\\:getFromDBForItemAndUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:getInformationsFromDirectory\\(\\) should return array but returns array\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method User\\:\\:getNameForLog\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$language of static method Glpi\\\\Dashboard\\\\Grid\\:\\:getAllDashboardCardsCacheKey\\(\\) expects string\\|null, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$name of static method Plugin\\:\\:messageMissingRequirement\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$typetab of static method CommonGLPI\\:\\:registerStandardTab\\(\\) expects class\\-string\\<CommonGLPI\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\|string\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Printer\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Printer\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Printer\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of class Safe\\\\DateTime constructor expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Problem\\:\\:displayTabContentForItem\\(\\) should return bool but returns false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProblemTask\\:\\:displayPlanningItem\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProblemTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProblemTask\\:\\:populatePlanning\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProblemTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProblemTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProblemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProblemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access static property \\$rightname on string\\|null\\.$#',
	'identifier' => 'staticProperty.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on string\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile\\:\\:displayTabContentForItem\\(\\) should return bool but returns false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'file\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'line\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Profile\\:\\:getRightsFor\\(\\) expects class\\-string\\<CommonDBTM\\>, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset mixed on array\\<\'\'\\>\\|Countable\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on Profile\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'rights\' might not exist on array\\<string, mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dohistory on Entity\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dohistory on Profile\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$dohistory on User\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Entity\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on Profile\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getID\\(\\) on User\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on Entity\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on Profile\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getNameID\\(\\) on User\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on Entity\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on Profile\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getType\\(\\) on User\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method Log\\:\\:history\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on static\\(Project\\)\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on static\\(Project\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Project\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_contains expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Project\\:\\:getKanbanPluginFilters\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object_or_class of function is_a expects object\\|string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$options of method CommonDBTM\\:\\:showFormButtons\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$options of method CommonDBTM\\:\\:showFormHeader\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$DESCRIPTION on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$PERCENT\\-COMPLETE on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$RRULE on Sabre\\\\VObject\\\\Component\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method delete\\(\\) on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Project\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isDeleted\\(\\) on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method restore\\(\\) on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on static\\(ProjectTask\\)\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'display_name\' might not exist on array\\{id\\: int, projecttasks_id\\: int, itemtype\\: class\\-string\\<CommonDBTM\\>, items_id\\: int, display_name\\?\\: string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projects_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'projecttasks_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Sabre\\\\VObject\\\\Component\\:\\:remove\\(\\) expects Sabre\\\\VObject\\\\Component\\|Sabre\\\\VObject\\\\Property\\|string, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vcomponent of method ProjectTask\\:\\:getCommonInputFromVcomponent\\(\\) expects Sabre\\\\VObject\\\\Component, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDBTM\\:\\:initForm\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTaskTeam.php',
];
$ignoreErrors[] = [
	'message' => '#^Part \\$sort \\(\'_effect_duration\'\\|\'fname\'\\|\'name\'\\|\'percent_done\'\\|\'plan_end_date\'\\|\'plan_start_date\'\\|\'planned_duration\'\\|\'projectname\'\\|\'sname\'\\|\'tname\'\\|array\\{\'plan_start_date ASC\'\\|\'plan_start_date DESC\', \'name\'\\}\\) of encapsed string cannot be cast to string\\.$#',
	'identifier' => 'encapsedStringPart.nonString',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTeam.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CronTask\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setVolume\\(\\) on CronTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedNotification\\:\\:canCreate\\(\\) should return bool but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedNotification\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'from\' might not exist on array\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setVolume\\(\\) on CronTask\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedWebhook\\:\\:canCreate\\(\\) should return bool but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RSSFeed\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Html\\:\\:convDateTime\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on CommonDBTM\\|false\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getModelClassInstance\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLinkURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSearchURL\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:addFiles\\(\\) expects array\\<string, mixed\\>, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Sabre\\\\VObject\\\\Component\\:\\:remove\\(\\) expects Sabre\\\\VObject\\\\Component\\|Sabre\\\\VObject\\\\Property\\|string, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$object of function get_class expects object, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vcomponent of method Reminder\\:\\:getCommonInputFromVcomponent\\(\\) expects Sabre\\\\VObject\\\\Component, Sabre\\\\VObject\\\\Component\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$users_id of method PlanningRecall\\:\\:getFromDBForItemAndUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReminderTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeTemplate\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$by_itemtype of static method Report\\:\\:getNetworkReport\\(\\) expects class\\-string\\<Glpi\\\\Socket\\|Location\\|NetworkEquipment\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:countForItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, \\(int\\|string\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$networkequipments_id of static method Report\\:\\:getNetworkEquipmentCriteria\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$sockets_id of static method Report\\:\\:getNetworkSocketCriteria\\(\\) expects int\\<1, max\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function Safe\\\\mktime expects int\\|null, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_followup_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/RequestType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_helpdesk_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/RequestType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_mail_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/RequestType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_mailfollowup_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/RequestType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getEntityID\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getIcon\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTypeName\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isRecursive\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function Safe\\\\strtotime expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method ReservationItem\\:\\:getFromDBbyItem\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method ReservationItem\\:\\:getAvailableItems\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<SimpleXMLElement\\>\\|false\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<SimpleXMLElement\\>\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset array\\<int\\|string\\>\\|string to array\\<int\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSpecificValueToDisplay\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getSpecificValueToSelect\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTitle\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:checkCriteria\\(\\) should return bool but returns bool\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getAction\\(\\) should return array but returns array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getCriteriaDisplayPattern\\(\\) should return string\\|null but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getCriteriaDisplayPattern\\(\\) should return string\\|null but returns int\\|string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getMenuContent\\(\\) should return array\\<string, array\\<string, array\\<string, array\\{search\\: string, add\\?\\: string, template\\?\\: string\\}\\|array\\{title\\: string, page\\: string, icon\\?\\: string, links\\?\\: array\\{search\\: string, add\\?\\: string, template\\?\\: string\\}\\}\\|string\\>\\|string\\>\\|string\\|true\\>\\|false but returns array\\{rule\\?\\: array\\{title\\: string, page\\: string, icon\\: string, options\\?\\: non\\-empty\\-array\\<string, array\\{title\\: string, page\\: string, links\\?\\: non\\-empty\\-array\\{add\\?\\: string, search\\?\\: \'/front/transfer\\.php\', transfer_list\\?\\: \'/front/transfer…\'\\}\\}\\>\\}, dictionnary\\?\\: array\\{options\\: non\\-empty\\-array\\<non\\-falsy\\-string, array\\{title\\: string, page\\: string, links\\: array\\{search\\: string, add\\?\\: string\\}\\}\\>\\}\\|array\\{title\\: string, shortcut\\: \'\', page\\: \'/front/dictionnary…\', icon\\: string, options\\: non\\-empty\\-array\\<non\\-falsy\\-string, array\\{title\\: string, page\\: string, links\\: array\\{search\\: string, add\\?\\: string\\}\\}\\>\\}, is_multi_entries\\: true\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'appendto\' might not exist on array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of function getUserName expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of function getUserName expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method Rule\\:\\:getActionValue\\(\\) expects string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Rule\\:\\:cleanForItemActionOrCriteria\\(\\) expects CommonDBTM, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:show\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of static method Html\\:\\:computeGenericDateTimeSearch\\(\\) expects string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getImpactName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getPriorityName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getStatus\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getUrgencyName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILValidation\\:\\:getStatus\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Dropdown\\:\\:getGlobalSwitch\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Dropdown\\:\\:getValueWithUnit\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Ticket\\:\\:getTicketTypeName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, array\\<int\\|string\\>\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$haystack of function in_array expects array, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$params of static method Rule\\:\\:doHookAndMergeResults\\(\\) expects array\\<string, array\\<string, array\\<int\\|string\\>\\|string\\>\\>, array\\<int\\|string, array\\<mixed\\>\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$params of static method Rule\\:\\:doHookAndMergeResults\\(\\) expects array\\<string, array\\<string, array\\<int\\|string\\>\\|string\\>\\>, array\\<int\\|string, array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$type of method Rule\\:\\:getActionValue\\(\\) expects string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$pattern of method Rule\\:\\:getAdditionalCriteriaDisplayPattern\\(\\) expects string, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\<int\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<int\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleAction\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleAction\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of static method RuleAction\\:\\:getActionByID\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:show\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$sub_type of method RuleAction\\:\\:getAlreadyUsedForRuleID\\(\\) expects class\\-string\\<Rule\\>, class\\-string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Static property RuleAction\\:\\:\\$itemtype \\(class\\-string\\<Rule\\>\\) does not accept class\\-string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 11,
	'path' => __DIR__ . '/src/RuleAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on Rule\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 4,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Rule\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method find\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getRuleWithCriteriasAndActions\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method maybeRecursive\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method update\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 3,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method useConditions\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getConditionsArray\\(\\) on Rule\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:deleteRuleOrder\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:getRuleClassName\\(\\) should return class\\-string\\<Rule\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:getRulesXMLFile\\(\\) should return string\\|null but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'table\' might not exist on array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$classname of function isPluginItemType expects string, class\\-string\\<RuleCollection\\>\\|RuleCollection given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of function getItemForTable expects string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$table of static method Dropdown\\:\\:getDropdownName\\(\\) expects string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method RuleCriteria\\:\\:getConditionByID\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$type of method Rule\\:\\:getActionValue\\(\\) expects string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Property SingletonRuleList\\:\\:\\$list \\(array\\<Rule\\>\\) does not accept array\\<Rule\\|null\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 12,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 5,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset array\\<int\\|string\\>\\|string to array\\<int\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'appendto\' might not exist on array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_column expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of function array_key_exists expects int\\|string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$urgency of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$impact of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$subject of function Safe\\\\preg_match expects string, array\\<int\\|string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\<int\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCommonITILObjectCollection\\:\\:getItemtype\\(\\) should return class\\-string\\<CommonITILObject\\> but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObjectCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCriteria\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCriteria\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_shift expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method RuleCriteria\\:\\:getConditions\\(\\) expects class\\-string\\<Rule\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Static property RuleCriteria\\:\\:\\$itemtype \\(class\\-string\\<Rule\\>\\) does not accept class\\-string\\|false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDefineItemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'type\' to array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDefineItemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleDefineItemtype\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDefineItemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDefineItemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareAllRulesInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDefineItemtypeCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleDictionnaryDropdownCollection\\:\\:replayRulesOnExistingDB\\(\\) should return int\\|false but returns bool\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getItemForItemtype expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Dropdown\\:\\:importExternal\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$printermodels_id of static method CartridgeItem\\:\\:addCompatibleType\\(\\) expects int, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 4,
	'path' => __DIR__ . '/src/RuleDictionnaryPrinter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 7,
	'path' => __DIR__ . '/src/RuleDictionnarySoftware.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftware.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<SimpleXMLElement\\>\\|false\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAgent\\(\\) on class\\-string\\|object\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method rulepassed\\(\\) on class\\-string\\|object\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleImportAsset\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type class\\-string\\<CommonDBTM\\>\\|CommonDBTM\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$val of method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareAllRulesInput\\(\\) expects stdClass, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin of static method Plugin\\:\\:getInfo\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plugin_key of static method Plugin\\:\\:isPluginActive\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleLocation.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 7,
	'path' => __DIR__ . '/src/RuleMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 13,
	'path' => __DIR__ . '/src/RuleRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 3,
	'path' => __DIR__ . '/src/RuleRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects LDAP\\\\Result, array\\|LDAP\\\\Result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRightCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 3,
	'path' => __DIR__ . '/src/RuleSoftwareCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|null supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 8,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 6,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\<mixed\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 4,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'table\' to array\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 9,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleTicket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method SNMPCredential\\:\\:checkRequiredFields\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/SNMPCredential.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method SNMPCredential\\:\\:prepareInputs\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/SNMPCredential.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'users_id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:dropdownDoCount\\(\\) should return string\\|void but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:getSpecificValueToSelect\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:setDoCount\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:setEntityRecur\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:unmarkDefaults\\(\\) should return bool but returns bool\\|mysqli_result\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int\\|string, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'savedsearches_id\' might not exist on array\\<string, mixed\\>\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch_User\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\|Glpi\\\\DBAL\\\\QueryExpression supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:displayData\\(\\) should return bool but returns false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:explodeWithID\\(\\) should return array\\<string\\>\\|false but returns array\\<string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$from_type of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getMetaLeftJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:manageParams\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getLeftJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getSelectCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchEngine\\:\\:show\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getActionsFor\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getCleanedOptions\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:isInfocomOption\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ref_table of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDefaultJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$to_type of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getMetaLeftJoinCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$fixed of static method Glpi\\\\Search\\\\Output\\\\HTMLSearchOutput\\:\\:showHeader\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getHavingCriteria\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$issort of static method Glpi\\\\Search\\\\Output\\\\HTMLSearchOutput\\:\\:showHeaderItem\\(\\) expects bool, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$meta_type of static method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getLeftJoinCriteria\\(\\) expects \'\'\\|class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type array\\<int\\|string, array\\<int\\|string, array\\<string, string\\>\\|string\\>\\|string\\>\\|string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function Safe\\\\strtotime expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$interface of static method Profile\\:\\:getRightsForForm\\(\\) expects \'all\'\\|\'central\'\\|\'helpdesk\', string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$userID of static method Session\\:\\:initEntityProfiles\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$user_id of class Glpi\\\\Session\\\\SessionInfo constructor expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'name\' to array\\<string, array\\<int\\|string\\>\\|string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:addSoftware\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:dropdownLicenseToInstall\\(\\) should return int but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:dropdownSoftwareToInstall\\(\\) should return int but returns int\\|string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getDatas\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Software\\:\\:getTreeCategoryList\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslatedValue\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$manufacturer_id of method Software\\:\\:addSoftware\\(\\) expects int, bool\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareLicense\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$nb of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects int, int\\<0, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SolutionTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SolutionTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SolutionTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset non\\-falsy\\-string to array\\<string, string\\>\\|string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method isEntityAssign\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Change\'\\|\'Problem\'\\|\'Ticket\' might not exist on array\\{Ticket\\: array\\{Ticket_Global\\: array\\{name\\: string, file\\: \'stat\\.global\\.php…\'\\}, Ticket_Ticket\\: array\\{name\\: string, file\\: \'stat\\.tracking\\.php…\'\\}, Ticket_Location\\: array\\{name\\: string, file\\: \'stat\\.location\\.php…\'\\}, Ticket_Item\\: array\\{name\\: string, file\\: \'stat\\.item\\.php…\'\\}\\}, Problem\\?\\: array\\{Problem_Global\\: array\\{name\\: string, file\\: \'stat\\.global\\.php…\'\\}, Problem_Problem\\: array\\{name\\: string, file\\: \'stat\\.tracking\\.php…\'\\}, Problem_Location\\: array\\{name\\: string, file\\: \'stat\\.location\\.php…\'\\}, Problem_Item\\: array\\{name\\: string, file\\: \'stat\\.item\\.php…\'\\}\\}, Change\\?\\: array\\{Change_Global\\: array\\{name\\: string, file\\: \'stat\\.global\\.php…\'\\}, Change_Change\\: array\\{name\\: string, file\\: \'stat\\.tracking\\.php…\'\\}, Change_Location\\: array\\{name\\: string, file\\: \'stat\\.location\\.php…\'\\}, Change_Item\\: array\\{name\\: string, file\\: \'stat\\.item\\.php…\'\\}\\}\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset mixed might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_sum expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonITILTask\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$value2 of static method Stat\\:\\:constructEntryValues\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method State\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/State.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/State.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Stencil\\:\\:getStencilFromItem\\(\\) expects CommonDBTM, CommonDBTM\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'linktype\' on array\\{linktype\\: class\\-string\\<CommonDBTM\\>, entities_id\\: int, name\\: string, id\\: int, serial\\: string\\|null, otherserial\\: string\\|null, is_deleted\\: 0\\|1\\}\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Supplier\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method Supplier\\:\\:managePictures\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TaskCategory\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TaskCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TaskCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TaskTemplate\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/TaskTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TaskTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TaskTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TaskTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'engine\' might not exist on array\\{0\\?\\: string, engine\\?\\: non\\-empty\\-string, 1\\?\\: non\\-empty\\-string, 2\\?\\: string, version\\?\\: string, 3\\?\\: string, 4\\?\\: string, comment\\?\\: non\\-falsy\\-string, \\.\\.\\.\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$hostname of function gethostbyname expects string, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getTableForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, string\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getField\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getFromDB\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:displayTabContentForItem\\(\\) should return bool but returns false\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:dropdownType\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itilcategories_id\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'type\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of method CommonITILObject\\:\\:isTakeIntoAccountComputationBlocked\\(\\) expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$params of method CommonITILObject\\:\\:getEntitiesForRequesters\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$urgency of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method CommonITILValidation\\:\\:getNumberToValidate\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$users_id of static method CommonITILValidation\\:\\:getTargetCriteriaForUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$delay of method Calendar\\:\\:computeEndDate\\(\\) expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$impact of static method CommonITILObject\\:\\:computePriority\\(\\) expects int\\<1, 5\\>, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$item of method CommonITILObject\\:\\:setTechAndGroupFromHardware\\(\\) expects CommonDBTM\\|null, CommonDBTM\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$users_id of method CommonITILObject\\:\\:isUser\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 9,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$specifictime of static method Html\\:\\:computeGenericDateTimeSearch\\(\\) expects int\\|string, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TicketTask\\:\\:displayPlanningItem\\(\\) should return string but returns string\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TicketTask\\:\\:populatePlanning\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TicketTemplate\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:checkNewVersionAvailable\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:decodeFromUtf8\\(\\) should return string but returns array\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:doCallCurl\\(\\) should return string but returns string\\|true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:encodeInUtf8\\(\\) should return string but returns array\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'file\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'line\' might not exist on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: list\\<mixed\\>, object\\?\\: object\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 might not exist on array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:isNewID\\(\\) expects int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$file of static method Document\\:\\:isImage\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$from of function Safe\\\\copy expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$from of function Safe\\\\rename expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$path of function pathinfo expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of static method Toolbox\\:\\:strtolower\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function addslashes expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function rawurlencode expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, string\\|false\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$width of function Safe\\\\imagecreatetruecolor expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\|int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$callback of function usort expects callable\\(mixed, mixed\\)\\: int, \'version_compare\' given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$color of function imagecolortransparent expects int\\|null, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$height of function Safe\\\\imagecreatetruecolor expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function Safe\\\\unpack expects string, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$width of static method Html\\:\\:getImageHtmlTagForDocument\\(\\) expects int, int\\<0, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$height of static method Html\\:\\:getImageHtmlTagForDocument\\(\\) expects int, int\\<0, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$dst_width of function Safe\\\\imagecopyresampled expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#8 \\$dst_height of function Safe\\\\imagecopyresampled expects int, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'cpt\' on array\\<string, float\\|int\\|string\\|null\\>\\|false\\|null\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on mysqli_result\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getManufacturer\\(\\) should return string\\|false but returns non\\-empty\\-array\\<string\\>\\|non\\-falsy\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getProductName\\(\\) should return string\\|false but returns non\\-empty\\-array\\<string\\>\\|non\\-falsy\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$fields on Agent\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 4,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method add\\(\\) on CommonDBTM\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLink\\(\\) on Agent\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Unmanaged\\:\\:convert\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_key_exists expects array, array\\<string, mixed\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method fetch_assoc\\(\\) on bool\\|mysqli_result\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'target_version\' might not exist on array\\{0\\?\\: string, source_version\\?\\: non\\-falsy\\-string, 1\\?\\: non\\-falsy\\-string, target_version\\?\\: non\\-falsy\\-string, 2\\?\\: non\\-falsy\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, float\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$size\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between array\\|int\\|string\\|null and \'\\://\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot use array destructuring on array\\|false\\.$#',
	'identifier' => 'offsetAccess.nonArray',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:delete\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_image_size\\(\\) should return array\\|false but returns array\\{0\\: int\\<0, max\\>, 1\\: int\\<0, max\\>, 2\\: int, 3\\: string, mime\\: string, channels\\: int, bits\\: int\\}\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:post\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 3 might not exist on list\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function array_map expects \\(callable\\(mixed\\)\\: mixed\\)\\|null, array\\{\\$this\\(UploadHandler\\), string\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$image of function Safe\\\\imagerotate expects GdImage, bool\\|GdImage given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$color of function imagecolortransparent expects int\\|null, int\\<0, max\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$image of method UploadHandler\\:\\:gd_set_image_object\\(\\) expects GdImage, bool\\|GdImage given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$index of method UploadHandler\\:\\:handle_form_data\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function substr expects int\\|null, int\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$index of method UploadHandler\\:\\:validate\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$index of method UploadHandler\\:\\:validate_image_file\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$index of method UploadHandler\\:\\:get_file_name\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$content_range of method UploadHandler\\:\\:get_file_name\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Trying to invoke string but it might not be a callable\\.$#',
	'identifier' => 'callable.nonCallable',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and array\\<string\\>\\|string results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access an offset on array\\|Countable\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonDBTM\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'count\' on array\\|string\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'id\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on CommonDropdown\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'name\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'user_dn\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getForeignKeyField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTable\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTableField\\(\\) on CommonDBTM\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getAuthToken\\(\\) should return string\\|false but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getIdByName\\(\\) should return int but returns int\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getSpecificValueToSelect\\(\\) should return string but returns string\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getUsersFromDelegatedGroups\\(\\) should return array\\<int, string\\> but returns array\\<int\\|string, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function Safe\\\\sha1_file expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function file_exists expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:getDatas\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method User\\:\\:getTreeCategoryList\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ID of method User\\:\\:showMyForm\\(\\) expects int, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$copy_index of method User\\:\\:baseComputeCloneName\\(\\) expects int, int\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of static method DropdownTranslation\\:\\:getTranslatedValue\\(\\) expects string, class\\-string\\<CommonDBTM\\>\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects LDAP\\\\Result, array\\|LDAP\\\\Result given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$value of function getEntitiesRestrictCriteria expects \'\'\\|array\\<int\\>\\|int, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(list\\<string\\>\\) does not accept array\\<int\\<0, max\\>, string\\>\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'is_default\' on array\\<string, mixed\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/UserEmail.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonDBTM\\:\\:getById\\(\\) expects int\\|null, int\\|string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ValidatorSubstitute.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ValidatorSubstitute.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method addCell\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getHeaderByName\\(\\) on HTMLTableRow\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Vlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access constant class on CommonITILValidation\\|null\\.$#',
	'identifier' => 'classConstant.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getReasonPhrase\\(\\) on Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getStatusCode\\(\\) on Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on class\\-string\\<CommonITILTask\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call static method getTypeName\\(\\) on class\\-string\\<CommonITILValidation\\>\\|null\\.$#',
	'identifier' => 'staticMethod.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:getParentItemSchema\\(\\) should return array but returns array\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:getSpecificValueToSelect\\(\\) should return string but returns int\\|string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:prepareInputForClone\\(\\) should return array but returns array\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' might not exist on array\\{\\}\\|array\\{name\\: string, parent\\: class\\-string\\<CommonDBTM\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'properties\' might not exist on array\\|null\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'subtypes\' might not exist on array\\{main\\: array\\<class\\-string\\<CommonDBTM\\>, array\\{name\\: string\\}\\>, subtypes\\?\\: array\\<class\\-string\\<CommonDBTM\\>, array\\{\\}\\|array\\{name\\: string, parent\\: class\\-string\\<CommonDBTM\\>\\}\\>\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>\\|null might not exist on array\\<class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>, array\\{main\\: array\\<class\\-string\\<CommonDBTM\\>, array\\{name\\: string\\}\\>, subtypes\\?\\: array\\<class\\-string\\<CommonDBTM\\>, array\\{\\}\\|array\\{name\\: string, parent\\: class\\-string\\<CommonDBTM\\>\\}\\>\\}\\>\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$haystack of function str_starts_with expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of closure expects string, array\\<string, string\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of function getForeignKeyFieldForItemType expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Glpi\\\\Search\\\\SearchOption\\:\\:getOptionsForItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Webhook\\:\\:getAPISchemaBySupportedItemtype\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$schema of method Webhook\\:\\:getDefaultPayloadAsTwigTemplate\\(\\) expects array, array\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$itemtype of method Webhook\\:\\:addParentItemData\\(\\) expects class\\-string\\<CommonDBTM\\>, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ong of method CommonGLPI\\:\\:addStandardTab\\(\\) expects array\\<string, bool\\|string\\>, array\\<string, bool\\|string\\|null\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$form_itemtype of static method CommonGLPI\\:\\:createTabEntry\\(\\) expects class\\-string\\<CommonGLPI\\>\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Possibly invalid array key type array\\<string, string\\>\\|string\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, array\\<int\\|string, array\\<int\\|string, list\\<string\\>\\|string\\>\\|int\\|string\\>\\|class\\-string\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/autoload/CFG_GLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\.\\.\\.\\$arrays of function array_merge expects array, array\\<int\\<0, max\\>\\|string, list\\<string\\>\\|string\\>\\|int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/CFG_GLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getItemForItemtype\\(\\) should return \\(T of CommonDBTM\\)\\|false but returns CommonDBTM\\|false\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/dbutils-aliases.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'class\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/legacy-autoloader.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'plugin\' on non\\-empty\\-array\\|true\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/legacy-autoloader.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
