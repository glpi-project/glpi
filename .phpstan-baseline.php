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
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/commonitilobject_item.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/ajax/dropdownItilActors.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/form/answer.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/itilfollowup.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/ajax/searchoptionvalue.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/ajax/task.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.nonObject
	'message' => '#^Cannot access constant class on object\\|false\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: staticProperty.nonObject
	'message' => '#^Cannot access static property \\$rightname on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method add\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method check\\(\\) on object\\|false\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method delete\\(\\) on object\\|false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method getLinkURL\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method redirectToList\\(\\) on object\\|false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method restore\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method update\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method displayFullPageForItem\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getDefinition\\(\\) on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/asset/asset.form.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilobject_item.form.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitiltask.form.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilvalidation.form.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown.common.form.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdown.common.php',
];
$ignoreErrors[] = [
	// identifier: finally.exitPoint
	'message' => '#^The overwriting exit point is on this line\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/form/access_control.form.php',
];
$ignoreErrors[] = [
	// identifier: finally.exitPoint
	'message' => '#^This throw is overwritten by a different one in the finally block below\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/front/form/access_control.form.php',
];
$ignoreErrors[] = [
	// identifier: finally.exitPoint
	'message' => '#^The overwriting exit point is on this line\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/form/destination/formdestination.form.php',
];
$ignoreErrors[] = [
	// identifier: finally.exitPoint
	'message' => '#^This throw is overwritten by a different one in the finally block below\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/front/form/destination/formdestination.form.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/itilfollowup.form.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/massiveaction.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/front/rule.backup.php',
];
$ignoreErrors[] = [
	// identifier: match.unhandled
	'message' => '#^Match expression does not handle remaining value\\: mixed$#',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.graph.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/ticket.form.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and \'testing\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	// identifier: ternary.alwaysFalse
	'message' => '#^Ternary operator condition is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
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
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0/form.php',
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
	// identifier: arguments.count
	'message' => '#^Method Migration\\:\\:addPostQuery\\(\\) invoked with 4 parameters, 1\\-2 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.1.x_to_9.2.0.php',
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
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
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
	// identifier: return.type
	'message' => '#^Method Auth\\:\\:login\\(\\) should return bool but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Auth\\:\\:validateLogin\\(\\) should return bool but returns int\\.$#',
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
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept false\\.$#',
	'count' => 8,
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
	// identifier: property.nonObject
	'message' => '#^Cannot access property \\$fields on object\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	// identifier: greater.invalid
	'message' => '#^Comparison operation "\\>" between array\\|int and 0 results in an error\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
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
	'message' => '#^Strict comparison using \\=\\=\\= between bool and 0 will always evaluate to false\\.$#',
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
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'connect_string\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	// identifier: empty.offset
	'message' => '#^Offset \'connect_string\' on string in empty\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthMail.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'id\' does not exist on string\\.$#',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between Change\\|Contract\\|Problem\\|Project\\|Ticket and Item_Devices will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Budget.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CableStrand\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CableStrand.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
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
	// identifier: property.notFound
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$fields\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Cluster.php',
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
	'count' => 2,
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between numeric\\-string and \'\' will always evaluate to false\\.$#',
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
	// identifier: staticClassAccess.privateMethod
	'message' => '#^Unsafe call to private method CommonGLPI\\:\\:getTabIconClass\\(\\) through static\\:\\:\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	// identifier: smallerOrEqual.alwaysFalse
	'message' => '#^Comparison operation "\\<\\=" between int\\<1, max\\> and 0 is always false\\.$#',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$withtemplate \\(int\\) of method CommonITILCost\\:\\:showForObject\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$params \\(array\\{\\}\\) of method CommonITILObject\\:\\:getCommonDatatableColumns\\(\\) is incompatible with type array\\{ticket_stats\\: bool\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$params \\(array\\{\\}\\) of method CommonITILObject\\:\\:getDatatableEntries\\(\\) is incompatible with type array\\{ticket_stats\\: bool\\}\\.$#',
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
	'message' => '#^Strict comparison using \\=\\=\\= between false and int\\|string\\|null will always evaluate to false\\.$#',
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
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonDBRelation\\:\\:getLinkedTo\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
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
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with int will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTSTAMP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$RRULE\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$STATUS\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$CREATED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$CREATED\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTAMP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$STATUS\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
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
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	// identifier: arguments.count
	'message' => '#^Method CommonITILValidation\\:\\:getItilObjectItemType\\(\\) invoked with 1 parameter, 0 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidationCron.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonDBTM\\:\\:getClosedStatusArray\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 0 is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$itemtype \\(int\\) of method CommonItilObject_Item\\:\\:dropdownMyDevices\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method CommonItilObject_Item\\:\\:displayItemAddForm\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method CommonItilObject_Item\\:\\:dropdown\\(\\) should return int\\|string\\|false but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @param has invalid value \\(integer type \\$obj_id ITIL object on which the used item are attached\\)\\: Unexpected token "type", expected variable at offset 76$#',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(Computer\\) and PDU will always evaluate to false\\.$#',
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
	'message' => '#^Offset \'contact\' on array\\{\\}\\|array\\{states_id\\?\\: mixed, locations_id\\?\\: mixed\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'contact_num\' on array\\{\\}\\|array\\{states_id\\?\\: mixed, locations_id\\?\\: mixed\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'groups_id\' on array\\{\\}\\|array\\{states_id\\?\\: mixed, locations_id\\?\\: mixed\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'users_id\' on array\\{\\}\\|array\\{states_id\\?\\: mixed, locations_id\\?\\: mixed\\} in isset\\(\\) does not exist\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Config\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$expanded_info \\? array\\<string, \\{name\\: string, dark\\: boolean\\}\\> \\: array\\<string, string\\>\\)\\: Unexpected token "\\$expanded_info", expected type at offset 154$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$is_deleted \\(int\\) of method Consumable\\:\\:getMassiveActionsForItemtype\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method ContractCost\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ContractCost.php',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#4 \\$options \\(array\\{\\}\\) of method CronTask\\:\\:register\\(\\) is incompatible with type array\\{state\\: 0\\|1\\|2, mode\\: 1\\|2, allowmode\\: int, hourmin\\: int, hourmax\\: int, logs_lifetime\\: int, param\\: int, comment\\: string\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	// identifier: match.unreachable
	'message' => '#^Match arm is unreachable because previous comparison is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'allowmode\'\\|\'comment\'\\|\'hourmax\'\\|\'hourmin\'\\|\'logs_lifetime\'\\|\'mode\'\\|\'param\'\\|\'state\' on array\\{state\\: 0\\|1\\|2, mode\\: 1\\|2, allowmode\\: int, hourmin\\: int, hourmax\\: int, logs_lifetime\\: int, param\\: int, comment\\: string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method CronTaskLog\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTaskLog.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Access to property \\$connected on an unknown class DB\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Access to property \\$connected on an unknown class DBSlave\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Access to property \\$dbhost on an unknown class DBSlave\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method doQuery\\(\\) on an unknown class DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method doQuery\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method error\\(\\) on an unknown class DB\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method error\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getGlobalVariables\\(\\) on an unknown class DB\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method numrows\\(\\) on an unknown class DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method numrows\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method request\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method result\\(\\) on an unknown class DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method result\\(\\) on an unknown class DBSlave\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: property.nonObject
	'message' => '#^Cannot access property \\$first_connection on null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#11 \\$config_dir \\(null\\) of method DBConnection\\:\\:createMainConfig\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#11 \\$config_dir \\(null\\) of method DBConnection\\:\\:createSlaveConnectionFile\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$config_dir \\(null\\) of method DBConnection\\:\\:updateConfigProperties\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#4 \\$config_dir \\(null\\) of method DBConnection\\:\\:updateConfigProperty\\(\\) is incompatible with type string\\.$#',
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
	// identifier: return.missing
	'message' => '#^Method DBConnection\\:\\:showAllReplicateDelay\\(\\) should return string\\|null but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$no_display \\? string \\: null\\)\\: Unexpected token "\\$no_display", expected type at offset 236$#',
	'count' => 1,
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
	'message' => '#^Instanceof between string and Glpi\\\\DBAL\\\\QueryExpression will always evaluate to false\\.$#',
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
	'message' => '#^Instanceof between array\\|string and Glpi\\\\DBAL\\\\QueryExpression will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between string and Glpi\\\\DBAL\\\\QueryExpression will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between string and Glpi\\\\DBAL\\\\QuerySubQuery will always evaluate to false\\.$#',
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
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(DCRoom\\) and PDU will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
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
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.nonArray
	'message' => '#^Cannot use array destructuring on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$plugins_dirs \\(null\\) of method DbUtils\\:\\:fixItemtypeCase\\(\\) is incompatible with type array\\.$#',
	'count' => 1,
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
	'message' => '#^Method DbUtils\\:\\:getTreeValueCompleteName\\(\\) should return string but returns array\\<string, mixed\\>\\.$#',
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
	'message' => '#^Strict comparison using \\=\\=\\= between bool and \'auto\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between int\\<1, max\\> and 0 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between non\\-empty\\-array\\<int\\>\\|int\\|numeric\\-string and null will always evaluate to false\\.$#',
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
	'message' => '#^Method DisplayPreference\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method DisplayPreference\\:\\:showConfigForm\\(\\) never returns void so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type array\\|null is incompatible with native type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$return_response \\? Response \\: void\\)\\: Unexpected token "\\$return_response", expected type at offset 137$#',
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
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonDBTM\\:\\:getAdditionalField\\(\\)\\.$#',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(Enclosure\\) and PDU will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
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
	// identifier: return.void
	'message' => '#^Method Entity\\:\\:showUiCustomizationOptions\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$val \\=\\=\\= null \\? array\\<int\\|string, string\\> \\: string\\)\\: Unexpected token "\\$val", expected type at offset 275$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between int\\<0, max\\> and \'\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Fieldblacklist\\:\\:isFieldBlacklisted\\(\\) should return true but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	// identifier: property.unused
	'message' => '#^Property GLPI\\:\\:\\$error_handler is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPI.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$config_dir \\(null\\) of method GLPIKey\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method GLPIKey\\:\\:keyExists\\(\\) should return string but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Mime\\\\Header\\\\HeaderInterface\\:\\:getAddresses\\(\\)\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
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
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:\\$response \\(DOMDocument\\) does not accept array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	// identifier: catch.neverThrown
	'message' => '#^Dead catch \\- Glpi\\\\Exception\\\\PasswordTooWeakException is never thrown in the try block\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:applyMassiveAction\\(\\) with return type void returns array\\|null but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) should return array\\<bool\\>\\|bool\\|void but returns array\\<int\\<0, max\\>, array\\<int\\|string, mixed\\>\\>\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getActiveProfile\\(\\) should return int but returns array\\<string, mixed\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:getItemtype\\(\\) should return bool but returns string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) with return type void returns string but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Glpi\\\\Api\\\\APIRest\\:\\:parseIncomingParams\\(\\) should be compatible with return type \\(string\\) of method Glpi\\\\Api\\\\API\\:\\:parseIncomingParams\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\:\\:getKnownSchema\\(\\) should return array\\|null but returns Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AbstractController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\|\\(Closure\\)\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ComponentController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:authorize\\(\\) should return Glpi\\\\Http\\\\Response but returns Psr\\\\Http\\\\Message\\\\ResponseInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\<string, array\\<string, array\\<string, string\\>\\|string\\>\\|string\\>\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:token\\(\\) should return Glpi\\\\Http\\\\Response but returns Psr\\\\Http\\\\Message\\\\ResponseInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @var has invalid value \\(\\{name\\: string, default\\: mixed\\}\\[\\] \\$allowed_keys_mapping\\)\\: Unexpected token "\\{", expected type at offset 9$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\DropdownController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/DropdownController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<int\\|string, array\\|\\(Closure\\)\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getSubitemLinkFields\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getSubitemSchemaFor\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getModelClass\\(\\) on an unknown class Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getTypeClass\\(\\) on an unknown class Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method isEntityAssign\\(\\) on an unknown class Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method isField\\(\\) on an unknown class Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method maybeDeleted\\(\\) on an unknown class Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ManagementController\\:\\:getManagementTypes\\(\\) has invalid return type Glpi\\\\Api\\\\HL\\\\Controller\\\\CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ManagementController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<int\\|string, array\\<string, mixed\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'schema_name\' does not exist on string\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'version_introduced\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ProjectController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\|\\(Closure\\)\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ProjectController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\<string, array\\<string, array\\|string\\>\\|string\\>\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\RuleController\\:\\:getRawKnownSchemas\\(\\) should return array\\<string, Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\> but returns array\\<string, array\\<string, array\\<string, array\\>\\|string\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'fields\' on string on left side of \\?\\? does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function array_key_exists\\(\\) with \'x\\-itemtype\' and Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:getUnionSchema\\(\\) should return array\\{x\\-subtypes\\: array\\{schema_name\\: string, itemtype\\: string\\}, type\\: \'object\', properties\\: array\\} but returns array\\{x\\-subtypes\\: non\\-empty\\-array\\<int\\<0, max\\>, array\\{schema_name\\: string, itemtype\\: mixed\\}\\>, type\\: \'object\', properties\\: mixed\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:validateTypeAndFormat\\(\\) should return bool but returns string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/DebugRequestMiddleware.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'parameters\' on array\\{tags\\: array\\<string\\>, responses\\: array, description\\?\\: string, parameters\\: non\\-empty\\-array\\<string, array\\{name\\: \'Accept\\-Language\', in\\: \'header\', description\\: \'The language to use…\', schema\\: array\\{type\\: \'string\'\\}, examples\\: array\\{English_GB\\: array\\{value\\: \'en_GB\', summary\\: \'English \\(United…\'\\}, French_FR\\: array\\{value\\: \'fr_FR\', summary\\: \'French \\(France\\)\'\\}, Portuguese_BR\\: array\\{value\\: \'pt_BR\', summary\\: \'Portuguese \\(Brazil\\)\'\\}\\}\\}\\|array\\{name\\: string, in\\: string, description\\: string, required\\?\\: \'true\'\\|bool, schema\\?\\: mixed\\}\\>, requestBody\\?\\: array\\{content\\: array\\{application/json\\: array\\{schema\\: array\\{type\\: string, format\\?\\: string, pattern\\?\\: string, properties\\?\\: array\\<string, array\\{type\\: string, format\\?\\: string\\}\\>\\}\\}\\}\\}, security\\: array\\<array\\<string, array\\<string, mixed\\>\\>\\>\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	// identifier: smaller.alwaysFalse
	'message' => '#^Comparison operation "\\<" between 0 and 0 is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Lexer.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-type RoutePathCacheHint has invalid value\\: Unexpected token "\\{", expected type at offset 40$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type mixed is not subtype of native type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:doRequestMiddleware\\(\\) never returns Glpi\\\\Http\\\\Response so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:resumeSession\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset 0 does not exist on array\\<class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset 1 does not exist on array\\<class\\-string\\<Glpi\\\\Api\\\\HL\\\\Controller\\\\AbstractController\\>, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ConfigurationConstants.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'GLPI_AJAX_DASHBOARD\'\\|\'GLPI_ALLOW_IFRAME…\'\\|\'GLPI_CACHE_DIR\'\\|\'GLPI_CALDAV_IMPORT…\'\\|\'GLPI_CENTRAL…\'\\|\'GLPI_CONFIG_DIR\'\\|\'GLPI_CRON_DIR\'\\|\'GLPI_DEMO_MODE\'\\|\'GLPI_DISABLE_ONLY…\'\\|\'GLPI_DOC_DIR\'\\|\'GLPI_DOCUMENTATION…\'\\|\'GLPI_DUMP_DIR\'\\|\'GLPI_ENVIRONMENT…\'\\|\'GLPI_GRAPH_DIR\'\\|\'GLPI_INSTALL_MODE\'\\|\'GLPI_INVENTORY_DIR\'\\|\'GLPI_LOCAL_I18N_DIR\'\\|\'GLPI_LOCK_DIR\'\\|\'GLPI_LOG_DIR\'\\|\'GLPI_MARKETPLACE…\'\\|\'GLPI_MARKETPLACE_DIR\'\\|\'GLPI_NETWORK_MAIL\'\\|\'GLPI_NETWORK…\'\\|\'GLPI_PICTURE_DIR\'\\|\'GLPI_PLUGIN_DOC_DIR\'\\|\'GLPI_RSS_DIR\'\\|\'GLPI_SERVERSIDE_URL…\'\\|\'GLPI_SESSION_DIR\'\\|\'GLPI_TELEMETRY_URI\'\\|\'GLPI_TEXT_MAXSIZE\'\\|\'GLPI_THEMES_DIR\'\\|\'GLPI_TMP_DIR\'\\|\'GLPI_UPLOAD_DIR\'\\|\'GLPI_USER_AGENT…\'\\|\'GLPI_VAR_DIR\'\\|\'PLUGINS_DIRECTORIES\' on array\\{\\} on left side of \\?\\? does not exist\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/ConfigurationConstants.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$env \\(null\\) of method Glpi\\\\Application\\\\ErrorHandler\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'Code\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'Message\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Application\\\\ErrorHandler\\:\\:\\$exit_code is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset \'comment\' does not exist on string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/ItemtypeExtension.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$cachedir \\(null\\) of method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and 2 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:getById\\(\\) should return static\\(Glpi\\\\Asset\\\\Asset\\)\\|false but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$definition$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$profiles$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Asset\\\\AssetModel\\:\\:getById\\(\\) should return static\\(Glpi\\\\Asset\\\\AssetModel\\)\\|false but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetModel.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Asset\\\\AssetType\\:\\:getById\\(\\) should return static\\(Glpi\\\\Asset\\\\AssetType\\)\\|false but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetType.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.nonObject
	'message' => '#^Cannot access constant class on CommonDBTM\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$source_itemtype$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/AbstractCapacity.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$classname$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/CapacityInterface.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$config_dir \\(null\\) of method Glpi\\\\Cache\\\\CacheManager\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$cache_dir \\(null\\) of method Glpi\\\\Cache\\\\CacheManager\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:\\$fields\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VCalendar\\:\\:\\$PRODID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:add\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:can\\(\\)\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:delete\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getType\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isField\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:isNewItem\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:update\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Sabre\\\\VObject\\\\Document\\:\\:getBaseComponent\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getPrincipalByPath\\(\\) should return array but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: return.empty
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\Browser\\:\\:httpGet\\(\\) should return bool but empty return statement found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and \'development\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Console\\\\AbstractCommand\\:\\:\\$progress_bar \\(Symfony\\\\Component\\\\Console\\\\Helper\\\\ProgressBar\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Console\\\\Application\\:\\:\\$db \\(DBmysql\\) does not accept DB\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	// identifier: property.unused
	'message' => '#^Property Glpi\\\\Console\\\\Application\\:\\:\\$error_handler is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:shouldSetDBConfig\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	// identifier: match.unhandled
	'message' => '#^Match expression does not handle remaining value\\: mixed$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getFallbackRoomId\\(\\) never returns float so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:getImportErrorsVerbosity\\(\\) never returns float so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:isAlreadyInstalled\\(\\) should return array but returns bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Auth\\:\\:\\$auth_succeded \\(int\\) does not accept true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/AbstractUserCommand.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Console\\\\Helper\\\\HelperInterface\\:\\:ask\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/GrantCommand.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/ApiController.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and \'development\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ErrorController.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/IndexController.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\Controller\\\\LegacyFileLoadController\\:\\:getRequest\\(\\) never returns null so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/StatusController.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/StatCsvExport.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DBAL/QueryExpression.php',
];
$ignoreErrors[] = [
	// identifier: parameter.phpDocType
	'message' => '#^PHPDoc tag @param for parameter \\$interval_unit with type Glpi\\\\DBAL\\\\QueryExpression\\|int\\|string is not subtype of native type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DBAL/QueryFunction.php',
];
$ignoreErrors[] = [
	// identifier: match.unhandled
	'message' => '#^Match expression does not handle remaining value\\: string$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonDBVisible\\:\\:getVisibilityCriteria\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: smaller.alwaysTrue
	'message' => '#^Comparison operation "\\<" between 0 and 1 is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Static method Glpi\\\\Dashboard\\\\Provider\\:\\:getSearchOptionID\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:getTabNameForItem\\(\\) should return string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Form\\\\AnswersSet\\:\\:getTabNameForItem\\(\\) should return string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AnswersSet.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method getItem\\(\\) on CommonDBTM\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\AbstractFormDestinationType\\:\\:getTabNameForItem\\(\\) should return string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/AbstractFormDestinationType.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method class\\-string\\<Glpi\\\\Form\\\\Destination\\\\AbstractFormDestinationType\\>\\|Glpi\\\\Form\\\\Destination\\\\AbstractFormDestinationType\\:\\:getById\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:getTabNameForItem\\(\\) should return string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type array\\<Glpi\\\\Form\\\\Form\\> is incompatible with native type Glpi\\\\Form\\\\Export\\\\Result\\\\ImportResult\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$history \\(int\\) of method Glpi\\\\Form\\\\Form\\:\\:post_updateItem\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(array\\<Glpi\\\\Form\\\\Comment\\>\\) of method Glpi\\\\Form\\\\Form\\:\\:getComments\\(\\) should be compatible with return type \\(string\\) of method CommonDBTM\\:\\:getComments\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	// identifier: method.nonObject
	'message' => '#^Cannot call method getItem\\(\\) on CommonDBTM\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'extra_data\' on array on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type int is incompatible with native type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:getBlocks\\(\\) has invalid return type Glpi\\\\Form\\\\Block\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:getBlocks\\(\\) should return array\\<Glpi\\\\Form\\\\Block\\> but returns array\\<int, Glpi\\\\Form\\\\Comment\\|Glpi\\\\Form\\\\Question\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(array\\<Glpi\\\\Form\\\\Comment\\>\\) of method Glpi\\\\Form\\\\Section\\:\\:getComments\\(\\) should be compatible with return type \\(string\\) of method CommonDBTM\\:\\:getComments\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Http\\\\LegacyAssetsListener\\:\\:\\$projectDir is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/LegacyAssetsListener.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.nonObject
	'message' => '#^Cannot access constant class on Glpi\\\\Asset\\\\AssetModel\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/LegacyDropdownRouteListener.php',
];
$ignoreErrors[] = [
	// identifier: classConstant.nonObject
	'message' => '#^Cannot access constant class on Glpi\\\\Asset\\\\AssetType\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/LegacyDropdownRouteListener.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Http\\\\LegacyRouterListener\\:\\:\\$projectDir is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\Environment\\:\\:\\$conf is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Environment.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) does not accept Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	// identifier: property.onlyWritten
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\Monitor\\:\\:\\$import_monitor_on_partial_sn is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$index\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysFalse
	'message' => '#^Comparison operation "\\>" between 0 and 1 is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: foreach.emptyArray
	'message' => '#^Empty array passed to foreach\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'Computer\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'NetworkEquipment\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'Phone\' on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Process.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type stdClass supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: method.childParameterType
	'message' => '#^Parameter \\#4 \\$ports_id \\(array\\) of method Glpi\\\\Inventory\\\\Asset\\\\Unmanaged\\:\\:rulepassed\\(\\) should be compatible with parameter \\$ports_id \\(int\\) of method Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:rulepassed\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$request_query \\(string\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:\\$states_id_default \\(int\\) on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$itemtype \\(string\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Volume.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'label\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.offset
	'message' => '#^Offset \'render_callback\' on array\\{label\\: string, item_action\\: bool, render_callback\\: callable\\(\\)\\: mixed, action_callback\\: callable\\(\\)\\: mixed\\} on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleantemp\\(\\) with return type void returns int but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$inventory_content \\(string\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: isset.property
	'message' => '#^Property Glpi\\\\Inventory\\\\Inventory\\:\\:\\$mainasset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Kernel\\\\Kernel\\:\\:getLogDir\\(\\) should return string but returns null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Kernel.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and \'development\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Kernel.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between 0 and 1 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between 0 and 2 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysFalse
	'message' => '#^Right side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\{client_id\\: string, user_id\\: string, scopes\\: string\\[\\]\\}\\)\\: Unexpected token "\\{", expected type at offset 79$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Search\\\\CriteriaFilter\\:\\:getTabNameForItem\\(\\) should return string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$itemtype \\(string\\) of method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:findCriteriaInSession\\(\\) is incompatible with type class\\-string\\<CommonDBTM\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$itemtype \\(string\\) of method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:getDefaultCriteria\\(\\) is incompatible with type class\\-string\\<CommonDBTM\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$field$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$table$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\IWriter\\:\\:setOrientation\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Pdf.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property Glpi\\\\Search\\\\Output\\\\Spreadsheet\\:\\:\\$writer \\(PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\BaseWriter\\) does not accept PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\IWriter\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Pdf.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Spreadsheet.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to static method getAssignableVisiblityCriteria\\(\\) on an unknown class Glpi\\\\Features\\\\AssignableItem\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#4 \\$meta \\(int\\) of method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:giveItem\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#4 \\$meta_type \\(string\\) of method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getSelectCriteria\\(\\) is incompatible with type class\\-string\\<CommonDBTM\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#7 \\$meta_type \\(string\\) of method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getLeftJoinCriteria\\(\\) is incompatible with type class\\-string\\<CommonDBTM\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getHavingCriteria\\(\\) should return array but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getSelectCriteria\\(\\) should return array but returns Glpi\\\\DBAL\\\\QueryExpression\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getWhereCriteria\\(\\) should return array\\|null but returns Glpi\\\\DBAL\\\\QueryExpression\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:giveItem\\(\\) should return string but returns int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{0\\: string, 1\\: string, 2\\: string, 3\\: numeric\\-string, 4\\?\\: string, 5\\?\\: non\\-empty\\-string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{string, string, string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: parameter.phpDocType
	'message' => '#^PHPDoc tag @param for parameter \\$NOT with type string is incompatible with native type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type string is incompatible with native type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: varTag.trait
	'message' => '#^PHPDoc tag @var for variable \\$itemtype has invalid type Glpi\\\\Features\\\\AssignableItem\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: method.staticCall
	'message' => '#^Static call to instance method stdClass\\:\\:addWhere\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between class\\-string\\<CommonDBTM\\> and \'AllAssets\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonGLPI\\:\\:showBrowseView\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Glpi\\\\Search\\\\Input\\\\SearchInputInterface\\:\\:cleanParams\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Glpi\\\\Search\\\\Input\\\\SearchInputInterface\\:\\:manageParams\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Glpi\\\\Search\\\\Input\\\\SearchInputInterface\\:\\:showGenericSearch\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Glpi\\\\Search\\\\Provider\\\\SearchProviderInterface\\:\\:constructData\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method Glpi\\\\Search\\\\Provider\\\\SearchProviderInterface\\:\\:constructSQL\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$digits$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$period$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Glpi\\\\Socket\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Socket.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:getBaselineManifest\\(\\) never returns null so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$directory \\(null\\) of method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:__construct\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Glpi\\\\UI\\\\ThemeManager\\:\\:getCustomThemesDirectory\\(\\) should return string but returns null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/ThemeManager.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$history \\(int\\) of method Group\\:\\:post_updateItem\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.leftAlwaysFalse
	'message' => '#^Left side of && is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Group\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Group\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDropdown\\:\\:showForm\\(\\)$#',
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
	'count' => 4,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_string\\(\\) with array will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$http_response_code \\(int\\) of method Html\\:\\:redirect\\(\\) is incompatible with type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Html\\:\\:getMenuFuzzySearchList\\(\\) should return array\\{url\\: string, title\\: string\\} but returns array\\<int\\<0, max\\>, array\\{url\\: mixed, title\\: mixed\\}\\>\\.$#',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 656$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display is true \\? true \\: string\\)\\: Unexpected token "\\$display", expected type at offset 219$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between float and \'\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between float and \'\\-\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and \'development\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	// identifier: ternary.elseUnreachable
	'message' => '#^Else branch is unreachable because ternary operator condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	// identifier: booleanNot.alwaysFalse
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	// identifier: assign.propertyType
	'message' => '#^Property IPAddress\\:\\:\\$binary \\(array\\<int\\>\\) does not accept string\\.$#',
	'count' => 1,
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
	'message' => '#^Method ITILTemplateField\\:\\:showForITILTemplate\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateField.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$history \\(int\\) of method ITILValidationTemplate\\:\\:post_updateItem\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILValidationTemplate.php',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 264$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Cluster\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_DeviceSimcard.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between 0\\|1\\|2 and \'\' will always evaluate to false\\.$#',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Environment.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method forceGlobalState\\(\\) on an unknown class Glpi\\\\Features\\\\Kanban\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method getFromDB\\(\\) on an unknown class Glpi\\\\Features\\\\Kanban\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Item_Kanban\\:\\:loadStateForItem\\(\\) should return array but returns null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	// identifier: varTag.trait
	'message' => '#^PHPDoc tag @var for variable \\$item has invalid type Glpi\\\\Features\\\\Kanban\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Item_Line\\:\\:prepareInput\\(\\) should return array but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Line.php',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Plug.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Item_Plug\\:\\:showItems\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Plug.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Process.php',
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
	// identifier: return.type
	'message' => '#^Method Item_Ticket\\:\\:displayTabContentForItem\\(\\) should return bool but returns string\\.$#',
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
	// identifier: return.type
	'message' => '#^Method KnowbaseItemTranslation\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
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
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonDBTM\\:\\:getFromDBForTicket\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method LevelAgreement\\:\\:getNextActionForTicket\\(\\) should return OlaLevel_Ticket\\|SlaLevel_Ticket\\|false but returns CommonDBTM\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Static property LevelAgreement\\:\\:\\$levelclass \\(class\\-string\\<LevelAgreementLevel\\>\\) does not accept default value of type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	// identifier: property.defaultValue
	'message' => '#^Static property LevelAgreement\\:\\:\\$levelticketclass \\(class\\-string\\<CommonDBTM\\>\\) does not accept default value of type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$is_deleted \\(int\\) of method Line\\:\\:getMassiveActionsForItemtype\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Line.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Line.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$params \\(array\\{\\}\\) of method Link\\:\\:getAllLinksFor\\(\\) is incompatible with type array\\{id\\: int, name\\: string, link\\: string, data\\: string, open_window\\: bool\\|null\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'data\' on array\\{id\\: int, name\\: string, link\\: string, data\\: string, open_window\\: bool\\|null\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'id\' on array\\{id\\: int, name\\: string, link\\: string, data\\: string, open_window\\: bool\\|null\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'link\' on array\\{id\\: int, name\\: string, link\\: string, data\\: string, open_window\\: bool\\|null\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'name\' on array\\{id\\: int, name\\: string, link\\: string, data\\: string, open_window\\: bool\\|null\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(array\\<int, \\{name\\: string, type\\: string\\}\\>\\)\\: Unexpected token "\\{", expected type at offset 119$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysFalse
	'message' => '#^Result of \\|\\| is always false\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method Link\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between CommonDBTM and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between int\\<1, max\\> and 0 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Lock.php',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(Monitor\\) and PDU will always evaluate to false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(NetworkEquipment\\) and PDU will always evaluate to false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(void\\) of method NetworkName\\:\\:showForm\\(\\) should be compatible with return type \\(bool\\) of method CommonDBTM\\:\\:showForm\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:isDynamic\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: method.childReturnType
	'message' => '#^Return type \\(array\\) of method NetworkPortConnectionLog\\:\\:getTabNameForItem\\(\\) should be compatible with return type \\(string\\) of method CommonGLPI\\:\\:getTabNameForItem\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortConnectionLog.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$val \\!\\=\\= null \\? string \\: array\\)\\: Unexpected token "\\$val", expected type at offset 218$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$val \\!\\=\\= null \\? string \\: array\\)\\: Unexpected token "\\$val", expected type at offset 220$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: offsetAccess.notFound
	'message' => '#^Offset mixed does not exist on array\\{\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method NetworkPortMetrics\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortMetrics.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and \'NetworkEquipment\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort_NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset 2 on array\\{array\\<int, string\\>, array\\<int, non\\-empty\\-string\\>, array\\<int, numeric\\-string\\>, array\\<int, non\\-empty\\-string\\>\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	// identifier: nullCoalesce.property
	'message' => '#^Static property NotificationEventMailing\\:\\:\\$mailer \\(GLPIMailer\\) on left side of \\?\\? is not nullable\\.$#',
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
	// identifier: booleanAnd.leftAlwaysTrue
	'message' => '#^Left side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$extratype \\(\'\'\\) of method Notification_NotificationTemplate\\:\\:getModeClass\\(\\) is incompatible with type \'event\'\\|\'setting\'\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: else.unreachable
	'message' => '#^Else branch is unreachable because previous condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$extratype \\=\\=\\= \'event\' \\? class\\-string\\<NotificationEventInterface\\> \\: class\\-string\\<NotificationSetting\\>\\)\\: Unexpected token "\\$extratype", expected type at offset 235$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$type$#',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel_Ticket.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(PassiveDCEquipment\\) and PDU will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(Peripheral\\) and PDU will always evaluate to false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$RRULE\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysTrue
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Planning\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
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
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$STATUS\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$CREATED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$CREATED\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTAMP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$STATUS\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
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
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/src/Plugin.php',
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
	// identifier: parameter.notFound
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$printer$#',
	'count' => 1,
	'path' => __DIR__ . '/src/PrinterLog.php',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getID\\(\\)\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between false and int\\|string\\|null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$withtemplate \\(int\\) of method ProjectCost\\:\\:showForProject\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method ProjectCost\\:\\:showForProject\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectCost.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTEND\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTSTART\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$PERCENT\\-COMPLETE\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$RRULE\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$STATUS\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$CREATED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$CREATED\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTAMP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$STATUS\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonDBTM\\:\\:setVolume\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
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
	// identifier: instanceof.alwaysFalse
	'message' => '#^Instanceof between static\\(Rack\\) and PDU will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
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
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$STATUS\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$CREATED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VEvent\\|Sabre\\\\VObject\\\\Component\\\\VJournal\\|Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VTodo\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$CREATED\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DESCRIPTION\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTEND\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTAMP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DTSTART\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$DUE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$RRULE\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$STATUS\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$SUMMARY\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Node\\:\\:\\$URL\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 219$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getSystemSQLCriteria\\(\\) on \\(int\\|string\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getTypeName\\(\\) on \\(int\\|string\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getTypeName\\(\\) on int\\|string\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: match.unhandled
	'message' => '#^Match expression does not handle remaining value\\: string$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Report\\:\\:getOSInstallCounts\\(\\) should return array\\<string, array\\<int, array\\>\\> but returns array\\<int\\|string, array\\<string, mixed\\>\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: parameter.unresolvableType
	'message' => '#^PHPDoc tag @param for parameter \\$by_itemtype contains unresolvable type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @return has invalid value \\(array\\<class\\-string\\<CommonDBTM\\>, array\\<int, array\\>\\)\\: Unexpected token "\\*/", expected \'\\>\' at offset 74$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$event \\(array\\{\\}\\) of method Reservation\\:\\:updateEvent\\(\\) is incompatible with type array\\{id\\: int, start\\: string, end\\: string\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$options \\(array\\{\\}\\) of method Reservation\\:\\:showForm\\(\\) is incompatible with type array\\{item\\: array\\<int, int\\>, start\\: string, end\\: string\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$is_deleted \\(int\\) of method Reservation\\:\\:getMassiveActionsForItemtype\\(\\) is incompatible with type bool\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$options \\(array\\{\\}\\) of method Reservation\\:\\:computePeriodicities\\(\\) is incompatible with type array\\{type\\: \'day\'\\|\'month\'\\|\'week\', end\\: string, subtype\\?\\: string, days\\?\\: int\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: if.alwaysFalse
	'message' => '#^If condition is always false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method Reservation\\:\\:processMassiveActionsForOneItemtype\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'end\' on array\\{type\\: \'day\'\\|\'month\'\\|\'week\', end\\: string, subtype\\?\\: string, days\\?\\: int\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'item\' on array\\{item\\: array\\<int, int\\>, start\\: string, end\\: string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: isset.offset
	'message' => '#^Offset \'type\' on array\\{type\\: \'day\'\\|\'month\'\\|\'week\', end\\: string, subtype\\?\\: string, days\\?\\: int\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between ReservationItem and false will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between float and 0 will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	// identifier: binaryOp.invalid
	'message' => '#^Binary operation "\\*" between string and 3600 results in an error\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ReservationItem.php',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#1 \\$options \\(array\\{\\}\\) of method RuleAction\\:\\:dropdownActions\\(\\) is incompatible with type array\\{subtype\\: string, name\\: string, field\\: string, value\\?\\: string, alreadyused\\?\\: bool, display\\?\\: bool\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$options \\(array\\{\\}\\) of method RuleAction\\:\\:showForm\\(\\) is incompatible with type array\\{parent\\: Rule\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleAction\\:\\:dropdownActions\\(\\) should return int\\|string but returns false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method RuleCollection\\:\\:exportRulesToXML\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleCollection\\:\\:getRuleClassName\\(\\) should return class\\-string\\<Rule\\> but returns string\\.$#',
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
	'message' => '#^Offset \'entity\' on array\\{0\\?\\: array\\{entity\\: mixed\\}, criteria\\?\\: non\\-empty\\-array\\<int\\<0, max\\>, array\\{id\\: mixed, name\\: mixed, label\\: string, pattern\\: mixed\\}\\>, actions\\?\\: non\\-empty\\-array\\<int\\<0, max\\>, array\\{id\\: mixed, name\\: mixed, label\\: string, value\\: mixed\\}\\>\\}&non\\-empty\\-array in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	// identifier: greaterOrEqual.alwaysTrue
	'message' => '#^Comparison operation "\\>\\=" between int\\<0, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$options \\(array\\{\\}\\) of method RuleCriteria\\:\\:showForm\\(\\) is incompatible with type array\\{parent\\: Rule\\}\\.$#',
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
	// identifier: return.type
	'message' => '#^Method RuleImportAsset\\:\\:displayAdditionalRuleCondition\\(\\) should return false but returns true\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
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
	// identifier: return.type
	'message' => '#^Method RuleImportEntity\\:\\:displayAdditionalRuleCondition\\(\\) should return false but returns true\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/RuleImportEntity.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleMatchedLog\\:\\:getTabNameForItem\\(\\) should return string but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleMatchedLog\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method RuleRight\\:\\:displayAdditionalRuleCondition\\(\\) should return false but returns true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRight.php',
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
	// identifier: return.void
	'message' => '#^Method Search\\:\\:displayData\\(\\) with return type void returns false\\|null but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between string and 0 will always evaluate to false\\.$#',
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
	// identifier: nullCoalesce.expr
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Session\\:\\:haveRight\\(\\) should return bool but returns int\\.$#',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$comment \\(string\\) of method Software\\:\\:putInTrash\\(\\) is incompatible with type comment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
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
	// identifier: class.notFound
	'message' => '#^Parameter \\$comment of method Software\\:\\:putInTrash\\(\\) has invalid type comment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonGLPI\\:\\:getTypes\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#6 \\$value \\(string\\) of method Stat\\:\\:constructEntryValues\\(\\) is incompatible with type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 301$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 621$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$display \\? void \\: string\\)\\: Unexpected token "\\$display", expected type at offset 622$#',
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
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
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
	// identifier: booleanNot.alwaysTrue
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: method.unused
	'message' => '#^Static method Ticket\\:\\:getListForItemSearchOptionsCriteria\\(\\) is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	// identifier: return.void
	'message' => '#^Method TicketTemplate\\:\\:showHelpdeskPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
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
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$allowlist \\(null\\) of method Toolbox\\:\\:isUrlSafe\\(\\) is incompatible with type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#2 \\$level \\(string\\) of method Toolbox\\:\\:log\\(\\) is incompatible with type int\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: parameter.defaultValue
	'message' => '#^Default value of the parameter \\#3 \\$config_dir \\(null\\) of method Toolbox\\:\\:writeConfig\\(\\) is incompatible with type string\\.$#',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$return_response \\? Response \\: void\\)\\: Unexpected token "\\$return_response", expected type at offset 498$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.alwaysFalse
	'message' => '#^Result of && is always false\\.$#',
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
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between null and true will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	// identifier: greater.alwaysTrue
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between array and false will always evaluate to false\\.$#',
	'count' => 2,
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
	// identifier: property.notFound
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$fields\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
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
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/ValidatorSubstitute.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property CommonGLPI\\:\\:\\$fields\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:getSentQueriesSearchParams\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:showCustomHeaders\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:showPayloadEditor\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:showPreviewForm\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:showSecurityForm\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method CommonGLPI\\:\\:showSentQueries\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getKnownSchemas\\(\\) on \\(int\\|string\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: staticMethod.nonObject
	'message' => '#^Cannot call static method getTypeName\\(\\) on int\\|string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Webhook\\:\\:getTabNameForItem\\(\\) should return string but returns array\\<int, string\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: return.phpDocType
	'message' => '#^PHPDoc tag @return with type bool is incompatible with native type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: booleanAnd.rightAlwaysTrue
	'message' => '#^Right side of && is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: identical.alwaysFalse
	'message' => '#^Strict comparison using \\=\\=\\= between \\(int\\|string\\) and null will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: deadCode.unreachable
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: varTag.differentVariable
	'message' => '#^Variable \\$controller in PHPDoc tag @var does not match any variable in the foreach loop\\: \\$itemtypes, \\$supported_itemtype, \\$type_data$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: varTag.differentVariable
	'message' => '#^Variable \\$list_itemtype in PHPDoc tag @var does not match assigned variable \\$recursive_search\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	// identifier: class.notFound
	'message' => '#^Call to method save_run\\(\\) on an unknown class XHProfRuns_Default\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/XHProf.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Function getDateCriteria\\(\\) should return string but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/dbutils-aliases.php',
];
$ignoreErrors[] = [
	// identifier: function.impossibleType
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/i18n.php',
];
$ignoreErrors[] = [
	// identifier: foreach.nonIterable
	'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/legacy-autoloader.php',
];
$ignoreErrors[] = [
	// identifier: return.unusedType
	'message' => '#^Function glpi_autoload\\(\\) never returns void so it can be removed from the return type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/legacy-autoloader.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
