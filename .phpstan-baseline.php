<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between class\\-string\\<CommonDBTM\\> and \'Planning\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/ajax/central.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' on \\*NEVER\\* in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' on array\\{comment\\: string\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/common.tabs.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/ajax/dropdownItilActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'id\' to string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot assign offset \'impactcontexts_id\' to string\\.$#',
	'identifier' => 'offsetAssign.dimType',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'node_id\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type null\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/ajax/kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/reservations.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/ajax/searchoptionvalue.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 2,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/transfers.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/updateTrackingDeviceType.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/viewsubitem.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonITILCost and CommonITILCost will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonITILTask and CommonITILTask will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitiltask.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonITILValidation and CommonITILValidation will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilvalidation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDropdown and CommonDropdown will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDropdown and CommonDropdown will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown.common.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type class\\-string is not subtype of native type array\\<mixed\\>\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 2,
	'path' => __DIR__ . '/front/item_device.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/front/logout.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/front/notification.tags.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'identifier' => 'greater.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/front/report.infocom.conso.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'identifier' => 'greater.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/front/report.infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in include_once\\(\\) "CAS\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'includeOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between Laminas\\\\I18n\\\\Translator\\\\TranslatorInterface and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/inc/based_config.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getDateCriteria\\(\\) should return string but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/inc/db.function.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/inc/includes.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type DBmysql is not subtype of native type DB\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.80.x_to_0.83.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.83.0_to_0.83.1.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.0_to_0.83.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.83.1_to_0.83.3.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.1_to_0.83.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.x_to_0.84.0.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.x_to_0.84.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.0_to_0.84.1.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.0_to_0.84.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.1_to_0.84.3.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.1_to_0.84.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.3_to_0.84.4.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.3_to_0.84.4.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.5_to_0.84.6.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.5_to_0.84.6.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.85.0_to_0.85.3.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.0_to_0.85.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.3_to_0.85.5.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.3_to_0.85.5.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.85.x_to_0.90.0.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.x_to_0.90.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.0_to_0.90.1.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.0_to_0.90.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.1_to_0.90.5.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.1_to_0.90.5.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.x_to_9.1.0.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.x_to_9.1.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.0_to_10.0.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.1_to_10.0.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.2_to_10.0.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.3_to_10.0.4.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.4_to_10.0.5.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.0_to_9.1.1.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.0_to_9.1.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.1_to_9.1.3.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.1_to_9.1.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 4,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 on array\\{list\\<string\\>, list\\<numeric\\-string\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.1_to_9.5.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type \'PhoneModel\'\\|\'PrinterModel\'\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0/itemtype_pictures.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/install/update.php',
];
$ignoreErrors[] = [
	'message' => '#^Method APIClient\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method APIClient\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\}\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_callable\\(\\) with callable\\(\\)\\: mixed will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:requestAgent\\(\\) should return GuzzleHttp\\\\Psr7\\\\Response but returns Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'action_callback\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:\\$response \\(DOMDocument\\) does not accept array\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 4,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always false\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Glpi\\\\Exception\\\\PasswordTooWeakException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:applyMassiveAction\\(\\) with return type void returns array\\|null but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) should return array\\<bool\\>\\|bool\\|void but returns list\\<array\\<int\\|string, mixed\\>\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getActiveProfile\\(\\) should return int but returns array\\<string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in include_once\\(\\) "/var/www/glpi/inc/downstream\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'includeOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:getItemtype\\(\\) should return bool but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type array is not subtype of native type array\\<int\\|string, array\\<mixed\\>\\|string\\>\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) should be compatible with return type \\(string\\) of method Glpi\\\\Api\\\\API\\:\\:parseIncomingParams\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between false and array will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIXmlrpc.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIXmlrpc.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Api/Deprecated/Computer_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Api/Deprecated/Computer_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Api/Deprecated/Netpoint.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Api/Deprecated/TicketFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between Psr\\\\Log\\\\LoggerInterface and Psr\\\\Log\\\\LoggerInterface will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Code\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Message\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$last_fatal_trace \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$reserved_memory \\(string\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$reserved_memory is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and \'comment\'\\|\'error\' will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/Extension/ItemtypeExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between null and 2 will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'external\' and \'external\' will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:connection_ldap\\(\\) never assigns null to &\\$error so it can be removed from the by\\-ref type\\.$#',
	'identifier' => 'parameterByRef.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:login\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'auths_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'host\' on string in empty\\(\\) does not exist\\.$#',
	'identifier' => 'empty.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept bool\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 5,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$password_expired \\(int\\) does not accept default value of type false\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept bool\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between 2\\|3\\|4 and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 5,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always false\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:ldapStamp2UnixStamp\\(\\) should return int but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthMail\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'connect_string\' on string in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Blacklist\\:\\:\\$blacklists \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Budget\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Budget\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Budget\\:\\:showValuesByEntity\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CableStrand\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:\\$fields\\.$#',
	'identifier' => 'property.notFound',
	'count' => 5,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:can\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:delete\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getType\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isField\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isNewItem\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:update\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getPrincipalByPath\\(\\) should return array but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\Browser\\:\\:httpGet\\(\\) should return bool but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 2,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CartridgeItem\\:\\:cronCartridge\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Central.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Central\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Central.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$checkitem \\(null\\) of method Certificate\\:\\:getSpecificMassiveActions\\(\\) should be compatible with parameter \\$checkitem \\(object\\) of method CommonDBTM\\:\\:getSpecificMassiveActions\\(\\)$#',
	'identifier' => 'method.childParameterType',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\!\\= between \'\' and \'\' will always evaluate to false\\.$#',
	'identifier' => 'notEqual.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'circle\' and null will always evaluate to false\\.$#',
	'identifier' => 'equal.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Change\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:showChildsForItemForm\\(\\) should return bool\\|void but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:getItemsAssociationRequest\\(\\) should return array but returns DBmysqlIterator\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter &\\$item by\\-ref type of method CommonDBConnexity\\:\\:canConnexityItem\\(\\) expects CommonDBTM\\|null, bool\\|CommonDBTM given\\.$#',
	'identifier' => 'parameterByRef.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:processMassiveActionsForOneItemtype\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBRelation\\:\\:\\$_force_log_option \\(int\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 6,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<mixed\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always false\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between int\\<1, max\\> and 0 will always evaluate to false\\.$#',
	'identifier' => 'equal.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:forwardEntityInformations\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:isActive\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:isDeleted\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:isRecursive\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:isTemplate\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:restoreInput\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type class\\-string is not subtype of native type static\\(CommonDBTM\\)\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$right \\(int\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$searchopt \\(array\\) does not accept default value of type false\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always false\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between array\\<mixed\\> and false will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between int and \'is_recursive\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always false\\.$#',
	'identifier' => 'ternary.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Yield can be used only with these return types\\: Generator, Iterator, Traversable, iterable\\.$#',
	'identifier' => 'generator.returnType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBVisible\\:\\:showVisibility\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between \\$this\\(CommonDropdown\\) and IPAddress will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<mixed, mixed\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_integer\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonGLPI\\:\\:getDisplayOptions\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILActor\\:\\:showSupplierNotificationForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILActor\\:\\:showUserNotificationForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 6,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<mixed, mixed\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with int\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with CommonDBTM and \'showForm\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with class\\-string\\<static\\(CommonITILObject\\)\\> and \'getFormUrl\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:computePriority\\(\\) should return int but returns float\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getDefaultActor\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getDefaultActorRightSearch\\(\\) should return bool but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:showSubForm\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'_auto_import\' on string in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset string on string in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type null\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type string\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between CommonDBTM and \'User\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonITILRecurrent is not subtype of native type RecurrentChange\\|TicketRecurrent\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrentCron.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:genericDisplayPlanningItem\\(\\) should return string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:genericPopulatePlanning\\(\\) should return array but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getItemsAsVCalendars\\(\\) should return array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\> but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:post_updateItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:dropdownValidator\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:dropdownValidator\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'contact\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'contact_num\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'groups_id\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'users_id\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(array\\<mixed\\>\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(integer\\: count\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 199 on line 9$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'identifier' => 'booleanOr.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function property_exists\\(\\) with \\$this\\(Glpi\\\\Console\\\\AbstractCommand\\) and \'db\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Console\\\\AbstractCommand\\:\\:\\$progress_bar \\(Symfony\\\\Component\\\\Console\\\\Helper\\\\ProgressBar\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function property_exists\\(\\) with \\$this\\(Glpi\\\\Console\\\\Application\\) and \'db\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Console\\\\Application\\:\\:\\$db \\(DBmysql\\) does not accept DB\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type SplFileInfo is not subtype of native type DirectoryIterator\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:shouldSetDBConfig\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getFallbackRoomId\\(\\) never returns float so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) never returns float so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:isAlreadyInstalled\\(\\) should return array but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/System/CheckRequirementsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract\\:\\:dropdown\\(\\) should return int\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function sprintf\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'function.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTaskLog\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTaskLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$connected on an unknown class DB\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$connected on an unknown class DBSlave\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method request\\(\\) on an unknown class DBSlave\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$first_connection on null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 4,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBConnection\\:\\:getDBSlaveConf\\(\\) should return DBmysql\\|void but returns DBSlave\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBConnection\\:\\:getReadConnection\\(\\) should return DBmysql but returns DBSlave\\.$#',
	'identifier' => 'return.type',
	'count' => 5,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in include_once\\(\\) "/var/www/glpi/config/config_db_slave\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'includeOnce.fileNotFound',
	'count' => 4,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between string and QueryExpression will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<string\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_numeric\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between array\\|string and AbstractQuery will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between array\\|string and QueryExpression will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between string and QueryExpression will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between string and QuerySubQuery will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 4,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DCRoom\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DCRoom\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DCRoom\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<mixed\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Filters/DatesModFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method CommonDBVisible\\:\\:getVisibilityCriteria\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\<" between 0 and 1 is always true\\.$#',
	'identifier' => 'smaller.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with DBmysql and \'close\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot use array destructuring on string\\.$#',
	'identifier' => 'offsetAccess.nonArray',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getHourFromSql\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getTreeLeafValueName\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getTreeValueCompleteName\\(\\) should return string but returns array\\<string, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getTreeValueName\\(\\) should return string but returns array\\<int, int\\|string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between DBmysql and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between \'\\.php\' and bool will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between array\\|string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between bool and \'auto\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between int and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Debug/ProfilerSection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceGraphicCard\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceGraphicCard\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceHardDrive\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceHardDrive\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceMemory\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceMemory\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceProcessor\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceProcessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DeviceProcessor\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceProcessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DisplayPreference\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with CommonDBTM\\|null will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDBTM and CommonDBTM will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:dropdown\\(\\) should return int\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method Document\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document_Item\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document_Item\\:\\:getTypeItemsQueryParams\\(\\) should return DBmysqlIterator but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document_Item\\:\\:showForDocument\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'users_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Domain\\:\\:dropdownDomains\\(\\) with return type void returns int\\<0, max\\> but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Domain\\:\\:dropdownDomains\\(\\) with return type void returns int\\|string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 3,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DomainRecord\\:\\:canCreateItem\\(\\) should return bool but returns int\\<0, max\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecord.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 8,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_numeric\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownConnect\\(\\) should return array\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 3,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownFindNum\\(\\) should return array\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 3,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownName\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownUsers\\(\\) should return array\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownValue\\(\\) should return array\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:show\\(\\) should return int\\|string\\|false but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:showSelectItemFromItemtypes\\(\\) should return int but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'max\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'min\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between float and \'0\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type string supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'field\' on bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'items_id\' on bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'itemtype\' on bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'language\' on bool\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DropdownTranslation\\:\\:getTranslationsForAnItem\\(\\) should return string but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DropdownTranslation\\:\\:hasItemtypeATranslation\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DropdownTranslation\\:\\:post_purgeItem\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(true;\\)\\: Unexpected token ";", expected TOKEN_HORIZONTAL_WS at offset 140 on line 6$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Enclosure\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Enclosure\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Enclosure\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:isRecursive\\(\\) should return int but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:showUiCustomizationOptions\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(int\\) of method Entity\\:\\:isRecursive\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:isRecursive\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Fieldblacklist\\:\\:isFieldBlacklisted\\(\\) should return true but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:keyExists\\(\\) should return string but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot cast list\\<string\\>\\|null to string\\.$#',
	'identifier' => 'cast.string',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression in empty\\(\\) is always falsy\\.$#',
	'identifier' => 'empty.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between null and null will always evaluate to true\\.$#',
	'identifier' => 'identical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Group\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between HTMLTableHeader and HTMLTableHeader will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Property HTMLTableGroup\\:\\:\\$new_headers is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method HTMLTableHeader\\:\\:getCompositeName\\(\\)\\.$#',
	'identifier' => 'method.notFound',
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
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 7,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with bool will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between Glpi\\\\Console\\\\Application and Glpi\\\\Console\\\\Application will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:cleanPostForTextArea\\(\\) should return string but returns array\\<string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:closeForm\\(\\) should return string but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getScssCompilePath\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:showTimeField\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:showTimeField\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) never returns void so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) should return string\\|void but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<int\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 4,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_numeric\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 3,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Method IPNetmask\\:\\:setNetmaskFromString\\(\\) should return false but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPNetwork\\:\\:\\$address \\(IPAddress\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPNetwork\\:\\:\\$data_for_implicit_update \\(array\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPNetwork\\:\\:\\$gateway \\(IPAddress\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPNetwork\\:\\:\\$netmask \\(IPNetmask\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Property IPNetwork\\:\\:\\$networkUpdate \\(bool\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILCategory\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplate\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplate\\:\\:showCentralPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplateHiddenField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateHiddenField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplateMandatoryField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateMandatoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplatePredefinedField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type class\\-string is not subtype of native type TKey of int\\|string\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always true\\.$#',
	'identifier' => 'booleanOr.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/ImpactItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:Amort\\(\\) should return array\\|float but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:showTco\\(\\) should return float but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) does not accept Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with Glpi\\\\Inventory\\\\Asset\\\\MainAsset and \'isPartial\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \\$this\\(Glpi\\\\Inventory\\\\Asset\\\\NetworkEquipment\\) and \'getManagementPorts\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \\$this\\(Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\) and \'handleAggregations\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with Glpi\\\\Inventory\\\\Asset\\\\MainAsset and \'isPartial\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between 0 and 1 is always false\\.$#',
	'identifier' => 'greater.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\!\\= between 0 and 1 will always evaluate to true\\.$#',
	'identifier' => 'notEqual.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between 0 and 2 will always evaluate to false\\.$#',
	'identifier' => 'equal.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Computer\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'NetworkEquipment\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'Phone\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 3,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between string and true will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$ports_id \\(array\\) of method Glpi\\\\Inventory\\\\Asset\\\\Unmanaged\\:\\:rulepassed\\(\\) should be compatible with parameter \\$ports_id \\(int\\) of method Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:rulepassed\\(\\)$#',
	'identifier' => 'method.childParameterType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with Glpi\\\\Inventory\\\\Asset\\\\MainAsset and \'isPartial\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$enabled_inventory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\}\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_callable\\(\\) with callable\\(\\)\\: mixed will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'render_callback\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleantemp\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(void;\\)\\: Unexpected token ";", expected TOKEN_HORIZONTAL_WS at offset 73 on line 4$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$inventory_content \\(string\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$mainasset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Cluster\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_DeviceCamera_ImageFormat\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageFormat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_DeviceCamera_ImageResolution\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageResolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Disk\\:\\:showForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Enclosure\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Kanban\\:\\:loadStateForItem\\(\\) should return array but returns null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Problem\\:\\:showForProblem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Project\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Rack\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type int is not subtype of native type mixed\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_RemoteManagement\\:\\:showForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_SoftwareLicense\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_SoftwareLicense\\:\\:showForLicense\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_SoftwareLicense\\:\\:showForLicenseByEntity\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_SoftwareVersion\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Ticket\\:\\:dropdown\\(\\) should return int\\|string\\|false but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Ticket\\:\\:itemAddForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Ticket\\:\\:showForTicket\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Itil_Project\\:\\:showForItil\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Itil_Project\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Knowbase\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Knowbase.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'ASC\' and \'ASC\' will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:searchForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:showBrowseForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:showForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:showManageForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method KnowbaseItem\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method KnowbaseItemTranslation\\:\\:showVisibility\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItemTranslation\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItemTranslation\\:\\:showFull\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItemTranslation\\:\\:showFull\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(true;\\)\\: Unexpected token ";", expected TOKEN_HORIZONTAL_WS at offset 133 on line 6$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem_Item\\:\\:dropdownAllTypes\\(\\) should return string but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type OlaLevel\\|SlaLevel is not subtype of native type static\\(LevelAgreementLevel\\)\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Link\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method Link\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Link_Itemtype\\:\\:showForLink\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Link_Itemtype.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Location\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Location\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDBTM and CommonDBTM will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Lockedfield.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and string will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between null and string will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Mail\\\\SMTP\\\\OAuthTokenProvider\\:\\:getOauthToken\\(\\) never returns null so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/SMTP/OAuthTokenProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Laminas\\\\Mail\\\\Storage\\\\Message\\:\\:\\$date\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\:\\:getFolders\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\:\\:moveMessage\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getAddressList\\(\\) on array\\|ArrayIterator\\|Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:cronMailgate\\(\\) should return \\-1 but returns 0\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:cronMailgate\\(\\) should return \\-1 but returns 1\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 4,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept default value of type false\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between int and false will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between \'GIT\' and \'CLOUD\' will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array\\<int\\|string, mixed\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with int will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_countable\\(\\) with array\\<int\\|string, array\\<int\\|string, mixed\\>\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getTime\\(\\) on int\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDBTM and CommonDBTM will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$remainings \\(array\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$remainings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Property MassiveAction\\:\\:\\$timer \\(int\\) does not accept Timer\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 8,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between Glpi\\\\Console\\\\Application and Glpi\\\\Console\\\\Application will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and \'comment\'\\|\'error\'\\|\'info\' will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkAlias\\:\\:showForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkAlias\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method NetworkAlias\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkName\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method NetworkName\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:switchInstantiationType\\(\\) should return bool but returns NetworkPortInstantiation\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_NetworkName \\(array\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_NetworkPortConnect \\(array\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_instantiation \\(array\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortDialup\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortDialup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortEthernet\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortEthernet\\:\\:getInstantiationHTMLTableHeaders\\(\\) should return null but returns HTMLTableHeader\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getInstantiationHTMLTableHeaders\\(\\) should return null but returns HTMLTableHeader\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between NetworkPort and CommonDBChild will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'identifier' => 'booleanOr.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and null will always evaluate to false\\.$#',
	'identifier' => 'notIdentical.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between null and null will always evaluate to true\\.$#',
	'identifier' => 'identical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{list\\<string\\>, list\\<\'"\'\\|\'\\\\\'\'\\>, list\\<numeric\\-string\\>, list\\<\'"\'\\|\'\\\\\'\'\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationSetting\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationSetting.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:showForGroup\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTargetCommonITILObject\\:\\:addAdditionnalUserInfo\\(\\) should return 0\\|0\\.0\\|\'\'\\|\'0\'\\|array\\{\\}\\|false\\|null but returns array\\{show_private\\: mixed, is_self_service\\: mixed\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:getTemplateByLanguage\\(\\) should return int\\|false but returns non\\-falsy\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:getName\\(\\) should return string but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:showForNotification\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:showForNotificationTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'from\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 3,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(array\\: empty array if itemtype is not lockable; else returns UNLOCK right\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 144 on line 7$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(bool\\: true if locked\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 290 on line 6$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(bool\\: true if object is locked, and \\$this is filled with record from DB\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 68 on line 4$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(bool\\: true if read\\-only profile lock has been set\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 90 on line 5$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(bool\\|ObjectLock\\: returns ObjectLock if locked, else false\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 123 on line 7$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PDU\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PDU\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PDU\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PassiveDCEquipment\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PassiveDCEquipment\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PassiveDCEquipment\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Pdu_Plug\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdu_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdu_Plug.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always false\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:checkAvailability\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showCentral\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showPlanning\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showSingleLinePlanningFilter\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:updateEventTimes\\(\\) should return bool but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'itemtype\' does not exist on class\\-string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'planning_type\' does not exist on class\\-string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type class\\-string is not subtype of native type array\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 11,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\!\\= between \'\' and \'\' will always evaluate to false\\.$#',
	'identifier' => 'notEqual.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Problem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Problem\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 2,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between 2 and 2 will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile\\:\\:showFormSetupHelpdesk\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$interface \\=\\= \'all\' \\? array\\<string, array\\<string, array\\<string, RightDefinition\\[\\]\\>\\>\\> \\: \\(\\$form \\=\\= \'all\' \\? array\\<string, array\\<string, RightDefinition\\[\\]\\>\\> \\: \\(\\$group \\=\\= \'all\' \\? array\\<string, RightDefinition\\[\\]\\> \\: RightDefinition\\[\\]\\)\\)\\)\\: Unexpected token "\\$interface", expected type at offset 517 on line 12$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Profile\\:\\:\\$profileRight \\(array\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with int\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with class\\-string\\<static\\(Project\\)\\> and \'getFormUrl\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Project\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Project\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method Project\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectCost\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:showFor\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'from\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'items_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RSSFeed\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with non\\-empty\\-list\\<string\\> will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rack\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rack\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rack\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rack\\:\\:showForRoom\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between static\\(Reminder\\) and ExtraVisibilityCriteria will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'plan\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(true;\\)\\: Unexpected token ";", expected TOKEN_HORIZONTAL_WS at offset 125 on line 6$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ReminderTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between ReservationItem and false will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ReservationItem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 8,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method Rule\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between non\\-falsy\\-string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_subclass_of\\(\\) with \'Rule\' and mixed will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always false\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:exportRulesToXML\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'entity\' on array\\{entity\\: true, criterias\\?\\: non\\-empty\\-list\\<mixed\\>, actions\\?\\: non\\-empty\\-list\\<mixed\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>\\=" between int\\<0, max\\> and 0 is always true\\.$#',
	'identifier' => 'greaterOrEqual.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'allow_conditions\' on non\\-empty\\-array in empty\\(\\) always exists and is not falsy\\.$#',
	'identifier' => 'empty.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleDictionnarySoftwareCollection\\:\\:replayRulesOnExistingDB\\(\\) should return int\\|false but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftwareCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Property RuleImportAsset\\:\\:\\$restrict_entity is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleImportAssetCollection\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \\(list\\<string\\>\\|string\\) on string in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRightCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/SNMPCredential.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:croncountAll\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(void\\) of method SavedSearch\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch_Alert\\:\\:showForSavedSearch\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 4,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'Problem\' and \'Problem\' will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between 2 and 2 will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 6,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:displayData\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:displayMetaCriteria\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:displaySearchoption\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:displaySearchoptionValue\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:giveItem\\(\\) should return string but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Search\\:\\:outputData\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 3,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{0\\: string, 1\\: string, 2\\: string, 3\\: numeric\\-string, 4\\?\\: string, 5\\?\\: non\\-empty\\-string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{string, string, string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between 100\\|float\\|string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between int\\|string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function ctype_digit\\(\\) with \\*NEVER\\* will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_int\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with \\*NEVER\\* will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:loadLanguage\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Laminas\\\\I18n\\\\Translator\\\\Translator\\:\\:\\$cache \\(Laminas\\\\Cache\\\\Storage\\\\StorageInterface\\|null\\) does not accept Glpi\\\\Cache\\\\I18nCache\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'identifier' => 'booleanOr.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type CommonDBTM supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Socket\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 2,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 3,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'_system_category\' does not exist on array\\{name\\: string, manufacturers_id\\: int, entities_id\\: int, is_recursive\\: 0\\|1, is_helpdesk_visible\\: mixed\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'condition\' on array\\{table\\: \'glpi…\', joinparams\\: array\\{jointype\\: \'child\'\\}\\} on left side of \\?\\? does not exist\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareLicense\\:\\:cronSoftware\\(\\) should return 0 but returns 1\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareLicense\\:\\:showForSoftware\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareVersion\\:\\:showForSoftware\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stat\\:\\:displayLineGraph\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stat\\:\\:displayPieGraph\\(\\) with return type void returns string but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Supplier\\:\\:showInfocoms\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between DBmysql and DBmysql will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/System/Requirement/InstallationNotOverriden.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/System/Requirement/MysqliMysqlnd.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:cronTelemetry\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:cronTelemetry\\(\\) with return type void returns null but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function array_key_exists\\(\\) with \\(int\\|string\\) and array\\{\\} will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:getDefaultActor\\(\\) should return bool but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:getDefaultActorRightSearch\\(\\) should return bool but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:showForm\\(\\) should return bool but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'_users_id_requester\' on string in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type Group\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 3,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 3,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TicketTemplate\\:\\:showHelpdeskPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between false and array will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with non\\-empty\\-array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with array\\<string\\>\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with array\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_numeric\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_object\\(\\) with array\\<string\\>\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_object\\(\\) with array\\|string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \'Session\' and \'getLoginUserID\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>\\=" between 3 and 3 is always true\\.$#',
	'identifier' => 'greaterOrEqual.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between CommonDBTM and CommonDBTM will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between Glpi\\\\Console\\\\Application and Glpi\\\\Console\\\\Application will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between null and null will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'function\' on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: array\\<mixed\\>, object\\?\\: object\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'redirect_url\' on \\(array\\{url\\: string, content_type\\: string\\|null, http_code\\: int, header_size\\: int, request_size\\: int, filetime\\: int, ssl_verify_result\\: int, redirect_count\\: int, \\.\\.\\.\\}\\|false\\) on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 0 on non\\-empty\\-list\\<string\\> in empty\\(\\) always exists and is not falsy\\.$#',
	'identifier' => 'empty.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter &\\$curl_info by\\-ref type of method Toolbox\\:\\:callCurl\\(\\) expects array\\|null, \\(array\\<string, array\\<int, array\\<string, string\\>\\>\\|float\\|int\\|string\\|null\\>\\|false\\) given\\.$#',
	'identifier' => 'parameterByRef.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'identifier' => 'booleanOr.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and CommonDBTM will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Transfer\\:\\:\\$inittype \\(string\\) does not accept default value of type int\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Update\\:\\:\\$dbversion is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\>\\=" between 1 and 0 is always true\\.$#',
	'identifier' => 'greaterOrEqual.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 18,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_numeric\\(\\) with int will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_string\\(\\) with string will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type array is not subtype of native type array\\{\\}\\|array\\{list\\<string\\>, list\\<string\\>\\}\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always false\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method save_run\\(\\) on an unknown class XHProfRuns_Default\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/XHProf.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
