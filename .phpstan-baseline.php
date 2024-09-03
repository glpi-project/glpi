<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:\\$fields\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$LAST\\-MODIFIED\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\:\\:\\$UID\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property Sabre\\\\VObject\\\\Component\\\\VCalendar\\:\\:\\$PRODID\\.$#',
	'count' => 1,
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getFromDB\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/CalDAV.php',
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
	// identifier: staticMethod.notFound
	'message' => '#^Call to an undefined static method CommonDBVisible\\:\\:getVisibilityCriteria\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	// identifier: cast.string
	'message' => '#^Cannot cast array\\<int, string\\>\\|null to string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method HTMLTableHeader\\:\\:getCompositeName\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
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
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$instantiation_type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	// identifier: property.notFound
	'message' => '#^Access to an undefined property object\\:\\:\\$name\\.$#',
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
	// identifier: method.notFound
	'message' => '#^Call to an undefined method KnowbaseItemTranslation\\:\\:showVisibility\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
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
	// identifier: method.nonObject
	'message' => '#^Cannot call method getTime\\(\\) on int\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/MassiveAction.php',
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
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @phpstan\\-return has invalid value \\(\\$interface \\=\\= \'all\' \\? array\\<string, array\\<string, array\\<string, RightDefinition\\[\\]\\>\\>\\> \\: \\(\\$form \\=\\= \'all\' \\? array\\<string, array\\<string, RightDefinition\\[\\]\\>\\> \\: \\(\\$group \\=\\= \'all\' \\? array\\<string, RightDefinition\\[\\]\\> \\: RightDefinition\\[\\]\\)\\)\\)\\: Unexpected token "\\$interface", expected type at offset 517$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Profile.php',
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
	// identifier: class.notFound
	'message' => '#^Call to method save_run\\(\\) on an unknown class XHProfRuns_Default\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/XHProf.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
