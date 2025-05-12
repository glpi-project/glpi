<?php declare(strict_types = 1);

if (preg_match('/^8\.2\./', PHP_VERSION) !== 1 && preg_match('/^8\.3\./', PHP_VERSION) !== 1) {
    return ['parameters' => ['ignoreErrors' => []]];
}

/*
 * The following error will occur only on PHP 8.2 and PHP 8.3.
 * Unfortunately, it is not possible to ignore it using a @phpstan-ignore, so we have to handle it here.
 */

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Function pack is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\pack;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/front/cron.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/front/planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/front/planningexternalevent.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 5,
	'path' => __DIR__ . '/front/stat.global.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 5,
	'path' => __DIR__ . '/front/stat.item.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 5,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 5,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.90.x_to_9.1.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.5.1_to_9.5.2.php',
];
$ignoreErrors[] = [
	'message' => '#^Function long2ip is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\long2ip;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/APIClient.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Function gmmktime is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\gmmktime;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 28,
	'path' => __DIR__ . '/src/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/CalendarSegment.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 6,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 7,
	'path' => __DIR__ . '/src/CronTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getallheaders is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\getallheaders;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Agent/Communication/AbstractRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getallheaders is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\getallheaders;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/CoreController.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 26,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Controller/ReportController.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Api/HL/Doc/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateTimeType.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Asset/CustomFieldType/DateType.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getallheaders is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\getallheaders;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/ApiController.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getallheaders is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\getallheaders;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Controller/StatusController.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Csv/LogCsvExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Csv/PlanningCsv.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 6,
	'path' => __DIR__ . '/src/Glpi/Dashboard/FakeProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Filters/AbstractFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 9,
	'path' => __DIR__ . '/src/Glpi/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/Form.php',
];
$ignoreErrors[] = [
	'message' => '#^Function array_walk_recursive is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\array_walk_recursive;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Form/QuestionType/QuestionTypeItemDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Device.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/OperatingSystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/Asset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Inventory/MainAsset/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Function array_walk_recursive is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\array_walk_recursive;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/ItemTranslation/ItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Function array_walk_recursive is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\array_walk_recursive;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/CriteriaFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/Search/Output/ExportSearchOutput.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Glpi/Search/Provider/SQLProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/System/Log/LogParser.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 20,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 14,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationAjax.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/NotificationTargetUser.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/ObjectLock.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 20,
	'path' => __DIR__ . '/src/Planning.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/PlanningRecall.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/PrinterLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 5,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 4,
	'path' => __DIR__ . '/src/QueuedWebhook.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 7,
	'path' => __DIR__ . '/src/Report.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 24,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/ReservationItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryDropdownCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnaryPrinterCollection.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleImportAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 3,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 10,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Function imagesx is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\imagesx;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Function imagesy is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\imagesy;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Function date is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\date;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 2,
	'path' => __DIR__ . '/src/User.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
