<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Function show_rights_dropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/ajax/getAbstractRightDropdownValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Method class@anonymous/install/empty_data\\.php\\:50\\:\\:getEmptyData\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	'message' => '#^Method class@anonymous/install/empty_data\\.php\\:50\\:\\:getEmptyData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/install/empty_data.php',
];
$ignoreErrors[] = [
	'message' => '#^Function acceptLicense\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1000to1001\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.0_to_10.0.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1000to1001\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.0_to_10.0.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10010to10011\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.10_to_10.0.11.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10010to10011\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.10_to_10.0.11.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10011to10012\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.11_to_10.0.12.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10011to10012\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.11_to_10.0.12.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10012to10013\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.12_to_10.0.13.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10012to10013\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.12_to_10.0.13.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10014to10015\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.14_to_10.0.15.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10014to10015\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.14_to_10.0.15.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10015to10016\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.15_to_10.0.16.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10015to10016\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.15_to_10.0.16.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10016to10017\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.16_to_10.0.17.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10016to10017\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.16_to_10.0.17.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10017to10018\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.17_to_10.0.18.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update10017to10018\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.17_to_10.0.18.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1001to1002\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.1_to_10.0.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1001to1002\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.1_to_10.0.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1002to1003\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.2_to_10.0.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1002to1003\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.2_to_10.0.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1003to1004\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.3_to_10.0.4.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1003to1004\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.3_to_10.0.4.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1004to1005\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.4_to_10.0.5.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1004to1005\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.4_to_10.0.5.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1005to1006\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.5_to_10.0.6.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1005to1006\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.5_to_10.0.6.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1006to1007\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.6_to_10.0.7.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1006to1007\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.6_to_10.0.7.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1007to1008\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.7_to_10.0.8.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1007to1008\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.7_to_10.0.8.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1008to1009\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.8_to_10.0.9.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1008to1009\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.8_to_10.0.9.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1009to10010\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.9_to_10.0.10.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update1009to10010\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.9_to_10.0.10.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update100xto1100\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update100xto1100\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function add_itils_validationstep_to_existings_itils\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0/validationsteps.php',
];
$ignoreErrors[] = [
	'message' => '#^Function remove_validation_percent_on_itils\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.x_to_11.0.0/validationsteps.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update940to941\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.4.0_to_9.4.1.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update941to942\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.4.1_to_9.4.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update942to943\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_9.4.2_to_9.4.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update94xto950\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.4.x_to_9.5.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update94xto950\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/install/migrations/update_9.4.x_to_9.5.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update951to952\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.1_to_9.5.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update95xto1000\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function update95xto1000\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.x_to_10.0.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:cronCleanoldagents\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:guessAddresses\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:handleAgentResponse\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:handleAgentResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:createModalWindow\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:createModalWindow\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:createModalWindow\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:updateItemJsCode\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:updateItemJsCode\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:updateItemOnEventJsCode\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ajax\\:\\:updateItemOnEventJsCode\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Ajax.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Appliance\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Appliance_Item_Relation\\:\\:getListJSForApplianceItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Appliance_Item_Relation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:checkAlternateAuthSystems\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:getAlternateAuthSystemsUserLogin\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:getAlternateAuthSystemsUserLogin\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:getAlternateAuthSystemsUserLogin\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:getAlternateAuthSystemsUserLogin\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:isValidLogin\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:login\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:login\\(\\) throws checked exception JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:login\\(\\) throws checked exception RobThree\\\\Auth\\\\TwoFactorAuthException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:login\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:setRememberMeCookie\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:setRememberMeCookie\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:validateLogin\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:validateLogin\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:connectToServer\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:connectToServer\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:getAllUsers\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:getLdapDateValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:guidToString\\(\\) throws checked exception Safe\\\\Exceptions\\\\MiscException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:isValidGuid\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:ldapStamp2UnixStamp\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:ldapStamp2UnixStamp\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:testLDAPSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\LdapException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method AuthLDAP\\:\\:testLDAPSockopen\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BarcodeManager\\:\\:generateQRCode\\(\\) throws checked exception Com\\\\Tecnick\\\\Barcode\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/BarcodeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BarcodeManager\\:\\:generateQRCode\\(\\) throws checked exception Com\\\\Tecnick\\\\Color\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/BarcodeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Blacklist\\:\\:process\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Blacklist\\:\\:processBlackList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cable\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Cable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar\\:\\:computeEndDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar\\:\\:getActiveTimeBetween\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar\\:\\:isHoliday\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar_Holiday\\:\\:getHolidaysForCalendar\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar_Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar_Holiday\\:\\:getHolidaysForCalendar\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Calendar_Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Calendar_Holiday\\:\\:invalidateCalendarCache\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar_Holiday.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cartridge\\:\\:showForCartridgeItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cartridge\\:\\:showForPrinter\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Certificate\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Certificate\\:\\:showForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Change_Ticket\\:\\:processMassiveActionsForOneItemtype\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Cluster\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:affectChild\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:autoinventoryInformation\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:getItemField\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:getItemField\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:getSQLCriteriaToSearchForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBChild\\:\\:showChildsForItemForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBChild.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:canConnexity\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:canConnexityItem\\(\\) throws checked exception CommonDBConnexityItemNotFound but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:canConnexityItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:getItemFromArray\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:getItemsForLog\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:processMassiveActionsForOneItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBConnexity\\:\\:showMassiveActionsSubForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:affectRelation\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getFromDBForItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getItemField\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getItemField\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getListForItemParams\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getMemberPosition\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getRelationMassiveActionsPeerForSubForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:getSQLCriteriaToSearchForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:processMassiveActionsForOneItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:rawSearchOptions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:showMassiveActionsSubForm\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBRelation\\:\\:showMassiveActionsSubForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:canUnrecurs\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:cleanRelationData\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:filterValues\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getAutofillMark\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getFromDB\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getFromDBByCrit\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getFromDBByRequest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getItemtypeOrModelPicture\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getModelClassInstance\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getSearchOptionsToAdd\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:getValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:load1NTableData\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:searchOptions\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDBTM\\:\\:update1NTableData\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDCModelDropdown\\:\\:displaySpecificTypeField\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonDropdown\\:\\:isUsed\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonGLPI\\:\\:showTabsContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonGLPI.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILActor\\:\\:post_addItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILActor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:__get\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:__isset\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:__unset\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:addTeamMember\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:checkFieldsConsistency\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:fillInputForBusinessRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getActorObjectForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getAssociatedDocumentsCriteria\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 12,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getDataToDisplayOnKanban\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 22,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getItemsTable\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getKanbanColumns\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getLegacyTimelineActionsHTML\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getRuleCollectionClassInstance\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTaskClassInstance\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTeamMemberForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTimelineItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTimelineItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:getTimelineItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:handleValidationStepThresholdInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:isNew\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:pre_updateInDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:transformActorsInput\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject\\:\\:updateActors\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject_CommonITILObject\\:\\:countLinksByStatus\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILObject_CommonITILObject\\:\\:getLinkedTo\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject_CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:computeNextCreationDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 12,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:computeNextCreationDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:createItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:getCreateTime\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILRecurrent\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:canUpdateItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:getItemInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:getItemInstance\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:prepareInputForUpdate\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILSatisfaction\\:\\:showSatisactionForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILSatisfaction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:addInstanceException\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:createInstanceClone\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:encodeRrule\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getAsVCalendar\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getGroupItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getInputFromVCalendar\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getItilObjectItemInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getPlanInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getRRuleInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getRsetFromRRuleField\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getUserItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getVCalendarForItem\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getVCalendarForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getVCalendarForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:getVCalendarForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:populatePlanning\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:post_addItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:prepareInputForUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:showVeryShort\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILTask\\:\\:test_valid_date\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:addITILValidationStepFromInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:getItilObjectItemInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:getTargetCriteriaForUser\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:post_addItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:post_deleteItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:recomputeItilStatus\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:removeUnsedITILValidationStep\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonITILValidation\\:\\:showSummary\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonItilObject_Item\\:\\:displayItemAddForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonItilObject_Item\\:\\:displayTabContentForItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonItilObject_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonTreeDropdown\\:\\:addSonInParents\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonTreeDropdown\\:\\:cleanParentsSons\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonTreeDropdown\\:\\:prepareInputForUpdate\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CommonTreeDropdown\\:\\:regenerateTreeUnderID\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:checkDbEngine\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:handleSmtpInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:loadLegacyConfiguration\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:post_updateItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:showPerformanceInformations\\(\\) throws checked exception Safe\\\\Exceptions\\\\OpcacheException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:showPerformanceInformations\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:showSystemInfoTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:showSystemInfoTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\ExecException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Config\\:\\:showSystemInfoTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Consumable\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contact_Supplier\\:\\:showForContact\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract\\:\\:cronContract\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Contract_Supplier\\:\\:showForContract\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronCircularlogs\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronCircularlogs\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronGraph\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronSession\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronSession\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronTemp\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronTemp\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:cronTemp\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:getNeedToRun\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:getNeedToRun\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:showForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:signal\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcntlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CronTask\\:\\:start\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcntlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBConnection\\:\\:deleteDBSlaveConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBConnection\\:\\:setConnectionCharset\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBConnection\\:\\:updateConfigProperties\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:beginTransaction\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:buildDelete\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:buildDrop\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:buildUpdate\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:buildUpdateOrInsert\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:checkForDeprecatedTableOptions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:commit\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:connect\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:decodeHtmlSpecialChars\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:doQuery\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:executeStatement\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:getQueriesFromFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:getQueriesFromFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:getTimezones\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:getVersionAndServer\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:isHtmlEncoded\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:prepare\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:query\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:queryOrDie\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:quoteName\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:quoteName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:removeSqlComments\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysql\\:\\:rollBack\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:analyseCriterion\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:analyseFkey\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:analyseJoins\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:buildQuery\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:convertOldRequestArgsToCriteria\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:convertOldRequestArgsToCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:getSql\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:handleFieldsAlias\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:handleOrderClause\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DBmysqlIterator\\:\\:seek\\(\\) throws checked exception OutOfBoundsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DCRoom\\:\\:getFilled\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DCRoom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DatabaseInstance\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:autoName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:exportArrayToDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:fixItemtypeCase\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:fixItemtypeCase\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:fixItemtypeCase\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getAncestorsOf\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getDateCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getDateCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getDbRelations\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getDbRelations\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getExpectedTableNameForClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getItemTypeForTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getItemTypeForTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getPlural\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getSingular\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getSonsOf\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:getTableNameForForeignKeyField\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DbUtils\\:\\:isForeignKeyField\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DefaultFilter\\:\\:getSearchCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DefaultFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DefaultFilter\\:\\:saveFilter\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DefaultFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getAsResponse\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getDownloadLink\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getFromDBbyContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getResizedImagePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getTreeCategoryList\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getTreeCategoryList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:getUploadedFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:isImage\\(\\) throws checked exception Safe\\\\Exceptions\\\\FileinfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:isImage\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:isValidDoc\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:loadAPISessionIfExist\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:moveDocument\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:moveDocument\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:moveUploadedDocument\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:renameForce\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Document\\:\\:showBrowseView\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Document.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DocumentType\\:\\:getUploadableFilePattern\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DocumentType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DomainRecordType\\:\\:decodeFields\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DomainRecordType\\:\\:displaySpecificTypeField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DomainRecordType\\:\\:getDefaults\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DomainRecordType\\:\\:showDataAjaxForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecordType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:dropdownIcons\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:dropdownIcons\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownActors\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownConnect\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownFindNum\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownNumber\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownUsers\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:getDropdownValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:show\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:showFromArray\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:showHours\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Dropdown\\:\\:showTimeStamp\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Enclosure\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:displaySpecificTypeField\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:getUsedConfig\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:handleConfigStrategyFields\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:handleCustomScenesSubmittedFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:post_getFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:post_updateItem\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Entity\\:\\:pre_deleteItem\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:decrypt\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:decryptUsingLegacyKey\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:decryptUsingLegacyKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:encrypt\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:encrypt\\(\\) throws checked exception Safe\\\\Exceptions\\\\SodiumException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:__call\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:__callstatic\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:__set\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:__set\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:buildDsn\\(\\) throws checked exception GuzzleHttp\\\\Exception\\\\GuzzleException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:buildDsn\\(\\) throws checked exception League\\\\OAuth2\\\\Client\\\\Provider\\\\Exception\\\\IdentityProviderException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:buildDsn\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIMailer\\:\\:normalizeLineBreaks\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getGlpiUserAgent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getOffers\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getOffers\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getOffers\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getRegistrationInformations\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getRegistrationInformations\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPINetwork\\:\\:getRegistrationInformations\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIPDF\\:\\:getFontList\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIUploadHandler\\:\\:uploadFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIUploadHandler\\:\\:validate\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIUploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) throws checked exception DOMException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getContentType\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getResponse\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\ZlibException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:getResponse\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:guessMode\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleContentType\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleJSONRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\ZlibException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleRequest\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:handleXMLRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\IconvException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:setMode\\(\\) throws checked exception DOMException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Agent\\\\Communication\\\\Headers\\\\Common\\:\\:getHeaders\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/Headers/Common.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:checkSessionToken\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getHttpBody\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:getMassiveActionParameters\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:handleDepreciation\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:initSession\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:inlineDocumentation\\(\\) throws checked exception League\\\\CommonMark\\\\Exception\\\\CommonMarkException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:inlineDocumentation\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:retrieveSession\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:searchItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:unlockSessionIfPossible\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:userPicture\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:call\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:call\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:call\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:getItemtype\\(\\) throws checked exception ReflectionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:inlineDocumentation\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\APIRest\\:\\:returnResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:addMyEmail\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyDefaultEmail\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyEmail\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyEmails\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyManagedItems\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyPicture\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getMyUsedItems\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:getUserPictureResponse\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AdministrationController\\:\\:me\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AdministrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AssetController\\:\\:getItemInfocom\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\AssetController\\:\\:getItemInfocom\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/AssetController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ComponentController\\:\\:getComponentsOfType\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ComponentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:getAllowedMethodsForMatchedRoute\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:getSession\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:showGettingStarted\\(\\) throws checked exception League\\\\CommonMark\\\\Exception\\\\CommonMarkException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:showGettingStarted\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:showGettingStarted\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\CoreController\\:\\:swaggerOAuthRedirect\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:getITILTimelineItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ITILController\\:\\:removeTeamMember\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ITILController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ManagementController\\:\\:getRawKnownSchemas\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ManagementController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportAssetCharacteristicsStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportAssetCharacteristicsStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportAssetStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportAssetStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportITILStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:exportITILStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:getAssetCharacteristicsStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:getAssetStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:getITILGlobalStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:getITILStats\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:listStatisticReports\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\ReportController\\:\\:listStatisticReports\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Controller\\\\RuleController\\:\\:getRuleInstanceFromRequest\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/RuleController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:castProperties\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Doc\\\\Schema\\:\\:validateTypeAndFormat\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\GraphQLGenerator\\:\\:convertRESTPropertyToGraphQLType\\(\\) throws checked exception GraphQL\\\\Error\\\\InvariantViolation but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/GraphQLGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\GraphQLGenerator\\:\\:convertRESTSchemaToGraphQLSchema\\(\\) throws checked exception GraphQL\\\\Error\\\\InvariantViolation but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/GraphQLGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\CookieAuthMiddleware\\:\\:process\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/CookieAuthMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\DebugResponseMiddleware\\:\\:process\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/DebugResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\DebugResponseMiddleware\\:\\:process\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/DebugResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\DebugResponseMiddleware\\:\\:process\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/DebugResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\IPRestrictionRequestMiddleware\\:\\:isCidrMatch\\(\\) throws checked exception Safe\\\\Exceptions\\\\NetworkException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/IPRestrictionRequestMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Middleware\\\\SecurityResponseMiddleware\\:\\:process\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Middleware/SecurityResponseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:expandGenericPaths\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\OpenAPIGenerator\\:\\:getComponentReference\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/OpenAPIGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RSQL\\\\Lexer\\:\\:tokenize\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Lexer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RSQL\\\\Parser\\:\\:parse\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RSQL\\\\Parser\\:\\:rsqlGroupToArray\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RSQL/Parser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getIDForOtherUniqueFieldBySchema\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getInputParamsBySchema\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\ResourceAccessor\\:\\:getItemFromSchema\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/ResourceAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:compilePath\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:compilePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:fromRouteAttribute\\(\\) throws checked exception ReflectionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:getAttributesFromPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:hydrate\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:invoke\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\RoutePath\\:\\:isValidPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/RoutePath.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:cacheRoutes\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:getRoutesFromCache\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:handleRequest\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:handleRequest\\(\\) throws checked exception ReflectionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:handleRequest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:handleRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:matchAll\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Router\\:\\:startTemporarySession\\(\\) throws checked exception Glpi\\\\Exception\\\\OAuth2KeyException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Router.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getItemRecordPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getSQLFieldForProperty\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getSearchCriteria\\(\\) throws checked exception Glpi\\\\Api\\\\HL\\\\APIException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getSearchResultsBySchema\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:getTableForFKey\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:hydrateRecords\\(\\) throws checked exception Glpi\\\\Api\\\\HL\\\\APIException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\:\\:hydrateRecords\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\\\SearchContext\\:\\:getPrimaryKeyPropertyForJoin\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\\\SearchContext\\:\\:getPrimaryKeyPropertyForJoin\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\\\SearchContext\\:\\:getSchemaTable\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Api\\\\HL\\\\Search\\\\SearchContext\\:\\:getUnionTables\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Search/SearchContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\Environment\\:\\:set\\(\\) throws checked exception Safe\\\\Exceptions\\\\MiscException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\Environment\\:\\:validate\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ImportMapGenerator\\:\\:addModulesToImportMap\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ImportMapGenerator\\:\\:addModulesToImportMap\\(\\) throws checked exception Symfony\\\\Component\\\\Filesystem\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ImportMapGenerator\\:\\:addModulesToImportMap\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ImportMapGenerator\\:\\:generate\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Application/ImportMapGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\ResourcesChecker\\:\\:isSourceCodeMixedOfMultipleVersions\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/ResourcesChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception Safe\\\\Exceptions\\\\MiscException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:computeConstants\\(\\) throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:initLogger\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:setSessionConfiguration\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\SystemConfigurator\\:\\:setSessionConfiguration\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/SystemConfigurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\DocumentExtension\\:\\:getDocumentSize\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/DocumentExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\FrontEndAssetsExtension\\:\\:configJs\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\FrontEndAssetsExtension\\:\\:cssPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\FrontEndAssetsExtension\\:\\:cssPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\FrontEndAssetsExtension\\:\\:importmap\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\PhpExtension\\:\\:phpConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/PhpExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\RoutingExtension\\:\\:path\\(\\) throws checked exception Symfony\\\\Component\\\\Routing\\\\Exception\\\\InvalidParameterException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/RoutingExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\RoutingExtension\\:\\:path\\(\\) throws checked exception Symfony\\\\Component\\\\Routing\\\\Exception\\\\MissingMandatoryParametersException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/RoutingExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\RoutingExtension\\:\\:url\\(\\) throws checked exception Symfony\\\\Component\\\\Routing\\\\Exception\\\\InvalidParameterException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/RoutingExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\RoutingExtension\\:\\:url\\(\\) throws checked exception Symfony\\\\Component\\\\Routing\\\\Exception\\\\MissingMandatoryParametersException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/RoutingExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\Extension\\\\SessionExtension\\:\\:hasItemtypeRight\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/Extension/SessionExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:__construct\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:display\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:display\\(\\) throws checked exception Twig\\\\Error\\\\RuntimeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:display\\(\\) throws checked exception Twig\\\\Error\\\\SyntaxError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\RuntimeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\SyntaxError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:renderFromStringTemplate\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Application\\\\View\\\\TemplateRenderer\\:\\:renderFromStringTemplate\\(\\) throws checked exception Twig\\\\Error\\\\SyntaxError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:getById\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:handleCustomFieldsUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset\\:\\:prepareDefinitionInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:decodeCapacities\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetModelClassInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getAssetTypeClassInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:getDecodedFieldsField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinition\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinitionManager\\:\\:__construct\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinitionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetDefinitionManager\\:\\:autoloadClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetDefinitionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetModel\\:\\:getById\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetModel\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetModel\\:\\:prepareDefinitionInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetType\\:\\:getById\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetType\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\AssetType\\:\\:prepareDefinitionInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/AssetType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Asset_PeripheralAsset\\:\\:unglobalizeItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Asset_PeripheralAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Capacity\\\\AbstractCapacity\\:\\:countPeerItemsUsage\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/AbstractCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Capacity\\\\HasImpactCapacity\\:\\:onCapacityDisabled\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasImpactCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Capacity\\\\HasImpactCapacity\\:\\:onCapacityEnabled\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasImpactCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\Capacity\\\\HasNotepadCapacity\\:\\:onObjectInstanciation\\(\\) throws checked exception ReflectionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/Capacity/HasNotepadCapacity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:cleanDBonPurge\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:getDecodedTranslationsField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:getFieldType\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:post_getFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:prepareInputForAddAndUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldDefinition\\:\\:validateSystemName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldOption\\\\AbstractOption\\:\\:setValue\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldOption/AbstractOption.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\AbstractType\\:\\:formatValueForDB\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/AbstractType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\AbstractType\\:\\:formatValueFromDB\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/AbstractType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\AbstractType\\:\\:setDefaultValue\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/AbstractType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateTimeType\\:\\:formatValueFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateTimeType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateTimeType\\:\\:normalizeValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateTimeType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateTimeType\\:\\:normalizeValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateTimeType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateType\\:\\:formatValueFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateType\\:\\:normalizeValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DateType\\:\\:normalizeValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\CustomFieldType\\\\DropdownType\\:\\:formatValueForDB\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DropdownType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\RuleDictionaryModel\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/RuleDictionaryModel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\RuleDictionaryModelCollection\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/RuleDictionaryModelCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\RuleDictionaryType\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/RuleDictionaryType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Asset\\\\RuleDictionaryTypeCollection\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/RuleDictionaryTypeCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:clearSymfonyCache\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:extractScheme\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getCacheStorageAdapter\\(\\) throws checked exception ErrorException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getCacheStorageAdapter\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getCacheStorageAdapter\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getCacheStorageAdapter\\(\\) throws checked exception Symfony\\\\Component\\\\Cache\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getKnownContexts\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:getKnownContexts\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:isContextValid\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:normalizeNamespace\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:resetAllCaches\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:setConfiguration\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:setConfiguration\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:testConnection\\(\\) throws checked exception ErrorException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:testConnection\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:testConnection\\(\\) throws checked exception Symfony\\\\Component\\\\Cache\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Cache\\\\CacheManager\\:\\:unsetConfiguration\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Cache/CacheManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:convertVCalendarToCalendarObject\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:createCalendar\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:createCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:deleteCalendar\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:deleteCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:deleteCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\Forbidden but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:deleteCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotFound but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:getCalendarItemForPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:getCalendarObjects\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotFound but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:storeCalendarObject\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:storeCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\Forbidden but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:storeCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\UnsupportedMediaType but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:updateCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Calendar\\:\\:updateCalendarObject\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotFound but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:findByUri\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getCalendarItemForPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getGroupMemberSet\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:getGroupMembership\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:searchPrincipals\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:setGroupMemberSet\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:updatePrincipal\\(\\) throws checked exception Sabre\\\\DAV\\\\Exception\\\\NotImplemented but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Node\\\\CalendarRoot\\:\\:getName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Node/CalendarRoot.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\Acl\\:\\:getCalendarItemForPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\Browser\\:\\:getCalendarItemForPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/Browser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CalDAV\\\\Plugin\\\\CalDAV\\:\\:getCalendarItemForPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\AbstractCommand\\:\\:askForConfirmation\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\AbstractCommand\\:\\:askForConfirmation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\AbstractCommand\\:\\:askForConfirmation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\AbstractCommand\\:\\:initialize\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\AbstractCommand\\:\\:outputSessionBufferedMessages\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:__construct\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:__construct\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceCircularReferenceException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:__construct\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:configureIO\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:getCommandName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Application\\:\\:getDefaultInputDefinition\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 12,
	'path' => __DIR__ . '/src/Glpi/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Assets\\\\CleanSoftwareCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Assets/CleanSoftwareCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Assets\\\\CleanSoftwareCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Assets/CleanSoftwareCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Assets\\\\PurgeSoftwareCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Assets/PurgeSoftwareCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Assets\\\\PurgeSoftwareCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Assets/PurgeSoftwareCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:execute\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:initialize\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\CompileScssCommand\\:\\:initialize\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\GenerateCodeManifestCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Build/GenerateCodeManifestCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\GenerateCodeManifestCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/GenerateCodeManifestCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\GenerateCodeManifestCommand\\:\\:generateManifest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/GenerateCodeManifestCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\GenerateCodeManifestCommand\\:\\:generateManifest\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/GenerateCodeManifestCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Build\\\\GenerateCodeManifestCommand\\:\\:generateManifest\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Build/GenerateCodeManifestCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ClearCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ClearCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ClearCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ClearCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ConfigureCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ConfigureCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ConfigureCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\ConfigureCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\DebugCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/DebugCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\DebugCommand\\:\\:execute\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/DebugCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\DebugCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/DebugCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\DebugCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/DebugCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\SetNamespacePrefixCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/SetNamespacePrefixCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\SetNamespacePrefixCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/SetNamespacePrefixCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Cache\\\\SetNamespacePrefixCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Cache/SetNamespacePrefixCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findCoreCommands\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findCoreCommands\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findPluginCommands\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findPluginCommands\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findPluginCommands\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findSymfonyCommands\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\CommandNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findSymfonyCommands\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findSymfonyCommands\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceCircularReferenceException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findSymfonyCommands\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findToolsCommands\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:findToolsCommands\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:getCommandFromFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\CommandLoader\\:\\:getCommandFromFile\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Config\\\\SetCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Config/SetCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:checkTimezonesAvailability\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:configureDatabase\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:configureDatabase\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\AbstractConfigureCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/AbstractConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\CheckSchemaIntegrityCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 10,
	'path' => __DIR__ . '/src/Glpi/Console/Database/CheckSchemaIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\CheckSchemaIntegrityCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/CheckSchemaIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\CheckSchemaIntegrityCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 12,
	'path' => __DIR__ . '/src/Glpi/Console/Database/CheckSchemaIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\ConfigureCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\ConfigureCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/ConfigureCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\EnableTimezonesCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\EnableTimezonesCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:execute\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 10,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:handTelemetryActivation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:handTelemetryActivation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:isInputContainingConfigValues\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:registerTelemetryActivationOptions\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:registerTelemetryActivationOptions\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:checkSchemaIntegrity\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:checkSchemaIntegrity\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:execute\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:getPrettyDbVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:getPrettyDbVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:handTelemetryActivation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:handTelemetryActivation\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:registerTelemetryActivationOptions\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\UpdateCommand\\:\\:registerTelemetryActivationOptions\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Database/UpdateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckDocumentsIntegrityCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckDocumentsIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckDocumentsIntegrityCommand\\:\\:validateDocument\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckDocumentsIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:dumpObjects\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:dumpObjects\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:fixEmailHeadersEncoding\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckHtmlEncodingCommand\\:\\:fixQuoteEntityWithoutSemicolon\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckHtmlEncodingCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:execute\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:initialize\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:initialize\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Diagnostic\\\\CheckSourceCodeIntegrityCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Diagnostic/CheckSourceCodeIntegrityCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Ldap\\\\SynchronizeUsersCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 10,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Ldap\\\\SynchronizeUsersCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 10,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Ldap\\\\SynchronizeUsersCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Ldap\\\\SynchronizeUsersCommand\\:\\:validateInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Maintenance\\\\DisableMaintenanceModeCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Maintenance/DisableMaintenanceModeCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Maintenance\\\\EnableMaintenanceModeCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Maintenance/EnableMaintenanceModeCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Maintenance\\\\EnableMaintenanceModeCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Maintenance/EnableMaintenanceModeCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\AbstractMarketplaceCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/AbstractMarketplaceCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\AbstractMarketplaceCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/AbstractMarketplaceCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\AbstractMarketplaceCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/AbstractMarketplaceCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\DownloadCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/DownloadCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\DownloadCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/DownloadCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\InfoCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/InfoCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\InfoCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/InfoCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\SearchCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/SearchCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Marketplace\\\\SearchCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Marketplace/SearchCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginMigrationCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginMigrationCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginMigrationCommand\\:\\:execute\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginMigrationCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginMigrationCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginMigrationCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:checkPlugin\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:checkPlugin\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:handleImportError\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:handleImportError\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AbstractPluginToCoreCommand\\:\\:storeItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AbstractPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:createApplianceEnvironments\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:createApplianceItems\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:createApplianceRelations\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:createApplianceTypes\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:createAppliances\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:migratePlugin\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:outputImportError\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:updateItemtypes\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\AppliancesPluginToCoreCommand\\:\\:updateProfilesApplianceRights\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/AppliancesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\BuildMissingTimestampsCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/BuildMissingTimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\DatabasesPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DatabasesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\DomainsPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DomainsPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\DynamicRowFormatCommand\\:\\:checkForPrerequisites\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\DynamicRowFormatCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\DynamicRowFormatCommand\\:\\:upgradeRowFormat\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/DynamicRowFormatCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\FormCreatorPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/FormCreatorPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\FormCreatorPluginToCoreCommand\\:\\:getMigration\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/FormCreatorPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\MigrateAllCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MigrateAllCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\MigrateAllCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\CommandNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MigrateAllCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\MigrateAllCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\ExceptionInterface but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MigrateAllCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\MyIsamToInnoDbCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\MyIsamToInnoDbCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/MyIsamToInnoDbCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:checkPlugin\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:checkPlugin\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importItemsSpecifications\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importOtherElements\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importOtherElements\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRackItems\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRackModels\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRackStates\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRackTypes\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRacks\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:importRooms\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:migratePlugin\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:migratePlugin\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\RacksPluginToCoreCommand\\:\\:outputImportError\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/RacksPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\TimestampsCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\TimestampsCommand\\:\\:execute\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\TimestampsCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\TimestampsCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/TimestampsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\UnsignedKeysCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\UnsignedKeysCommand\\:\\:execute\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\UnsignedKeysCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\Utf8mb4Command\\:\\:checkForPrerequisites\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\Utf8mb4Command\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Migration\\\\Utf8mb4Command\\:\\:migrateToUtf8mb4\\(\\) throws checked exception Glpi\\\\Console\\\\Exception\\\\EarlyExitException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\AbstractPluginCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/AbstractPluginCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\AbstractPluginCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/AbstractPluginCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\AbstractPluginCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/AbstractPluginCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\AbstractPluginCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/AbstractPluginCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\AbstractPluginCommand\\:\\:normalizeInput\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/AbstractPluginCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ActivateCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ActivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ActivateCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ActivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\DeactivateCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/DeactivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\DeactivateCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/DeactivateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:canRunInstallMethod\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:getAdditionnalParameters\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:getDirectoryChoiceChoices\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:getDirectoryChoiceChoices\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ListCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ListCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ListCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ListCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ListCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ListCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\ResumeExecutionCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/ResumeExecutionCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\SuspendExecutionCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/SuspendExecutionCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\UninstallCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/UninstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\UninstallCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Plugin/UninstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ProcessSoftwareCategoryRulesCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ProcessSoftwareCategoryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ProcessSoftwareCategoryRulesCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ProcessSoftwareCategoryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ReplayDictionnaryRulesCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ReplayDictionnaryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ReplayDictionnaryRulesCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ReplayDictionnaryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ReplayDictionnaryRulesCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ReplayDictionnaryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Rules\\\\ReplayDictionnaryRulesCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Rules/ReplayDictionnaryRulesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\ChangekeyCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/ChangekeyCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\DisableTFACommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\DisableTFACommand\\:\\:execute\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\DisableTFACommand\\:\\:execute\\(\\) throws checked exception JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\DisableTFACommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Security\\\\DisableTFACommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Security/DisableTFACommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\CheckRequirementsCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/System/CheckRequirementsCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\CheckStatusCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Console/System/CheckStatusCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\CheckStatusCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/System/CheckStatusCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\CheckStatusCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/System/CheckStatusCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\ListServicesCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/System/ListServicesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\System\\\\ListServicesCommand\\:\\:execute\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/System/ListServicesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Task\\\\UnlockCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Task\\\\UnlockCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Task\\\\UnlockCommand\\:\\:validateInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/Task/UnlockCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\AbstractUserCommand\\:\\:askForPassword\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/AbstractUserCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\AbstractUserCommand\\:\\:askForPassword\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/AbstractUserCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\AbstractUserCommand\\:\\:askForPassword\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/AbstractUserCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\AbstractUserCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/AbstractUserCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\CreateCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/CreateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\CreateCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/CreateCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\DeleteCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/DeleteCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\DeleteCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/DeleteCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\DisableCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/DisableCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\DisableCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/DisableCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\EnableCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/EnableCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\EnableCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/EnableCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\GrantCommand\\:\\:askForProfile\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/GrantCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\GrantCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/User/GrantCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\GrantCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Console/User/GrantCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\GrantCommand\\:\\:interact\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/GrantCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\ResetPasswordCommand\\:\\:configure\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Console/User/ResetPasswordCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\User\\\\ResetPasswordCommand\\:\\:execute\\(\\) throws checked exception Symfony\\\\Component\\\\Console\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/User/ResetPasswordCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ContentTemplates\\\\TemplateManager\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/TemplateManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ContentTemplates\\\\TemplateManager\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\RuntimeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/TemplateManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ContentTemplates\\\\TemplateManager\\:\\:render\\(\\) throws checked exception Twig\\\\Error\\\\SyntaxError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/TemplateManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ContentTemplates\\\\TemplateManager\\:\\:validate\\(\\) throws checked exception Twig\\\\Error\\\\LoaderError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/TemplateManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ContentTemplates\\\\TemplateManager\\:\\:validate\\(\\) throws checked exception Twig\\\\Error\\\\RuntimeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ContentTemplates/TemplateManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ApiController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ApiController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ApiController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ApiController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ApiController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Controller/ApiController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\CentralController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/CentralController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Config\\\\Helpdesk\\\\AbstractTileController\\:\\:getAndValidateLinkedItemFromDatabase\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Config/Helpdesk/AbstractTileController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Config\\\\Helpdesk\\\\CopyParentEntityController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Config/Helpdesk/CopyParentEntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Config\\\\Helpdesk\\\\UpdateTileController\\:\\:__invoke\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Config/Helpdesk/UpdateTileController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\DropdownFormController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\DropdownFormController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Controller/DropdownFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ErrorController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Controller/ErrorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\AllowListDropdown\\\\CountUsersController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/AllowListDropdown/CountUsersController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Condition\\\\EditorController\\:\\:validationEditor\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EditorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Condition\\\\EngineController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Condition/EngineController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Destination\\\\AddDestinationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/AddDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Destination\\\\PurgeDestinationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/PurgeDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Destination\\\\UpdateDestinationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Destination/UpdateDestinationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\ExportController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/ExportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Import\\\\Step2PreviewController\\:\\:previewResponse\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step2PreviewController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Import\\\\Step3ResolveIssuesController\\:\\:__invoke\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\AccessDeniedHttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Import/Step3ResolveIssuesController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\QuestionActorsDropdownController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/QuestionActorsDropdownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\RendererController\\:\\:__construct\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/RendererController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\SubmitAnswerController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/SubmitAnswerController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\SubmitAnswerController\\:\\:saveSubmittedAnswers\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/SubmitAnswerController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\TagListController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/TagListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Translation\\\\AddNewFormTranslationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/AddNewFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Translation\\\\DeleteFormTranslationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/DeleteFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\Translation\\\\UpdateFormTranslationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/Translation/UpdateFormTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Form\\\\ValidateAnswerController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Form/ValidateAnswerController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:errorReponse\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:handleDeleteAction\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:handlePurgeAction\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:handleRestoreAction\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:handleUpdateAction\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericAjaxCrudController\\:\\:successResponse\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericAjaxCrudController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericFormController\\:\\:handleFormAction\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\GenericFormController\\:\\:handleFormAction\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/GenericFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\IndexController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Controller/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\IndexController\\:\\:__invoke\\(\\) throws checked exception RobThree\\\\Auth\\\\TwoFactorAuthException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InstallController\\:\\:getProgressInitResponse\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InstallController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InstallController\\:\\:getProgressInitResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InstallController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InstallController\\:\\:getProgressInitResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InstallController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InstallController\\:\\:getProgressInitResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InstallController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InstallController\\:\\:updateDatabase\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/InstallController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InventoryController\\:\\:index\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/InventoryController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\InventoryController\\:\\:refusedEquipement\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/InventoryController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ItemType\\\\Form\\\\AuthMailFormController\\:\\:handleTestAction\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/AuthMailFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ItemType\\\\Form\\\\ContactFormController\\:\\:generateVCard\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/ContactFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ItemType\\\\Form\\\\MailCollectorFormController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/MailCollectorFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ItemType\\\\Form\\\\SavedSearchFormController\\:\\:createNotif\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ItemType/Form/SavedSearchFormController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Knowbase\\\\KnowbaseItemController\\:\\:content\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Knowbase/KnowbaseItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Knowbase\\\\KnowbaseItemController\\:\\:full\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Knowbase/KnowbaseItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Knowbase\\\\KnowbaseItemController\\:\\:search\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Knowbase/KnowbaseItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\LegacyFileLoadController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\LegacyFileLoadController\\:\\:__invoke\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\LegacyFileLoadController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\LegacyFileLoadController\\:\\:getRequest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/LegacyFileLoadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Plugin\\\\LogoController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/Plugin/LogoController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Plugin\\\\LogoController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Plugin/LogoController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ProgressController\\:\\:check\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/ProgressController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ServiceCatalog\\\\IndexController\\:\\:__construct\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\ServiceCatalog\\\\IndexController\\:\\:__invoke\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ServiceCatalog/IndexController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Session\\\\ChangeEntityController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Session/ChangeEntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\Session\\\\ChangeProfileController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/Session/ChangeProfileController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\StatusController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/StatusController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\UI\\\\Illustration\\\\CustomIllustrationController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/UI/Illustration/CustomIllustrationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\UI\\\\Illustration\\\\CustomSceneController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/UI/Illustration/CustomSceneController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\UI\\\\Illustration\\\\UploadController\\:\\:__invoke\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/UI/Illustration/UploadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\UI\\\\Illustration\\\\UploadController\\:\\:__invoke\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Controller/UI/Illustration/UploadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Controller\\\\WellKnownController\\:\\:changePassword\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/WellKnownController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Csv\\\\CsvResponse\\:\\:output\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/CsvResponse.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Csv\\\\CsvResponse\\:\\:output\\(\\) throws checked exception League\\\\Csv\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Csv/CsvResponse.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Csv\\\\CsvResponse\\:\\:output\\(\\) throws checked exception League\\\\Csv\\\\InvalidArgument but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/CsvResponse.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Csv\\\\PlanningCsv\\:\\:getFileContent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/PlanningCsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getCustomObjectClassInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getDecodedProfilesField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getDecodedTranslationsField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getPluralFormsForLanguage\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinition\\:\\:purgeConcreteClassFromDb\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\CustomObject\\\\AbstractDefinitionManager\\:\\:registerAutoload\\(\\) throws checked exception Safe\\\\Exceptions\\\\SplException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/CustomObject/AbstractDefinitionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DBAL\\\\QueryExpression\\:\\:__construct\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DBAL/QueryExpression.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DBAL\\\\QueryFunction\\:\\:__callStatic\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/DBAL/QueryFunction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DBAL\\\\QuerySubQuery\\:\\:__construct\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DBAL/QuerySubQuery.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DBAL\\\\QueryUnion\\:\\:getQuery\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DBAL/QueryUnion.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Dashboard\\:\\:getFromDB\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Dashboard\\:\\:importFromJson\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:articleListItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:averageTicketTimes\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:getObscureNumberForString\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:getTicketsEvolution\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:getTicketsStatus\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:nbTicketsGeneric\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\FakeProvider\\:\\:ticketsOpened\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Filters\\\\AbstractFilter\\:\\:getDatesCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Filters\\\\AbstractFilter\\:\\:getDatesSearchCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Filters\\\\AbstractGroupFilter\\:\\:getCriteria\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractGroupFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Filters\\\\UserTechFilter\\:\\:getCriteria\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/UserTechFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:addGridItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:getAllDasboardCards\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:getAllDashboardCardsCacheKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Grid\\:\\:restoreLastDashboard\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Provider\\:\\:bigNumberItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Provider\\:\\:formatMonthyearDates\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Provider\\:\\:getTicketsEvolution\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:getGradientPalette\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:markdown\\(\\) throws checked exception League\\\\CommonMark\\\\Exception\\\\CommonMarkException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:pie\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:searchShowList\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:searchShowList\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dashboard\\\\Widget\\:\\:searchShowList\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Widget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Debug\\\\Profile\\:\\:getCurrent\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Debug/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DependencyInjection\\\\PluginContainer\\:\\:configureContainerServices\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\DependencyInjection\\\\PluginContainer\\:\\:getContainerConfigurator\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/DependencyInjection/PluginContainer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dropdown\\\\Dropdown\\:\\:getById\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dropdown/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dropdown\\\\Dropdown\\:\\:getDefinition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Dropdown/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dropdown\\\\Dropdown\\:\\:prepareDefinitionInput\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dropdown/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Dropdown\\\\DropdownDefinitionManager\\:\\:autoloadClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Dropdown/DropdownDefinitionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Error\\\\ErrorHandler\\:\\:disableNativeErrorDisplaying\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Error/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Event\\:\\:add\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Event\\:\\:delete\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Event\\:\\:showForUser\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Event\\:\\:update\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Exception\\\\RedirectException\\:\\:__construct\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Exception/RedirectException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\AllowList\\:\\:canAnswer\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/AllowList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\AllowList\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/AllowList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\DirectAccess\\:\\:allowUnauthenticated\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/DirectAccess.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\DirectAccess\\:\\:canAnswer\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/DirectAccess.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\ControlType\\\\DirectAccess\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/ControlType/DirectAccess.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:createConfigFromUserInput\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:getConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:getStrategy\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AccessControl\\\\FormAccessControl\\:\\:prepareConfigInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AccessControl/FormAccessControl.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Answer\\:\\:fromDecodedJsonData\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Answer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\AnswersSet\\:\\:getAnswers\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/AnswersSet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Comment\\:\\:getSection\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Comment\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Comment\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Comment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionData\\:\\:getItemType\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionData\\:\\:getItemType\\(\\) throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\ItemConditionHandler\\:\\:applyValueOperator\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionHandler/ItemConditionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\UserDevicesConditionHandler\\:\\:applyMultipleDevicesValueOperator\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionHandler/UserDevicesConditionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\ConditionHandler\\\\UserDevicesConditionHandler\\:\\:applySingleDeviceValueOperator\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/ConditionHandler/UserDevicesConditionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\EditorManager\\:\\:getValueOperatorForValidationDropdownValues\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/EditorManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\EditorManager\\:\\:getValueOperatorForValidationDropdownValues\\(\\) throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/EditorManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\Engine\\:\\:computeCondition\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/Engine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\FormData\\:\\:createFromForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/FormData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Condition\\\\FormData\\:\\:parseRawConditionsData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Condition/FormData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\AbstractConfigField\\:\\:getConfig\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/AbstractConfigField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsField\\:\\:convertFieldConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\AssociatedItemsField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/AssociatedItemsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ContentField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ContentField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ContentField\\:\\:convertLegacyTags\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ContentField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ContentField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ContentField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\EntityField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\EntityField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\EntityField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\EntityField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:getItemWithAssignableItemtypeQuestionsValuesForDropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\SplException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILActorField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILActorField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILCategoryField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILCategoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILCategoryField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILCategoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILCategoryField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILCategoryField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILFollowupField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILFollowupField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILFollowupField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILFollowupField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILTaskField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILTaskField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ITILTaskField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ITILTaskField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsField\\:\\:applyConfiguratedValueAfterDestinationCreation\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsField\\:\\:convertFieldConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsField\\:\\:createITILLink\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LinkedITILObjectsField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LinkedITILObjectsField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LocationField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LocationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LocationField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LocationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\LocationField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/LocationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestSourceField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestSourceField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestSourceField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestSourceField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestTypeField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestTypeField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestTypeField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestTypeField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\RequestTypeField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/RequestTypeField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\SLMField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLMField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\SLMField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/SLMField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\StatusField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/StatusField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\StatusField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/StatusField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TemplateField\\:\\:__construct\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TemplateField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TemplateField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TemplateField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TitleField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TitleField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TitleField\\:\\:convertLegacyTags\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TitleField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\TitleField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/TitleField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\UrgencyField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/UrgencyField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\UrgencyField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/UrgencyField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\UrgencyField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/UrgencyField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationField\\:\\:applyConfiguratedValueToInputUsingAnswers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationField\\:\\:convertFieldConfig\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationField\\:\\:convertFieldConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\CommonITILField\\\\ValidationField\\:\\:renderConfigForm\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/CommonITILField/ValidationField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:getConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:getForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:isMandatory\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:prepareInput\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Destination\\\\FormDestination\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Destination/FormDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\EndUserInputNameProvider\\:\\:filterAnswers\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/EndUserInputNameProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\EndUserInputNameProvider\\:\\:reindexAnswers\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/EndUserInputNameProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:__construct\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:getItemId\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Context\\\\DatabaseMapper\\:\\:tryTofindOneRowByName\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Context/DatabaseMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:computeJsonFileName\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:exportTranslations\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importAccessControlPolicices\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importAccessControlPolicices\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importAccessControlPolicices\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importBasicFormProperties\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importComments\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importCondition\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importCondition\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importDestinations\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importDestinations\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importFormsFromJson\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importQuestions\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importQuestions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importSections\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importTranslations\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:importTranslations\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:listIssues\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:prepareConditionsForImport\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:prepareConditionsForImport\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:prepareConditionsForImport\\(\\) throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Export\\\\Serializer\\\\FormSerializer\\:\\:previewImport\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Export/Serializer/FormSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:addDefaultDestinations\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:cronPurgeDraftForms\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:deleteMissingComments\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:deleteMissingQuestions\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:deleteMissingSections\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:getQuestionsByTypes\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateComments\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateComments\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateQuestions\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateQuestions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateSections\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Form\\:\\:updateSections\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:getCreationStrategyFromLegacy\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:getLogicOperatorFromLegacy\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:getStrategyConfigForAccessTypes\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:getVisibilityStrategyFromLegacy\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfAccessControls\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfDestinationFields\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfDestinationFieldsForType\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfDestinationType\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfITILActorsFields\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfTranslations\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfValidationConditions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Migration\\\\FormMigration\\:\\:processMigrationOfVisibilityConditions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Migration/FormMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Question\\:\\:getSection\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Question\\:\\:prepareInput\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Question\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Question\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/Question.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionType\\:\\:formatRawAnswer\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeActors\\:\\:convertDefaultValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeActors\\:\\:formatDefaultValueForDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeActors\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeActors\\:\\:getDefaultValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeActors\\:\\:prepareEndUserAnswer\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeActors.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeSelectable\\:\\:convertDefaultValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeSelectable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeSelectable\\:\\:convertExtraData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeSelectable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeSelectable\\:\\:getOptions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/AbstractQuestionTypeSelectable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeAssignee\\:\\:prepareEndUserAnswer\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeAssignee.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeCheckbox\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeCheckbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDateTime\\:\\:formatAnswer\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDateTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDateTime\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDateTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDateTime\\:\\:getConditionHandlers\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDateTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDateTime\\:\\:validateExtraDataInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDateTime.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDropdown\\:\\:convertExtraData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDropdown\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeDropdown\\:\\:isMultipleDropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItem\\:\\:formatDefaultValueForDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItem\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItem\\:\\:getDefaultValueItemId\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItem\\:\\:getDefaultValueItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:convertExtraData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:getCategoriesFilter\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:getDropdownRestrictionParams\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:getDropdownRestrictionParams\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:getRootItemsId\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeItemDropdown\\:\\:getSubtreeDepth\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeObserver\\:\\:prepareEndUserAnswer\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeObserver.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeRadio\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeRadio.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeRequester\\:\\:prepareEndUserAnswer\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeRequester.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeUserDevice\\:\\:getConditionHandlers\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeUserDevice\\:\\:prepareEndUserAnswer\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeUserDevice.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:getTemplateResultForCategories\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:getTemplateResultForQuestionTypes\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:getTemplateSelectionForCategories\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:getTemplateSelectionForQuestionTypes\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\QuestionType\\\\QuestionTypesManager\\:\\:loadCoreQuestionsTypes\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:getForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:listTranslationsHandlers\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Section\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Section.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogManager\\:\\:removeChildrenCompositeWithoutChildren\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/ServiceCatalogManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\ServiceCatalog\\\\ServiceCatalogManager\\:\\:removeRootCompositeWithoutChildren\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/ServiceCatalog/ServiceCatalogManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Form\\\\Tag\\\\FormTagsManager\\:\\:insertTagsContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Form/Tag/FormTagsManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:addQuestion\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:addQuestion\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:createForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:getCategoryQuestionData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:getLocationQuestionData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:getObserversQuestionData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:getUserDevicesQuestionData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\DefaultDataManager\\:\\:setDefaultDestinationConfig\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/DefaultDataManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\Tile\\\\FormTile\\:\\:post_getFromDB\\(\\) throws checked exception Glpi\\\\Helpdesk\\\\Tile\\\\InvalidTileException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/FormTile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:addTile\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:addTile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:deleteTile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Helpdesk\\\\Tile\\\\TilesManager\\:\\:getItemTileForTile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Helpdesk/Tile/TilesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:applyStrategy\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:computeFallbackStrategy\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:computeFallbackStrategyForPlugin\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:getTargetFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:isHiddenFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:isTargetAPhpScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Firewall\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/Firewall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\Response\\:\\:sendError\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/Response.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\SessionManager\\:\\:getTargetFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\SessionManager\\:\\:isHiddenFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\SessionManager\\:\\:isResourceStateless\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\SessionManager\\:\\:isTargetAPhpScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Http\\\\SessionManager\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Http/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Antivirus\\:\\:prepare\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Antivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Device\\:\\:handle\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Device.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Drive\\:\\:isDrive\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Drive.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:cleanName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:handleLinks\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/InventoryAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Memory\\:\\:prepare\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkCard\\:\\:handleIpNetworks\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkCard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:getNameForMac\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:getNameForMac\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:getNameForMac\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:handleIpNetworks\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:handlePorts\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:rulepassed\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\OperatingSystem\\:\\:prepare\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Printer\\:\\:handle\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Printer\\:\\:prepare\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\RemoteManagement\\:\\:prepare\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\Software\\:\\:getNormalizedComparisonKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:handleIpNetworks\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Asset\\\\VirtualMachine\\:\\:prepare\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:importFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:importFiles\\(\\) throws checked exception wapmorgan\\\\UnifiedArchive\\\\Exceptions\\\\NonExistentArchiveFileException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:isInventoryFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Conf\\:\\:showConfigForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Conf.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleanorphans\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:cronCleantemp\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:doInventory\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:extractMetadata\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:handleInventoryFile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:handleInventoryFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:processInventoryData\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:setData\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:setData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Inventory\\:\\:setData\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:handleIpNetworks\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:prepareForBios\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:rulepassed\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\MainAsset\\:\\:rulepassed\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:getStackId\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\NetworkEquipment\\:\\:getStackId\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\Printer\\:\\:handleMetrics\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\MainAsset\\\\Printer\\:\\:prepare\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Inventory\\\\Request\\:\\:prolog\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslatedPercentage\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslation\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslationsToDo\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:getTranslationsToReview\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\ItemTranslation\\\\ItemTranslation\\:\\:prepapreInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\ControllerListener\\\\FirewallStrategyListener\\:\\:onKernelController\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/ControllerListener/FirewallStrategyListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\ExceptionListener\\\\AccessErrorListener\\:\\:onKernelException\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/ExceptionListener/AccessErrorListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\PostBootListener\\\\SessionStart\\:\\:getTargetFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\PostBootListener\\\\SessionStart\\:\\:isHiddenFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\PostBootListener\\\\SessionStart\\:\\:isTargetAPhpScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\PostBootListener\\\\SessionStart\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\PostBootListener\\\\SessionStart\\:\\:onPostBoot\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/PostBootListener/SessionStart.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\CatchInventoryAgentRequestListener\\:\\:onKernelRequest\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/CatchInventoryAgentRequestListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:getTargetFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:isHiddenFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:isTargetAPhpScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:onKernelRequest\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\FrontEndAssetsListener\\:\\:onKernelRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/FrontEndAssetsListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findAssetModelclass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findAssetTypeclass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findCustomAssetClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findCustomDropdownClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findGenericClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyItemtypeRouteListener\\:\\:findPluginClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyItemtypeRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyRouterListener\\:\\:getTargetFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyRouterListener\\:\\:isHiddenFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyRouterListener\\:\\:isTargetAPhpScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyRouterListener\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\LegacyRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/LegacyRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceCircularReferenceException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\NotFoundHttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:onKernelRequest\\(\\) throws checked exception Symfony\\\\Component\\\\Routing\\\\Exception\\\\MethodNotAllowedException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceCircularReferenceException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\PluginsRouterListener\\:\\:resolveController\\(\\) throws checked exception Symfony\\\\Component\\\\DependencyInjection\\\\Exception\\\\ServiceNotFoundException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/PluginsRouterListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\RedirectLegacyRouteListener\\:\\:onKernelRequest\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/RedirectLegacyRouteListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\SessionCheckCookieListener\\:\\:onKernelRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/SessionCheckCookieListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Kernel\\\\Listener\\\\RequestListener\\\\SessionVariables\\:\\:onKernelRequest\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Kernel/Listener/RequestListener/SessionVariables.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:downloadArchive\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:getAllPlugins\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:getAllPlugins\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:getPaginatedCollection\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:getPluginsForTag\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Api\\\\Plugins\\:\\:getTopTags\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Api/Plugins.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:downloadPlugin\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:downloadPlugin\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:downloadPlugin\\(\\) throws checked exception wapmorgan\\\\UnifiedArchive\\\\Exceptions\\\\EmptyFileListException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:proxifyPluginArchive\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:proxifyPluginArchive\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:proxifyPluginArchive\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\Controller\\:\\:setPluginState\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:getButtons\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:getButtons\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Marketplace\\\\View\\:\\:installed\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Marketplace/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:copyItems\\(\\) throws checked exception Glpi\\\\Migration\\\\MigrationException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:copyItems\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:copyPolymorphicConnexityItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:importItem\\(\\) throws checked exception Glpi\\\\Migration\\\\MigrationException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:importItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:importItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\AbstractPluginMigration\\:\\:importItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/AbstractPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getCustomFieldSpecs\\(\\) throws checked exception Glpi\\\\Migration\\\\MigrationException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getCustomFieldSpecs\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getCustomFieldSpecs\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getExpectedClassNameForPluginTable\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getExpectedClassNameForPluginTable\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getExpectedTableForPluginClassName\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getExpectedTableForPluginClassName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getGenericObjectFieldsDefinition\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getTargetItemtype\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:getTargetItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importAssetsDefinitions\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importAssetsDefinitions\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importAssetsDefinitions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importDropdownDefinitions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importDropdowns\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importDropdowns\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importObjects\\(\\) throws checked exception Glpi\\\\Migration\\\\MigrationException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:importObjects\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Migration\\\\GenericobjectPluginMigration\\:\\:isAGenericObjectFkeyField\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Migration/GenericobjectPluginMigration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\AccessTokenRepository\\:\\:isAccessTokenRevoked\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/AccessTokenRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\AuthCodeRepository\\:\\:getNewAuthCode\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/AuthCodeRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\ClientRepository\\:\\:getClientEntity\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/ClientRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\ScopeRepository\\:\\:finalizeScopes\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/ScopeRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\Server\\:\\:__construct\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\Server\\:\\:checkKeys\\(\\) throws checked exception Glpi\\\\Exception\\\\OAuth2KeyException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\Server\\:\\:deleteKeys\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\Server\\:\\:doGenerateKeys\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\OAuth\\\\Server\\:\\:generateKeys\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Plugin\\\\HookManager\\:\\:registerFile\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Plugin/HookManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Plugin\\\\HookManager\\:\\:registerFunctionalHook\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Plugin/HookManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Plugin\\\\HookManager\\:\\:registerItemHook\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Plugin/HookManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:__construct\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/AbstractProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:finish\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/AbstractProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\AbstractProgressIndicator\\:\\:triggerUpdate\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/AbstractProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:canAccessProgressIndicator\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:getProgressIndicator\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:getProgressIndicator\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:getUniqueStorageKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:save\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\ProgressStorage\\:\\:save\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Progress/ProgressStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Progress\\\\StoredProgressIndicator\\:\\:update\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Progress/StoredProgressIndicator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:fixImagesPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:getTextFromHtml\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:imageGallery\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:isRichTextHtmlContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:loadImagesLazy\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:normalizeHtmlContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:replaceImagesByGallery\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\RichText\\:\\:replaceImagesByGallery\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\RichText\\\\UserMention\\:\\:refreshUserMentionsHtmlToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/RichText/UserMention.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Rules\\\\RulesManager\\:\\:initializeRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Rules/RulesManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\CriteriaFilter\\:\\:post_getFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:displaySearchoption\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Input\\\\QueryBuilder\\:\\:showGenericSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Input/QueryBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Output\\\\ExportSearchOutput\\:\\:displayConfigItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Output/ExportSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Output\\\\HTMLSearchOutput\\:\\:showItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Glpi/Search/Output/HTMLSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Output\\\\NamesListSearchOutput\\:\\:showItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/NamesListSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Output\\\\Ods\\:\\:getMime\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Ods.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Output\\\\Spreadsheet\\:\\:displayData\\(\\) throws checked exception PhpOffice\\\\PhpSpreadsheet\\\\Writer\\\\Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/Spreadsheet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:constructCriteriaSQL\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:constructData\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:explodeWithID\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getDefaultWhereCriteria\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getHavingCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getLeftJoinCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getOrderByCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getSelectCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:getWhereCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 14,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:giveItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:giveItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:makeTextSearchValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\Provider\\\\SQLProvider\\:\\:parseJoinString\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\SearchEngine\\:\\:getMetaParentItemtypesForTypesConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Search\\\\SearchEngine\\:\\:getOutputForLegacyKey\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/SearchEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\PermissionManager\\:\\:getAllEntities\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/PermissionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:getTwoFactorAuth\\(\\) throws checked exception TypeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:getTwoFactorAuth\\(\\) throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:isBackupCodesAvailable\\(\\) throws checked exception JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:regenerateBackupCodes\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:setSecretForUser\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:showTOTPConfigForm\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:showTOTPConfigForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:showTOTPSetupForm\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Security\\\\TOTPManager\\:\\:showTOTPSetupForm\\(\\) throws checked exception RobThree\\\\Auth\\\\TwoFactorAuthException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Security/TOTPManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\AbstractDatabaseChecker\\:\\:fetchTableColumns\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\AbstractDatabaseChecker\\:\\:getIndex\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/AbstractDatabaseChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseKeysChecker\\:\\:getMisnamedKeys\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseKeysChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseKeysChecker\\:\\:getMissingKeys\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseKeysChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:canCheckIntegrity\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:checkCompleteSchema\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:checkCompleteSchema\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:diff\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:extractSchemaFromFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:getEffectiveCreateTableSql\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:getNormalizedSql\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 11,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:getSchemaPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\DatabaseSchemaIntegrityChecker\\:\\:normalizeWhitespaces\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/DatabaseSchemaIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:generateManifest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:generateManifest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:generateManifest\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:getBaselineManifest\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:getDiff\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:getGLPIRelease\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Diagnostic\\\\SourceCodeIntegrityChecker\\:\\:getSummary\\(\\) throws checked exception JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Diagnostic/SourceCodeIntegrityChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:__construct\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:download\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:empty\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:getLogsFilesList\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:getLogsFilesList\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:getLogsFilesList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:parseLogFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Log\\\\LogParser\\:\\:parseLogFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\DbEngine\\:\\:check\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/DbEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\PhpSupportedVersion\\:\\:check\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/PhpSupportedVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\PhpVersion\\:\\:check\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/PhpVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SeLinux\\:\\:check\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SeLinux.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SeLinux\\:\\:check\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SeLinux.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsConfiguration\\:\\:isAutostartOn\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsConfiguration\\:\\:isUsetranssidOn\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsConfiguration\\:\\:isUsetranssidOn\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsSecurityConfiguration\\:\\:getCookiesHttpOnly\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsSecurityConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsSecurityConfiguration\\:\\:getCookiesSamesite\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsSecurityConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Requirement\\\\SessionsSecurityConfiguration\\:\\:getCookiesSecure\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Requirement/SessionsSecurityConfiguration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Status\\\\StatusChecker\\:\\:getIMAPStatus\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Status/StatusChecker.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\System\\\\Variables\\:\\:getDataDirectories\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Variables.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\ArrayPathAccessor\\:\\:getArrayPaths\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/ArrayPathAccessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\DataExport\\:\\:normalizeValueForTextExport\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DataExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\DatabaseSchema\\:\\:getEmptySchemaPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/DatabaseSchema.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Filesystem\\:\\:canWriteFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Filesystem\\:\\:isFilepathSafe\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Filesystem\\:\\:isFilepathSafe\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Filesystem\\:\\:normalizePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\FrontEnd\\:\\:getVersionCacheKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\NetworkException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/FrontEnd.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Sanitizer\\:\\:decodeHtmlSpecialChars\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Sanitizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Sanitizer\\:\\:isHtmlEncoded\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Sanitizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\Sanitizer\\:\\:isNsClassOrCallableIdentifier\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/Sanitizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:extractCoreItemtypeFromUrlPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:extractPluginItemtypeFromUrlPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:isGLPIRelativeUrl\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:isGLPIRelativeUrl\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:isPluginUrlPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\URL\\:\\:sanitizeURL\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Toolbox/URL.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\VersionParser\\:\\:getIntermediateVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\VersionParser\\:\\:getMajorVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\VersionParser\\:\\:getNormalizedVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\VersionParser\\:\\:isDevVersion\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Toolbox\\\\VersionParser\\:\\:isStableRelease\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Toolbox/VersionParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:checkIconFile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:getCustomIllustrationFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:getCustomSceneFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:getIconsDefinitions\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:getIconsDefinitions\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:saveCustomIllustration\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:saveCustomScene\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\IllustrationManager\\:\\:validateOrInitCustomContentDir\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/IllustrationManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\ThemeManager\\:\\:getCurrentTheme\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\ThemeManager\\:\\:getCustomThemes\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\ThemeManager\\:\\:getCustomThemes\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/UI/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\UI\\\\ThemeManager\\:\\:getCustomThemes\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/UI/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Group\\:\\:updateLastGroupChange\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableBase\\:\\:addHeader\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableBase\\:\\:appendHeader\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableBase\\:\\:getHeaderByName\\(\\) throws checked exception HTMLTableUnknownHeader but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableBase\\:\\:getHeaderOrder\\(\\) throws checked exception HTMLTableUnknownHeadersOrder but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableBase\\:\\:getHeaders\\(\\) throws checked exception HTMLTableUnknownHeaders but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableCell\\:\\:__construct\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableCell\\:\\:__construct\\(\\) throws checked exception HTMLTableCellFatherCoherentHeader but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableCell\\:\\:__construct\\(\\) throws checked exception HTMLTableCellFatherSameRow but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableCell\\:\\:__construct\\(\\) throws checked exception HTMLTableCellWithoutFather but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableCell\\:\\:updateCellSteps\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableCell.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableGroup\\:\\:tryAddHeader\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableGroup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableMain\\:\\:tryAddHeader\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableMain.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableRow\\:\\:addCell\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableSubHeader\\:\\:updateColSpan\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableSubHeader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method HTMLTableSuperHeader\\:\\:LCM\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/HTMLTableSuperHeader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:activateUserTemplateAutocompletion\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:cleanParametersURL\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:compileScss\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:compileScss\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:compileScss\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:computeGenericDateTimeSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:computeGenericDateTimeSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:file\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:footer\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:generateHelpMenu\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getBackUrl\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getConfirmationOnActionScript\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getCoreVariablesForJavascript\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getImageHtmlTagForDocument\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getInvertedColor\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getPageHeaderTplVars\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getRefererUrl\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getScssCompilePath\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getScssFileHash\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getScssFileHash\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:getSimpleForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:includeHeader\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:initEditorSystem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:initEditorSystem\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:jsAdaptDropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:jsAjaxDropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:jsAlertCallback\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:link\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:loadJavascript\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:progress\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:redirect\\(\\) throws checked exception Glpi\\\\Exception\\\\RedirectException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:sanitizeDomId\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:sanitizeInputName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:script\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:showDateField\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:showGenericDateTimeSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:submit\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:timestampToRelativeStr\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:timestampToRelativeStr\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Html\\:\\:uploadedFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Method IPAddress\\:\\:setAddressFromString\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILSolution\\:\\:setParentItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILSolution.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplate\\:\\:getFromDBWithData\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplate\\:\\:getITILObjectClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITILTemplatePredefinedField\\:\\:getMultiplePredefinedValues\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITIL_ValidationStep\\:\\:getValidationStepsStatus\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ITIL_ValidationStep.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ITIL_ValidationStep\\:\\:post_updateItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ITIL_ValidationStep.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:bfs\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:buildGraph\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:displayListView\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:displayTabContentForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:getTabNameForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:makeDataForCytoscape\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:prepareParams\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Impact\\:\\:searchAsset\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:Amort\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:getWarrantyExpir\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:linearAmortise\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:linearAmortise\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:manageDateOnStatusChange\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Infocom\\:\\:showTco\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ItemAntivirus\\:\\:getTabNameForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ItemVirtualMachine\\:\\:findVirtualMachine\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ItemVirtualMachine\\:\\:getTabNameForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ItemVirtualMachine\\:\\:getUUIDRestrictCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/ItemVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:getDeviceType\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:getTableGroup\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:getTableGroup\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Devices\\:\\:showForm\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Devices.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Disk\\:\\:showForItem\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Disk\\:\\:showForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Environment\\:\\:getTabNameForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Kanban\\:\\:getKanbanItemForItemtype\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Kanban\\:\\:loadStateForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Kanban\\:\\:loadStateForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Kanban\\:\\:saveStateForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Kanban.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Process\\:\\:getTabNameForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Process.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Rack\\:\\:showForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_Rack\\:\\:showStats\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Item_RemoteManagement\\:\\:showForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:computeBooleanFullTextSearch\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 15,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getAnswer\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getListRequest\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getListRequest\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getTreeCategoryList\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getTreeCategoryList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaFAQ\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaKB\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaKB_Entity\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaKB_Group\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaKB_Profile\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:getVisibilityCriteriaKB_User\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method KnowbaseItem\\:\\:showBrowseView\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:computeDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:computeExecutionDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method LevelAgreement\\:\\:getActiveTimeBetween\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Line\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Line.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Link\\:\\:registerTag\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Location\\:\\:displaySpecificTypeField\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Location.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Lock\\:\\:showForItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Lock\\:\\:showForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Lock.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Log\\:\\:convertFiltersValuesToSqlCriteria\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:buildTicket\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:cleanContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:connect\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:deleteMails\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\ExceptionInterface but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:deleteMails\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:extractFolderData\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:extractValuesFromRefHeader\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getAdditionnalHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getAdditionnalHeaders\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getBody\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\IconvException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getDecodedContent\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getEmailFromHeader\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getEmailFromHeader\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getHeaders\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getItemFromHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getItemFromHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getItemFromHeaders\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getMessageIdFromHeaders\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getRecursiveAttached\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:getTotalMails\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\ExceptionInterface but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:isResponseToMessageSentByAnotherGlpi\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MailCollector\\:\\:isResponseToMessageSentByAnotherGlpi\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:__construct\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:__construct\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:displayProgressBar\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:getAllMassiveActions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:getCheckItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method MassiveAction\\:\\:showMassiveActionsSubForm\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Migration\\:\\:fieldFormat\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Migration\\:\\:renameItemtype\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Migration\\:\\:renameItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Migration\\:\\:renameTable\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkName\\:\\:displayTabContentForItem\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:showPort\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:splitInputForElements\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:updateMetrics\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortEthernet\\:\\:transformPortSpeed\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortEthernet.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortFiberchannel\\:\\:transformPortSpeed\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortFiberchannel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortInstantiation\\:\\:showNetworkPortSelector\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortInstantiation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortMetrics\\:\\:showMetrics\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortMetrics.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortType\\:\\:getDefaults\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortType\\:\\:getDefaults\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortType\\:\\:getInstantiationType\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPortType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPortType\\:\\:invalidateCache\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notepad\\:\\:getAllForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Notepad.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification\\:\\:saveFilter\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification\\:\\:send\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationEventMailing\\:\\:handleFailedSend\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationMailingSetting\\:\\:showFormConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationMailingSetting.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationSetting\\:\\:getMode\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationSetting.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationSetting\\:\\:getTypeName\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationSetting.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getMessageIdForEvent\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:getProfileJoinCriteria\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:removeExcludedTargets\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTargetUser\\:\\:addDataForTemplate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:convertRelativeGlpiLinksToAbsolute\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:process\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:processIf\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:getModeClass\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Notification_NotificationTemplate\\:\\:getModeClass\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification_NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OAuthClient\\:\\:post_getFromDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/OAuthClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OAuthClient\\:\\:prepareInputForAdd\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/OAuthClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OAuthClient\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/OAuthClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OAuthClient\\:\\:prepareInputForUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/OAuthClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OlaLevelAction\\:\\:__construct\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevelAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method OlaLevelCriteria\\:\\:__construct\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevelCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PCIVendor\\:\\:getList\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PCIVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PCIVendor\\:\\:getList\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PCIVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PCIVendor\\:\\:getList\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PCIVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PDU\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PassiveDCEquipment\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PassiveDCEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PendingReason_Item\\:\\:getAutoResolvedate\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PendingReason_Item\\:\\:getNextFollowupDate\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PendingReason_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Phone\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:cleanDates\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:cloneEvent\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:cloneEvent\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:constructEventsArray\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:constructEventsArray\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:editEventForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:generateIcal\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:getActorIdFromPlanningKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:getActorTypeFromPlanningKey\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:getPaletteColor\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showAddEventClassicForm\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showAddEventClassicForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:showSingleLinePlanningFilter\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:addInstanceException\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:createInstanceClone\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:encodeRrule\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getGroupItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getPlanInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getRRuleInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getRsetFromRRuleField\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getUserItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getVCalendarForItem\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getVCalendarForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getVCalendarForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getVCalendarForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:getVisibilityCriteria\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:populatePlanning\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEvent\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:addInstanceException\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:createInstanceClone\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:encodeRrule\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:getRsetFromRRuleField\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:populatePlanning\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningExternalEventTemplate\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PlanningRecall\\:\\:manageDatas\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:activate\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:activate\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:bootPlugins\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:checkGlpiVersion\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:checkPhpParameters\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:checkPhpVersion\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:checkPluginState\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:checkPluginState\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:clean\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:getAddSearchOptionsNew\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:getFilesystemPluginKeys\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:includeHook\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:init\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:install\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:load\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:loadLang\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:loadLang\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:loadPluginSetupFile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:loadPluginSetupFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:messageMissingRequirement\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:prepareInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:registerClass\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:registerPluginAutoloader\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:registerPluginAutoloader\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:resetHookableCacheEntries\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:unactivate\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Plugin\\:\\:uninstall\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Preference\\:\\:displayTabContentForItem\\(\\) throws checked exception JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Preference.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Preference\\:\\:displayTabContentForItem\\(\\) throws checked exception RobThree\\\\Auth\\\\TwoFactorAuthException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Preference.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Printer\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PrinterLog\\:\\:getMetrics\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PrinterLog\\:\\:getMetrics\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PrinterLog\\:\\:showMetrics\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Printer_CartridgeInfo\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer_CartridgeInfo.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Printer_CartridgeInfo\\:\\:showForPrinter\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer_CartridgeInfo.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Problem_Ticket\\:\\:processMassiveActionsForOneItemtype\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile\\:\\:getLinearRightChoice\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProfileRight\\:\\:addProfileRights\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProfileRight\\:\\:cleanAllPossibleRights\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProfileRight\\:\\:deleteProfileRights\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProfileRight\\:\\:getAllPossibleRights\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ProfileRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Profile_User\\:\\:logOperation\\(\\) throws checked exception Error but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Profile_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:addInstanceException\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:autoSetDate\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:createInstanceClone\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:displayPlanningItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:encodeRrule\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getGroupItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getInputFromVCalendar\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getItemsAsVCalendars\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getPlanInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getRRuleInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getRsetFromRRuleField\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getUserItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getVCalendarForItem\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getVCalendarForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getVCalendarForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:getVCalendarForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTask\\:\\:post_updateItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ProjectTaskTeam\\:\\:prepareInputForAdd\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTaskTeam.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedNotification\\:\\:cleanHtml\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedNotification\\:\\:cronQueuedNotification\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedNotification\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedWebhook\\:\\:cronQueuedWebhook\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedWebhook\\:\\:prepareInputForAdd\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedWebhook\\:\\:sendById\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method QueuedWebhook\\:\\:showForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RSSFeed\\:\\:getRSSFeed\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rack\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Rack.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:addInstanceException\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:addVisibilityRestrict\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:createInstanceClone\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:encodeRrule\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getCommonInputFromVcomponent\\(\\) throws checked exception UnexpectedValueException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getGroupItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getPlanInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getRRuleInputFromVComponent\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getRsetFromRRuleField\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getUserItemsAsVCalendars\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getVCalendarForItem\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getVCalendarForItem\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getVCalendarForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:getVCalendarForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:populatePlanning\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reminder\\:\\:populatePlanning\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Report\\:\\:getAssetTypeCounts\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Report\\:\\:getNetworkReport\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Report\\:\\:handleInfocomDates\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Report\\:\\:showInfocomReport\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Report\\:\\:showOtherInfocomReport\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:computePeriodicities\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 30,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:displayTabContentForItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:getEvents\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:getUniqueGroupFor\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:pre_deleteItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:showForm\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:showForm\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:test_valid_date\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Reservation\\:\\:updateEvent\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getCollectionClassInstance\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:getDefaultRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\SimplexmlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleAction\\:\\:computeFriendlyName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleAction\\:\\:getRegexResultById\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:getRuleClassName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:previewImportRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:previewImportRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCollection\\:\\:previewImportRules\\(\\) throws checked exception Safe\\\\Exceptions\\\\SimplexmlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCommonITILObject\\:\\:executeActions\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCommonITILObject\\:\\:getItemtype\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCommonITILObject\\:\\:getItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCommonITILObjectCollection\\:\\:getItemtype\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCommonITILObjectCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleCriteria\\:\\:computeFriendlyName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleImportAssetCollection\\:\\:prepareInputDataForProcess\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAssetCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleMailCollectorCollection\\:\\:prepareInputDataForProcess\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMailCollectorCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleMatchedLog\\:\\:getTabNameForItem\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method RuleMatchedLog\\:\\:showForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:addVisibilityRestrict\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:croncountAll\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:load\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SavedSearch\\:\\:prepareSearchUrlForDB\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:checkSeveralRightsOr\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:checkValidSessionId\\(\\) throws checked exception Glpi\\\\Exception\\\\SessionExpiredException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:checkValidSessionId\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:destroy\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:getCurrentDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:getCurrentProfile\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:getNewCSRFToken\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:getNewIDORToken\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:init\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:isRTL\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:loadGroups\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:loadLanguage\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:setPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:setPath\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:start\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:writeClose\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SlaLevelAction\\:\\:__construct\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevelAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SlaLevelCriteria\\:\\:__construct\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevelCriteria.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:getTreeCategoryList\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:getTreeCategoryList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Software\\:\\:showBrowseView\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareLicense\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Method SoftwareVersion\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stat\\:\\:constructEntryValues\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stat\\:\\:getItems\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stat\\:\\:showItems\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stencil\\:\\:displayStencil\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stencil\\:\\:displayStencilEditor\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stencil\\:\\:getTabNameForItem\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stencil\\:\\:removeZones\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Stencil\\:\\:resetZones\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Stencil.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:cronTelemetry\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:getViewLink\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabOsInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabOsInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabPhpInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabWebserverInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\CurlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabWebserverInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Telemetry\\:\\:grabWebserverInfos\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:computeTakeIntoAccountDelayStat\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:convertContentForTicket\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:getSpecificValueToDisplay\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:showCentralList\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Ticket\\:\\:showStatsDates\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 8,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:callCurl\\(\\) throws checked exception Safe\\\\Exceptions\\\\CurlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:callCurl\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:checkNewVersionAvailable\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:checkNewVersionAvailable\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:cleanDecimal\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:cleanInteger\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:cleanNewLines\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:cleanTagOrImage\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:cleanTarget\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:convertTagToImage\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:convertTagToImage\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:createSchema\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:decodeArrayFromInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:decodeArrayFromInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:decodeArrayFromInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\ZlibException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:decodeFromUtf8\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:deleteDir\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:deleteDir\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:deletePicture\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:encodeInUtf8\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:filename\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:formatOutputWebLink\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getColorForString\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getColorForString\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getColorForString\\(\\) throws checked exception Safe\\\\Exceptions\\\\MiscException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getDateFormats\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getDecimalNumbers\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getDocumentsFromTag\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFgColor\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFgColor\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\FileinfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getFileAsResponse\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getMailServerStorageInstance\\(\\) throws checked exception Laminas\\\\Mail\\\\Storage\\\\Exception\\\\ExceptionInterface but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getMemoryLimit\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getMemoryLimit\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getMioSizeFromString\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getPhpUploadSizeLimit\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:getRandomString\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:get_max_input_vars\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:hasTrait\\(\\) throws checked exception Safe\\\\Exceptions\\\\SplException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:isValidWebUrl\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:jsonDecode\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:log\\(\\) throws checked exception Safe\\\\Exceptions\\\\ErrorfuncException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:logInFile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:manageBeginAndEndPlanDates\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:parseMailServerConnectString\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:prepareArrayForInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:prepareArrayForInput\\(\\) throws checked exception Safe\\\\Exceptions\\\\ZlibException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:resizePicture\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:resizePicture\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 10,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:sendFile\\(\\) throws checked exception Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:shortenNumber\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:slugify\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:testWriteAccessToDirectory\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:testWriteAccessToDirectory\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Toolbox\\:\\:writeConfig\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getList\\(\\) throws checked exception Psr\\\\SimpleCache\\\\InvalidArgumentException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getList\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getList\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getManufacturer\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method USBVendor\\:\\:getProductName\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/USBVendor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Unmanaged\\:\\:checkSetup\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Update\\:\\:doUpdates\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Update\\:\\:getMigrationsToDo\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Update\\:\\:getMigrationsToDo\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Update\\:\\:isDbUpToDate\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:__construct\\(\\) throws checked exception Safe\\\\Exceptions\\\\UrlException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:basename\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:create_scaled_image\\(\\) throws checked exception Safe\\\\Exceptions\\\\ErrorfuncException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:delete\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:download\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:download\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:fix_file_extension\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:gd_create_scaled_image\\(\\) throws checked exception DivisionByZeroError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:gd_create_scaled_image\\(\\) throws checked exception Safe\\\\Exceptions\\\\ErrorfuncException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:gd_create_scaled_image\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:gd_imageflip\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:gd_orient_image\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:generate_response\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:generate_response\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_file_objects\\(\\) throws checked exception Safe\\\\Exceptions\\\\DirException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_file_size\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_image_size\\(\\) throws checked exception Safe\\\\Exceptions\\\\ErrorfuncException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_image_size\\(\\) throws checked exception Safe\\\\Exceptions\\\\ImageException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_scaled_image_file_paths\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:get_user_id\\(\\) throws checked exception Safe\\\\Exceptions\\\\SessionException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:handle_file_upload\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:has_image_file_extension\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:imagetype\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:post\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:readfile\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:readfile\\(\\) throws checked exception Safe\\\\Exceptions\\\\OutcontrolException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:upcount_name\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:validate\\(\\) throws checked exception Safe\\\\Exceptions\\\\InfoException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method UploadHandler\\:\\:validate\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:cronPasswordExpiration\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 9,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:dropPictureFiles\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:dropdown\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:forgetPassword\\(\\) throws checked exception Random\\\\RandomException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:forgetPassword\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getAuthToken\\(\\) throws checked exception Exception but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getFromLDAP\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getFromSSO\\(\\) throws checked exception Safe\\\\Exceptions\\\\MbstringException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getFromSSO\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getLdapFieldNames\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getLdapFieldValue\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getPasswordExpirationTime\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getSqlSearchResult\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getTreeCategoryList\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 7,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:getTreeCategoryList\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 3,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:isSubstituteOf\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 6,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:prepareInputForUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:prepareInputForUpdate\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 5,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:shouldChangePassword\\(\\) throws checked exception Safe\\\\Exceptions\\\\DatetimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:showBrowseView\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:syncLdapPhoto\\(\\) throws checked exception Safe\\\\Exceptions\\\\FilesystemException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:syncLdapPhoto\\(\\) throws checked exception Safe\\\\Exceptions\\\\StringsException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method User\\:\\:validatePassword\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 4,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ValidationStep\\:\\:getDefault\\(\\) throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/ValidationStep.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:addParentItemData\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:getDefaultPayloadAsTwigTemplate\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:getTabNameForItem\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:getWebhookBody\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 2,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:raise\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:saveFilter\\(\\) throws checked exception Safe\\\\Exceptions\\\\JsonException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Webhook\\:\\:validateCRAChallenge\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/Webhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Method XHProf\\:\\:stop\\(\\) throws checked exception RuntimeException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/XHProf.php',
];
$ignoreErrors[] = [
	'message' => '#^Function isPluginItemType\\(\\) throws checked exception Safe\\\\Exceptions\\\\PcreException but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/misc-functions.php',
];
$ignoreErrors[] = [
	'message' => '#^Function jsescape\\(\\) throws checked exception Twig\\\\Error\\\\RuntimeError but it\'s missing from the PHPDoc @throws tag\\.$#',
	'identifier' => 'missingType.checkedException',
	'count' => 1,
	'path' => __DIR__ . '/src/autoload/misc-functions.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
