<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between class\\-string\\<CommonDBTM\\> and \'Planning\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/central.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'comment\' on \\*NEVER\\* in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'comment\' on array\\{comment\\: string\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/comments.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/ajax/dropdownItilActors.php',
];
$ignoreErrors[] = [
	// identifier: offsetAssign.dimType
	'message' => '#^Cannot assign offset \'id\' to string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	// identifier: offsetAssign.dimType
	'message' => '#^Cannot assign offset \'impactcontexts_id\' to string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'node_id\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/ajax/searchoptionvalue.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/logout.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/front/report.infocom.conso.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/front/report.infocom.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Function glpi_autoload\\(\\) never returns void so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/inc/based_config.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Function getDateCriteria\\(\\) should return string but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/inc/db.function.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/inc/includes.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset string on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.80.x_to_0.83.0.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.83.0_to_0.83.1.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.0_to_0.83.1.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.83.1_to_0.83.3.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.1_to_0.83.3.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.x_to_0.84.0.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.0_to_0.84.1.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.0_to_0.84.1.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.1_to_0.84.3.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.1_to_0.84.3.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.84.3_to_0.84.4.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.3_to_0.84.4.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.5_to_0.84.6.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.5_to_0.84.6.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.85.0_to_0.85.3.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.0_to_0.85.3.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.3_to_0.85.5.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.3_to_0.85.5.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.85.x_to_0.90.0.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.85.x_to_0.90.0.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.0_to_0.90.1.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.0_to_0.90.1.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.1_to_0.90.5.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.1_to_0.90.5.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.x_to_9.1.0.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.x_to_9.1.0.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.0_to_10.0.1.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.1_to_10.0.2.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.2_to_10.0.3.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.3_to_10.0.4.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.4_to_10.0.5.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.0_to_9.1.1.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.0_to_9.1.1.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.1_to_9.1.3.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.1_to_9.1.3.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 1 on array\\{array\\<int, string\\>, array\\<int, numeric\\-string\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.1_to_9.5.2.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method APIClient\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method APIClient\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Agent\\:\\:requestAgent\\(\\) should return GuzzleHttp\\\\Psr7\\\\Response but returns Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'action_callback\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:\\$response \\(DOMDocument\\) does not accept array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	// identifier: catch.neverThrown
	'message' => '#^Dead catch \\- Glpi\\\\Exception\\\\PasswordTooWeakException is never thrown in the try block\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:applyMassiveAction\\(\\) with return type void returns array\\|null but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) should return array\\<bool\\>\\|bool\\|void but returns array\\<int\\<0, max\\>, array\\<int\\|string, mixed\\>\\>\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getActiveProfile\\(\\) should return int but returns array\\<string, mixed\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:getItemtype\\(\\) should return bool but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) should be compatible with return type \\(string\\) of method Glpi\\\\Api\\\\API\\:\\:parseIncomingParams\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIXmlrpc.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'Code\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'Message\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$last_fatal_trace \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$reserved_memory \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$reserved_memory is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/Extension/ItemtypeExtension.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and 2 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Auth\\:\\:login\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'auths_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset \'host\' on string in empty\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Property Auth\\:\\:\\$password_expired \\(int\\) does not accept default value of type false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method AuthLDAP\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method AuthLDAP\\:\\:ldapStamp2UnixStamp\\(\\) should return int but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$user_present \\(int\\) does not accept true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method AuthMail\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'connect_string\' on string in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Blacklist\\:\\:\\$blacklists \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Budget\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Budget\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Budget\\:\\:showValuesByEntity\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CableStrand\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:\\$fields\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:can\\(\\)\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:delete\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getType\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isField\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isNewItem\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:update\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getPrincipalByPath\\(\\) should return array but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\Browser\\:\\:httpGet\\(\\) should return bool but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CartridgeItem\\:\\:cronCartridge\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Central.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Central\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Central.php',
];
$ignoreErrors[] = [
	// identifier: method.childParameterType
	'message' => '#^Parameter \\#1 \\$checkitem \\(null\\) of method Certificate\\:\\:getSpecificMassiveActions\\(\\) should be compatible with parameter \\$checkitem \\(object\\) of method CommonDBTM\\:\\:getSpecificMassiveActions\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Change\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysFalse
	'message' => '#^Ternary operator condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBChild\\:\\:showChildsForItemForm\\(\\) should return bool\\|void but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBConnexity\\:\\:getItemsAssociationRequest\\(\\) should return array but returns DBmysqlIterator\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonDBRelation\\:\\:processMassiveActionsForOneItemtype\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property CommonDBRelation\\:\\:\\$_force_log_option \\(int\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonDBTM\\:\\:forwardEntityInformations\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBTM\\:\\:isActive\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBTM\\:\\:isDeleted\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBTM\\:\\:isRecursive\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBTM\\:\\:isTemplate\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonDBTM\\:\\:restoreInput\\(\\) should return array but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property CommonDBTM\\:\\:\\$right \\(int\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Property CommonDBTM\\:\\:\\$searchopt \\(array\\) does not accept default value of type false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysFalse
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between int and \'is_recursive\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysFalse
	'message' => '#^Ternary operator condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: generator.returnType
	'message' => '#^Yield can be used only with these return types\\: Generator, Iterator, Traversable, iterable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonDBVisible\\:\\:showVisibility\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between \\$this\\(CommonDropdown\\) and IPAddress will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_integer\\(\\) with string will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonGLPI\\:\\:getDisplayOptions\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILActor\\:\\:showSupplierNotificationForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILActor\\:\\:showUserNotificationForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysFalse
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with int\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonITILObject\\:\\:computePriority\\(\\) should return int but returns float\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonITILObject\\:\\:getDefaultActor\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonITILObject\\:\\:getDefaultActorRightSearch\\(\\) should return bool but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonITILObject\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILObject\\:\\:showSubForm\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'_auto_import\' on string in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset string on string in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between CommonDBTM and \'User\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CommonITILRecurrent\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILTask\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method CommonITILTask\\:\\:genericDisplayPlanningItem\\(\\) should return string but empty return statement found\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method CommonITILTask\\:\\:genericPopulatePlanning\\(\\) should return array but empty return statement found\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method CommonITILTask\\:\\:getItemsAsVCalendars\\(\\) should return array\\<Sabre\\\\VObject\\\\Component\\\\VCalendar\\> but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILTask\\:\\:post_updateItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILValidation\\:\\:dropdownValidator\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonITILValidation\\:\\:dropdownValidator\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Computer\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Computer\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Computer\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'contact\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'contact_num\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'groups_id\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'users_id\' on array\\{\\}\\|array\\{states_id\\?\\: string, locations_id\\?\\: string\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property CommonDBTM\\:\\:\\$updates \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Config\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysTrue
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Console\\\\AbstractCommand\\:\\:\\$progress_bar \\(Symfony\\\\Component\\\\Console\\\\Helper\\\\ProgressBar\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Console\\\\Application\\:\\:\\$db \\(DBmysql\\) does not accept DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:shouldSetDBConfig\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getFallbackRoomId\\(\\) never returns float so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) never returns float so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:isAlreadyInstalled\\(\\) should return array but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Contract\\:\\:dropdown\\(\\) should return int\\|string but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	// identifier: function.resultUnused
	'message' => '#^Call to function sprintf\\(\\) on a separate line has no effect\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CronTaskLog\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTaskLog.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Access to property \\$connected on an unknown class DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Access to property \\$connected on an unknown class DBSlave\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method request\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: property.nonObject
	'message' => '#^Cannot access property \\$first_connection on null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DBConnection\\:\\:getDBSlaveConf\\(\\) should return DBmysql\\|void but returns DBSlave\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DBConnection\\:\\:getReadConnection\\(\\) should return DBmysql but returns DBSlave\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between string and QueryExpression will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between array\\|string and AbstractQuery will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between array\\|string and QueryExpression will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between string and QueryExpression will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between string and QuerySubQuery will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DCRoom\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DCRoom\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DCRoom\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonDBVisible\\:\\:getVisibilityCriteria\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: smaller.alwaysTrue
	'message' => '#^Comparison operation "\\<" between 0 and 1 is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonArray
	'message' => '#^Cannot use array destructuring on string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DbUtils\\:\\:getHourFromSql\\(\\) should return array but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DbUtils\\:\\:getTreeLeafValueName\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DbUtils\\:\\:getTreeValueCompleteName\\(\\) should return string but returns array\\<string, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DbUtils\\:\\:getTreeValueName\\(\\) should return string but returns array\\<int, int\\|string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between \'\\.php\' and bool will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array\\|string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between bool and \'auto\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceGraphicCard\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceGraphicCard\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceGraphicCard.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceHardDrive\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceHardDrive\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceHardDrive.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceMemory\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceMemory\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceMemory.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceProcessor\\:\\:prepareInputForAdd\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceProcessor.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DeviceProcessor\\:\\:prepareInputForUpdate\\(\\) should return array\\|false but returns float\\|int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DeviceProcessor.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DisplayPreference\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_string\\(\\) with CommonDBTM\\|null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Document\\:\\:dropdown\\(\\) should return int\\|string but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Document\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Document\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Document_Item\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Document_Item\\:\\:getTypeItemsQueryParams\\(\\) should return DBmysqlIterator but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Document_Item\\:\\:showForDocument\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'users_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Domain\\:\\:dropdownDomains\\(\\) with return type void returns int\\<0, max\\> but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Domain\\:\\:dropdownDomains\\(\\) with return type void returns int\\|string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DomainRecord\\:\\:canCreateItem\\(\\) should return bool but returns int\\<0, max\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecord.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Dropdown\\:\\:getDropdownConnect\\(\\) should return array\\|string but empty return statement found\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Dropdown\\:\\:getDropdownFindNum\\(\\) should return array\\|string but empty return statement found\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Dropdown\\:\\:getDropdownName\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Dropdown\\:\\:getDropdownUsers\\(\\) should return array\\|string but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Dropdown\\:\\:getDropdownValue\\(\\) should return array\\|string but empty return statement found\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Dropdown\\:\\:show\\(\\) should return int\\|string\\|false but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Dropdown\\:\\:showSelectItemFromItemtypes\\(\\) should return int but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'max\' on array in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'min\' on array in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysFalse
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between float and \'0\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type string supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonOffsetAccessible
	'message' => '#^Cannot access offset \'field\' on bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonOffsetAccessible
	'message' => '#^Cannot access offset \'items_id\' on bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonOffsetAccessible
	'message' => '#^Cannot access offset \'itemtype\' on bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonOffsetAccessible
	'message' => '#^Cannot access offset \'language\' on bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DropdownTranslation\\:\\:getTranslationsForAnItem\\(\\) should return string but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method DropdownTranslation\\:\\:hasItemtypeATranslation\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method DropdownTranslation\\:\\:post_purgeItem\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Enclosure\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Enclosure\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Enclosure\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Entity\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Entity\\:\\:isRecursive\\(\\) should return int but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Entity\\:\\:showUiCustomizationOptions\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(int\\) of method Entity\\:\\:isRecursive\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:isRecursive\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Fieldblacklist\\:\\:isFieldBlacklisted\\(\\) should return true but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method GLPIKey\\:\\:keyExists\\(\\) should return string but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	// identifier: cast.string
	'message' => '#^Cannot cast array\\<int, string\\>\\|null to string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	// identifier: empty.expr
	'message' => '#^Expression in empty\\(\\) is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$display\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$filesize\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$prefix\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Group\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property HTMLTableGroup\\:\\:\\$new_headers is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method HTMLTableHeader\\:\\:getCompositeName\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property HTMLTableSuperHeader\\:\\:\\$headerSets is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableSuperHeader.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with bool will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Html\\:\\:cleanPostForTextArea\\(\\) should return string but returns array\\<string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Html\\:\\:closeForm\\(\\) should return string but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Html\\:\\:getScssCompilePath\\(\\) should return array but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Html\\:\\:showTimeField\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Html\\:\\:showTimeField\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) never returns void so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) should return string\\|void but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'max\' does not exist on array\\{value\\: mixed, maybeempty\\: mixed, canedit\\: mixed, mindate\\: mixed, maxdate\\: mixed, mintime\\: mixed, maxtime\\: mixed, timestep\\: mixed, \\.\\.\\.\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset \'max\' on array\\{value\\: mixed, maybeempty\\: mixed, canedit\\: mixed, mindate\\: mixed, maxdate\\: mixed, mintime\\: mixed, maxtime\\: mixed, timestep\\: mixed, \\.\\.\\.\\} in empty\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'min\' does not exist on array\\{value\\: mixed, maybeempty\\: mixed, canedit\\: mixed, mindate\\: mixed, maxdate\\: mixed, mintime\\: mixed, maxtime\\: mixed, timestep\\: mixed, \\.\\.\\.\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset \'min\' on array\\{value\\: mixed, maybeempty\\: mixed, canedit\\: mixed, mindate\\: mixed, maxdate\\: mixed, mintime\\: mixed, maxtime\\: mixed, timestep\\: mixed, \\.\\.\\.\\} in empty\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method IPNetmask\\:\\:setNetmaskFromString\\(\\) should return false but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPNetwork\\:\\:\\$address \\(IPAddress\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPNetwork\\:\\:\\$data_for_implicit_update \\(array\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPNetwork\\:\\:\\$gateway \\(IPAddress\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPNetwork\\:\\:\\$netmask \\(IPNetmask\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPNetwork\\:\\:\\$networkUpdate \\(bool\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysFalse
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method ITILCategory\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILCategory.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILFollowup.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method ITILTemplate\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ITILTemplate\\:\\:showCentralPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ITILTemplateHiddenField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateHiddenField.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ITILTemplateMandatoryField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateMandatoryField.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ITILTemplatePredefinedField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysTrue
	'message' => '#^Right side of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ImpactItem.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Infocom\\:\\:Amort\\(\\) should return array\\|float but returns string\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Infocom\\:\\:showTco\\(\\) should return float but returns string\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) does not accept Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$index\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 1 is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'Computer\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'NetworkEquipment\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'Phone\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: method.childParameterType
	'message' => '#^Parameter \\#4 \\$ports_id \\(array\\) of method Glpi\\\\Inventory\\\\Asset\\\\Unmanaged\\:\\:rulepassed\\(\\) should be compatible with parameter \\$ports_id \\(int\\) of method Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:rulepassed\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'label\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'render_callback\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleantemp\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$inventory_content \\(string\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$mainasset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Cluster\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_DeviceCamera_ImageFormat\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageFormat.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_DeviceCamera_ImageResolution\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_DeviceCamera_ImageResolution.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Disk\\:\\:showForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Enclosure\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Item_Kanban\\:\\:loadStateForItem\\(\\) should return array but returns null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_OperatingSystem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Problem\\:\\:showForProblem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Problem.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Project\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Rack\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_RemoteManagement\\:\\:showForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Item_SoftwareLicense\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_SoftwareLicense\\:\\:showForLicense\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_SoftwareLicense\\:\\:showForLicenseByEntity\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Item_SoftwareVersion\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Item_Ticket\\:\\:dropdown\\(\\) should return int\\|string\\|false but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Ticket\\:\\:itemAddForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Ticket\\:\\:showForTicket\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Itil_Project\\:\\:showForItil\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Itil_Project\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Knowbase\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Knowbase.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method KnowbaseItem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItem\\:\\:searchForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItem\\:\\:showBrowseForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItem\\:\\:showForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItem\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItem\\:\\:showManageForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method KnowbaseItem\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysTrue
	'message' => '#^Ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method KnowbaseItemTranslation\\:\\:showVisibility\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method KnowbaseItemTranslation\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItemTranslation\\:\\:showFull\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method KnowbaseItemTranslation\\:\\:showFull\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method KnowbaseItem_Item\\:\\:dropdownAllTypes\\(\\) should return string but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Link\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Link\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Link_Itemtype\\:\\:showForLink\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link_Itemtype.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Location\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Location\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Laminas\\\\Mail\\\\Storage\\\\Message\\:\\:\\$date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\:\\:getFolders\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Laminas\\\\Mail\\\\Storage\\\\AbstractStorage\\:\\:moveMessage\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method getAddressList\\(\\) on array\\|ArrayIterator\\|Laminas\\\\Mail\\\\Header\\\\HeaderInterface\\|string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method MailCollector\\:\\:cronMailgate\\(\\) should return \\-1 but returns 0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method MailCollector\\:\\:cronMailgate\\(\\) should return \\-1 but returns 1\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept default value of type false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property MailCollector\\:\\:\\$body_is_html \\(string\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/View.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Marketplace/View.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with int will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method getTime\\(\\) on int\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property MassiveAction\\:\\:\\$remainings \\(array\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property MassiveAction\\:\\:\\$remainings \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property MassiveAction\\:\\:\\$timer \\(int\\) does not accept Timer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Monitor\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Monitor\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Monitor\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method NetworkAlias\\:\\:showForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method NetworkAlias\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method NetworkAlias\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkAlias.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkEquipment\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkEquipment\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkEquipment\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method NetworkName\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method NetworkName\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPort\\:\\:switchInstantiationType\\(\\) should return bool but returns NetworkPortInstantiation\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_NetworkName \\(array\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_NetworkPortConnect \\(array\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property NetworkPort\\:\\:\\$input_for_instantiation \\(array\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortDialup\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortDialup.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortEthernet\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortEthernet\\:\\:getInstantiationHTMLTableHeaders\\(\\) should return null but returns HTMLTableHeader\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getInstantiationHTMLTable\\(\\) should return null but returns HTMLTableCell\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortFiberchannel\\:\\:getInstantiationHTMLTableHeaders\\(\\) should return null but returns HTMLTableHeader\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysTrue
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	// identifier: notIdentical.alwaysFalse
	'message' => '#^Strict comparison using \\!\\=\\= between null and null will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortMigration.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{array\\<int, string\\>, array\\<int, \'"\'\\|\'\\\\\'\'\\>, array\\<int, numeric\\-string\\>, array\\<int, \'"\'\\|\'\\\\\'\'\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NotificationSetting\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationSetting.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method NotificationTarget\\:\\:showForGroup\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NotificationTargetCommonITILObject\\:\\:addAdditionnalUserInfo\\(\\) should return 0\\|0\\.0\\|\'\'\\|\'0\'\\|array\\{\\}\\|false\\|null but returns array\\{show_private\\: mixed, is_self_service\\: mixed\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'name\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NotificationTemplate\\:\\:getTemplateByLanguage\\(\\) should return int\\|false but returns non\\-falsy\\-string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplateTranslation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Notification_NotificationTemplate\\:\\:getName\\(\\) should return string but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Notification_NotificationTemplate\\:\\:showForNotification\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Notification_NotificationTemplate\\:\\:showForNotificationTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'from\' does not exist on string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PDU\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PDU\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PDU\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PassiveDCEquipment\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PassiveDCEquipment\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method PassiveDCEquipment\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Pdu_Plug\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdu_Plug.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Pdu_Plug.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Peripheral\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Peripheral\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Peripheral\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Planning\\:\\:checkAvailability\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Planning\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Planning\\:\\:showCentral\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Planning\\:\\:showPlanning\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Planning\\:\\:showSingleLinePlanningFilter\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Planning\\:\\:updateEventTimes\\(\\) should return bool but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'itemtype\' does not exist on class\\-string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'planning_type\' does not exist on class\\-string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method PlanningExternalEvent\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:displayPlanningItem\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 11,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Problem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Problem\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Profile\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Profile\\:\\:showFormSetupHelpdesk\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$interface \\=\\= \'all\' \\? array\\<string, array\\<string, array\\<string, RightDefinition\\[\\]\\>\\>\\> \\: \\(\\$form \\=\\= \'all\' \\? array\\<string, array\\<string, RightDefinition\\[\\]\\>\\> \\: \\(\\$group \\=\\= \'all\' \\? array\\<string, RightDefinition\\[\\]\\> \\: RightDefinition\\[\\]\\)\\)\\)\\: Unexpected token "\\$interface", expected type at offset 517$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Profile\\:\\:\\$profileRight \\(array\\) does not accept null\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with int\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Project\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Project\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Project\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ProjectCost\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ProjectTask\\:\\:showFor\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'from\' does not exist on string\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'items_id\' on non\\-empty\\-array in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RSSFeed\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Rack\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Rack\\:\\:isEnclosurePart\\(\\) should return Enclosure\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Rack\\:\\:isRackPart\\(\\) should return Rack\\|false but returns array\\|bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Rack\\:\\:showForRoom\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RefusedEquipment.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Node\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Reminder\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between ReservationItem and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method ReservationItem\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Rule\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Rule\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Rule\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between non\\-falsy\\-string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method RuleCollection\\:\\:exportRulesToXML\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleCollection\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'entity\' on array\\{actions\\?\\: non\\-empty\\-array\\<int\\<0, max\\>, mixed\\>, criterias\\?\\: non\\-empty\\-array\\<int\\<0, max\\>, mixed\\>, entity\\: true\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: greaterOrEqual.alwaysTrue
	'message' => '#^Comparison operation "\\>\\=" between int\\<0, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset \'allow_conditions\' on non\\-empty\\-array in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleDictionnarySoftwareCollection\\:\\:replayRulesOnExistingDB\\(\\) should return int\\|false but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftwareCollection.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property RuleImportAsset\\:\\:\\$restrict_entity is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleImportAssetCollection\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \\(array\\<int, string\\>\\|string\\) on string in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SNMPCredential.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method SavedSearch\\:\\:croncountAll\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method SavedSearch\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method SavedSearch_Alert\\:\\:showForSavedSearch\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_User.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Search\\:\\:displayData\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Search\\:\\:displayMetaCriteria\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Search\\:\\:displaySearchoption\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Search\\:\\:displaySearchoptionValue\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Search\\:\\:giveItem\\(\\) should return string but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Search\\:\\:outputData\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{0\\: string, 1\\: string, 2\\: string, 3\\: numeric\\-string, 4\\?\\: string, 5\\?\\: non\\-empty\\-string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{string, string, string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Session\\:\\:loadLanguage\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Laminas\\\\I18n\\\\Translator\\\\Translator\\:\\:\\$cache \\(Laminas\\\\Cache\\\\Storage\\\\StorageInterface\\|null\\) does not accept Glpi\\\\Cache\\\\I18nCache\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysTrue
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type CommonDBTM supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Socket\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Socket.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'_system_category\' does not exist on array\\{name\\: string, manufacturers_id\\: int, entities_id\\: int, is_recursive\\: 0\\|1, is_helpdesk_visible\\: mixed\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'condition\' on array\\{table\\: \'glpi…\', joinparams\\: array\\{jointype\\: \'child\'\\}\\} on left side of \\?\\? does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method SoftwareLicense\\:\\:cronSoftware\\(\\) should return 0 but returns 1\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method SoftwareLicense\\:\\:showForSoftware\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method SoftwareVersion\\:\\:showForSoftware\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareVersion.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Stat\\:\\:displayLineGraph\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Stat\\:\\:displayPieGraph\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Supplier\\:\\:showInfocoms\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/System/Requirement/MysqliMysqlnd.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Telemetry\\:\\:cronTelemetry\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Telemetry\\:\\:cronTelemetry\\(\\) with return type void returns null but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function array_key_exists\\(\\) with mixed and array\\{\\} will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Ticket\\:\\:getDefaultActor\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Ticket\\:\\:getDefaultActorRightSearch\\(\\) should return bool but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Ticket\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Ticket\\:\\:showForm\\(\\) should return bool but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Ticket\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'_users_id_requester\' on string in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method TicketTemplate\\:\\:showHelpdeskPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between false and array will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket_Ticket.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with array\\<string\\>\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with array\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_object\\(\\) with array\\<string\\>\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_object\\(\\) with array\\|string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: greaterOrEqual.alwaysTrue
	'message' => '#^Comparison operation "\\>\\=" between 3 and 3 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.leftAlwaysTrue
	'message' => '#^Left side of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'function\' on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: array, object\\?\\: object\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'redirect_url\' on \\(array\\{url\\: string, content_type\\: string\\|null, http_code\\: int, header_size\\: int, request_size\\: int, filetime\\: int, ssl_verify_result\\: int, redirect_count\\: int, \\.\\.\\.\\}\\|false\\) on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset 0 on non\\-empty\\-array\\<int, string\\> in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.rightAlwaysTrue
	'message' => '#^Right side of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Property Transfer\\:\\:\\$inittype \\(string\\) does not accept default value of type int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Update\\:\\:\\$dbversion is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 18,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method User\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method save_run\\(\\) on an unknown class XHProfRuns_Default\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/XHProf.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
