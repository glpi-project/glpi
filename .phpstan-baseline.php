<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$input of method CommonDBTM\\:\\:add\\(\\) expects array, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/impact.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/itilfollowup.php',
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
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/solution.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/task.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 3,
	'path' => __DIR__ . '/ajax/timeline.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/ajax/transfers.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$force of method CommonDBTM\\:\\:deleteFromDB\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/unlockobject.php',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/agent.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/appliance.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/appliance_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/appliance_item_relation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/authldap.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/authmail.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/budget.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/cable.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/cartridge.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/cartridgeitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/certificate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/change.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/cluster.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitiltask.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/commonitilvalidation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/computer.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/computer_item.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/computerantivirus.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/computervirtualmachine.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/consumableitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/contact.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/contract.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/contractcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/database.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/databaseinstance.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/datacenter.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/dcroom.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$option of static method Html\\:\\:header\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/dictionnary.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/displaypreference.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/document.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domain.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/domainrecord.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/dropdown.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/dropdowntranslation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/enclosure.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/group.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/infocom.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDBTM\\:\\:add\\(\\) expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/infocom.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Glpi\\\\Inventory\\\\Conf\\:\\:\\$enabled_inventory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addError\\(\\) expects string, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/inventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_cluster.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_device.common.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_disk.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_enclosure.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_operatingsystem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/item_remotemanagement.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/itilfollowup.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/knowbaseitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/line.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/link.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/lockedfield.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$display of method MailCollector\\:\\:collect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/mailcollector.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/mailcollector.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/manuallink.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/monitor.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkalias.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkequipment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkname.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/networkport.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/networkportmigration.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notepad.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDBTM\\:\\:add\\(\\) expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notepad.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notification.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to Html\\:\\:ajaxFooter\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'staticMethod.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/front/notification.tags.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notification_notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getState\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationmailingsetting.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplate.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/notificationtemplatetranslation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ola.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevelaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/olalevelcriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/passivedcequipment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/pdu.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/pdu_plug.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/pdu_rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/peripheral.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/phone.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/planningexternalevent.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/printer.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/problem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/profile.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/project.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projectcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/projecttask.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/queuednotification.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rack.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/refusedequipment.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reminder.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of static method Search\\:\\:showList\\(\\) expects class\\-string\\<CommonDBTM\\>, class\\-string\\<CommonGLPI\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.dynamic.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reservation.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/reservationitem.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rssfeed.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$option of static method Html\\:\\:header\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.backup.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$option of static method Html\\:\\:header\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rule.test.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ruleaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/rulecriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/savedsearch.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/savedsearch_alert.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$option of static method Html\\:\\:header\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/setup.auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/sla.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevel.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevelaction.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slalevelcriteria.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/slm.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$token of method Glpi\\\\Mail\\\\SMTP\\\\OauthProvider\\\\ProviderInterface\\:\\:getResourceOwner\\(\\) expects League\\\\OAuth2\\\\Client\\\\Token\\\\AccessToken, League\\\\OAuth2\\\\Client\\\\Token\\\\AccessTokenInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/smtp_oauth2_callback.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/snmpcredential.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/socket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/software.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/front/softwarelicense.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/softwareversion.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$item_type_output_param of static method Html\\:\\:printPager\\(\\) expects int\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$item_type_output_param of static method Html\\:\\:printPager\\(\\) expects int\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/supplier.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticket_ticket.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/ticketcost.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/transfer.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/unmanaged.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/user.form.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in include_once\\(\\) "CAS\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'includeOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/inc/autoload.function.php',
];
$ignoreErrors[] = [
	'message' => '#^Function getDateCriteria\\(\\) should return string but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/inc/db.function.php',
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
	'message' => '#^Parameter \\#2 \\$debug_sql of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$debug_vars of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$log_in_files of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/install.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset mixed on array\\{\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.80.x_to_0.83.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/migrations/update_0.80.x_to_0.83.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$authtype of static method Auth\\:\\:isAlternateAuth\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.80.x_to_0.83.0.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.83.1_to_0.83.3.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$expression of class QueryExpression constructor expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_10.0.16_to_10.0.17/tree_dropdowns.php',
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
	'message' => '#^Parameter \\#1 \\$crit of method DBmysqlIterator\\:\\:analyseCrit\\(\\) expects array\\<string\\>, array\\<string, array\\<string, string\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_9.4.3_to_9.4.5.php',
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
	'message' => '#^Access to property \\$dbdefault on an unknown class DB\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/install/update.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableTableCaching\\(\\) on an unknown class DB\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/install/update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$debug_sql of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$debug_vars of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/update.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$log_in_files of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AbstractRightsDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Agent\\:\\:requestAgent\\(\\) should return GuzzleHttp\\\\Psr7\\\\Response but returns Psr\\\\Http\\\\Message\\\\ResponseInterface\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$parent of method Glpi\\\\Agent\\\\Communication\\\\AbstractRequest\\:\\:addNode\\(\\) expects DOMElement, DOMNode given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Alert.php',
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
	'message' => '#^Method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) should return array\\<bool\\>\\|bool\\|void but returns list\\<array\\<mixed\\>\\>\\.$#',
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
	'message' => '#^Parameter \\#1 \\$id of method Glpi\\\\Api\\\\API\\:\\:getNetworkPorts\\(\\) expects int, array\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getSearchOptionUniqID\\(\\) expects CommonDBTM, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects string, list\\<array\\<mixed\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$message of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects string, list\\<array\\<string, mixed\\>\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tab of static method Html\\:\\:printCleanArray\\(\\) expects array, \\$this\\(Glpi\\\\Api\\\\API\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/API.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$httpcode of method Glpi\\\\Api\\\\API\\:\\:returnError\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
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
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:applyMassiveAction\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:createItems\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:deleteItems\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getItem\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getItems\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getMassiveActionParameters\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:getMassiveActions\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:listSearchOptions\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:searchItems\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$itemtype of method Glpi\\\\Api\\\\API\\:\\:updateItems\\(\\) expects string, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIRest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user_id of method Glpi\\\\Api\\\\API\\:\\:userPicture\\(\\) expects bool\\|int, string given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$method of function xmlrpc_encode_request expects string, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Api/APIXmlrpc.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Appliance_Item.php',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function file_exists expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/Extension/FrontEndAssetsExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between null and 2 will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Application/View/TemplateRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Auth\\:\\:connection_ldap\\(\\) never assigns null to &\\$error so it can be removed from the by\\-ref type\\.$#',
	'identifier' => 'parameterByRef.unusedType',
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
	'message' => '#^Offset \'basedn\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'condition\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'login_field\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'sync_field\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ldap_method of static method AuthLDAP\\:\\:tryToConnectToServer\\(\\) expects array, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user of static method Auth\\:\\:showSynchronizationForm\\(\\) expects User, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Auth.php',
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
	'message' => '#^Parameter \\#1 \\$config_ldap of static method AuthLDAP\\:\\:isLdapPageSizeAvailable\\(\\) expects object, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ldap_method of method Auth\\:\\:connection_ldap\\(\\) expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$action of static method AuthLDAP\\:\\:ldapImportUserByServerId\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$entity of static method AuthLDAP\\:\\:getAllGroups\\(\\) expects string, int given\\.$#',
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
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$type of static method Blacklist\\:\\:getBlacklistedItems\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Blacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Blacklist\\:\\:\\$blacklists \\(array\\) on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.property',
	'count' => 1,
	'path' => __DIR__ . '/src/Blacklist.php',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:addslashes_deep\\(\\) expects array\\<string\\>\\|string, array\\<string, int\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Calendar.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$vcalendar of method Glpi\\\\CalDAV\\\\Contracts\\\\CalDAVCompatibleItemInterface\\:\\:getInputFromVCalendar\\(\\) expects Sabre\\\\VObject\\\\Component\\\\VCalendar, Sabre\\\\VObject\\\\Document given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Backend/Principal.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Backend\\\\Principal\\:\\:canViewGroupObjects\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/Acl.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group_id of method Glpi\\\\CalDAV\\\\Plugin\\\\Acl\\:\\:canViewGroupObjects\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CalDAV/Plugin/CalDAV.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Calendar.php',
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
	'message' => '#^Comparison operation "\\>" between int\\<1, max\\> and 0 is always true\\.$#',
	'identifier' => 'greater.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$cartitem of static method Cartridge\\:\\:showAddForm\\(\\) expects CartridgeItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$cartitem of static method Cartridge\\:\\:showForCartridgeItem\\(\\) expects CartridgeItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Cartridge\\:\\:countForCartridgeItem\\(\\) expects CartridgeItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Cartridge\\:\\:countForPrinter\\(\\) expects Printer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$printer of method Printer_CartridgeInfo\\:\\:showForPrinter\\(\\) expects Printer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$printer of static method Cartridge\\:\\:showForPrinter\\(\\) expects Printer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method CartridgeItem\\:\\:cronCartridge\\(\\) with return type void returns int but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CartridgeItem.php',
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
	'message' => '#^Parameter \\#1 \\$checkitem \\(null\\) of method Certificate\\:\\:getSpecificMassiveActions\\(\\) should be compatible with parameter \\$checkitem \\(object\\) of method CommonDBTM\\:\\:getSpecificMassiveActions\\(\\)$#',
	'identifier' => 'method.childParameterType',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Certificate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Change.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ChangeTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$change of static method Change_Item\\:\\:showForChange\\(\\) expects Change, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Change\\:\\:showListForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$change of static method Change_Problem\\:\\:showForChange\\(\\) expects Change, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$problem of static method Change_Problem\\:\\:showForProblem\\(\\) expects Problem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$change of static method Change_Ticket\\:\\:showForChange\\(\\) expects Change, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of static method Change_Ticket\\:\\:showForTicket\\(\\) expects Ticket, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Change_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$check_once of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getSQLCriteriaToSearchForItem\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBConnexity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$message_type of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method CommonDBRelation\\:\\:processMassiveActionsForOneItemtype\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBRelation.php',
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
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
	'count' => 2,
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
	'message' => '#^PHPDoc tag @var with type class\\-string is not subtype of native type static\\(CommonDBTM\\)\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:isNewID\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$condition of method CommonDBTM\\:\\:assetBusinessRules\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$integer of static method Toolbox\\:\\:cleanInteger\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$check_once of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$message_type of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects int, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$message_type of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$type of method Ticket\\:\\:getActiveTicketsForItem\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonDBTM.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonDBVisible.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function array_slice expects int\\|null, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDCModelDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between \\$this\\(CommonDropdown\\) and IPAddress will always evaluate to false\\.$#',
	'identifier' => 'instanceof.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Call to function is_integer\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
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
	'message' => '#^Parameter \\#1 \\$number of static method Html\\:\\:formatNumber\\(\\) expects float, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILCost.php',
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
	'message' => '#^Parameter \\#1 \\$type of method CommonITILObject\\:\\:getTemplateFieldName\\(\\) expects string\\|null, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$display_sec of static method Html\\:\\:timestampToString\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$new of static method CommonITILObject\\:\\:isAllowedStatus\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$actortype of method CommonITILObject\\:\\:hasValidActorInInput\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 7,
	'path' => __DIR__ . '/src/CommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILRecurrent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
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
	'message' => '#^Parameter \\#1 \\$hour of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILTask.php',
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
	'message' => '#^Parameter \\#1 \\$item of method CommonITILValidation\\:\\:showSummary\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CommonITILValidation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/CommonTreeDropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Computer\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$comp of static method ComputerAntivirus\\:\\:showForComputer\\(\\) expects Computer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ComputerAntivirus.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$comp of static method ComputerVirtualMachine\\:\\:showForComputer\\(\\) expects Computer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ComputerVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$comp of static method ComputerVirtualMachine\\:\\:showForVirtualMachine\\(\\) expects Computer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ComputerVirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(integer\\: count\\)\\: Unexpected token "\\:", expected TOKEN_HORIZONTAL_WS at offset 199 on line 9$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$comp of static method Computer_Item\\:\\:showForComputer\\(\\) expects Computer, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Computer_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$onlyglobal of static method Computer_Item\\:\\:dropdownAllConnect\\(\\) expects bool, int\\<min, 1\\>\\|int\\<3, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$onlyglobal of static method Computer_Item\\:\\:dropdownConnect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Computer_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$used of static method Computer_Item\\:\\:dropdownAllConnect\\(\\) expects array\\<int\\>, array\\<list\\<mixed\\>\\> given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$width of static method Html\\:\\:displayProgressBar\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$options of method CommonDropdown\\:\\:showForm\\(\\) expects array, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Property Glpi\\\\Console\\\\AbstractCommand\\:\\:\\$progress_bar \\(Symfony\\\\Component\\\\Console\\\\Helper\\\\ProgressBar\\) does not accept null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/AbstractCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function property_exists\\(\\) with \\$this\\(Glpi\\\\Console\\\\Application\\) and \'db\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$debug_sql of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$debug_vars of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$log_in_files of static method Toolbox\\:\\:setDebugMode\\(\\) expects bool\\|null, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Console\\\\Application\\:\\:\\$db \\(DBmysql\\) does not accept DB\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Application.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Build/CompileScssCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type SplFileInfo is not subtype of native type DirectoryIterator\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/CommandLoader.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Database/EnableTimezonesCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Database\\\\InstallCommand\\:\\:shouldSetDBConfig\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Database/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$action of static method AuthLDAP\\:\\:ldapImportUserByServerId\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Ldap/SynchronizeUsersCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:addslashes_deep\\(\\) expects array\\<string\\>\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Console/Migration/DatabasesPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:addslashes_deep\\(\\) expects array\\<string\\>\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Console/Migration/DomainsPluginToCoreCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/MyIsamToInnoDbCommand.php',
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
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/UnsignedKeysCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method DBConnection\\:\\:updateConfigProperty\\(\\) expects string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Migration/Utf8mb4Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Glpi\\\\Console\\\\Plugin\\\\InstallCommand\\:\\:isAlreadyInstalled\\(\\) should return array but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Plugin/InstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$consitem of static method Consumable\\:\\:showAddForm\\(\\) expects ConsumableItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$consitem of static method Consumable\\:\\:showForConsumableItem\\(\\) expects ConsumableItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Consumable\\:\\:countForConsumableItem\\(\\) expects ConsumableItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$show_old of static method Consumable\\:\\:showForConsumableItem\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Consumable.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ConsumableItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contact.php',
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
	'message' => '#^Method Contract\\:\\:dropdown\\(\\) should return int\\|string but empty return statement found\\.$#',
	'identifier' => 'return.empty',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$contract of static method ContractCost\\:\\:showForContract\\(\\) expects Contract, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ContractCost.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$contract of static method Contract_Item\\:\\:showForContract\\(\\) expects Contract, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
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
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$contract of static method Contract_Supplier\\:\\:showForContract\\(\\) expects Contract, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$supplier of static method Contract_Supplier\\:\\:showForSupplier\\(\\) expects Supplier, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Contract_Supplier.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/CronTask.php',
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
	'message' => '#^Parameter \\#1 \\$DBconnection of static method DBConnection\\:\\:getHistoryMaxDate\\(\\) expects DBmysql, DB given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$DBconnection of static method DBConnection\\:\\:getHistoryMaxDate\\(\\) expects DBmysql, DBSlave given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$display_sec of static method Html\\:\\:timestampToString\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/DBConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$log of static method Toolbox\\:\\:backtrace\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of method mysqli\\:\\:real_connect\\(\\) expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of method mysqli\\:\\:real_connect\\(\\) expects int\\|null, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
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
	'message' => '#^Parameter \\#1 \\$datacenter of static method DCRoom\\:\\:showForDatacenter\\(\\) expects Datacenter, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$name of static method AbstractRightsDropdown\\:\\:show\\(\\) expects string, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Grid.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method CommonDBVisible\\:\\:getVisibilityCriteria\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$instance of static method Database\\:\\:showForInstance\\(\\) expects DatabaseInstance, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Database.php',
];
$ignoreErrors[] = [
	'message' => '#^Method DatabaseInstance\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method DatabaseInstance\\:\\:showInstances\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DatabaseInstance.php',
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
	'message' => '#^Parameter \\#1 \\$input of static method Toolbox\\:\\:str_pad\\(\\) expects string, \\(float\\|int\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$IDf of method DbUtils\\:\\:getSonsOf\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DbUtils.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between \'\\.php\' and bool will always evaluate to false\\.$#',
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
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/DisplayPreference.php',
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
	'message' => '#^Parameter \\#4 \\$toobserve of static method Ajax\\:\\:updateItem\\(\\) expects string, false given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$doc of static method Document_Item\\:\\:showForDocument\\(\\) expects Document, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Document_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Document_Item\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$domain of static method DomainRecord\\:\\:showForDomain\\(\\) expects Domain, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecord.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method DomainRecord\\:\\:countForDomain\\(\\) expects Domain, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DomainRecord.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$domain of static method Domain_Item\\:\\:showForDomain\\(\\) expects Domain, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Domain_Item\\:\\:countForDomain\\(\\) expects Domain, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
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
	'message' => '#^Ternary operator condition is always true\\.$#',
	'identifier' => 'ternary.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Domain_Item.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
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
	'message' => '#^Parameter \\#3 \\$nb of function _n expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Dropdown.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$input of method DropdownTranslation\\:\\:checkBeforeAddorUpdate\\(\\) expects bool, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method DropdownTranslation\\:\\:getNumberOfTranslationsForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DropdownTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method DropdownTranslation\\:\\:showTranslations\\(\\) expects CommonDropdown, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, int\\<0, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$unicity of static method FieldUnicity\\:\\:showDoubles\\(\\) expects FieldUnicity, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/FieldUnicity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Fieldblacklist\\:\\:isFieldBlacklisted\\(\\) should return true but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Fieldblacklist.php',
];
$ignoreErrors[] = [
	'message' => '#^Method GLPIKey\\:\\:keyExists\\(\\) should return string but returns bool\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPINetwork.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$lw of method TCPDF\\:\\:setHeaderData\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$ln of method TCPDF\\:\\:Cell\\(\\) expects int, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIPDF.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$fill of method TCPDF\\:\\:Cell\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/GLPIPDF.php',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$group of static method Group_User\\:\\:showForGroup\\(\\) expects Group, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user of static method Group_User\\:\\:showForUser\\(\\) expects User, CommonGLPI given\\.$#',
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
	'message' => '#^Parameter \\#9 \\$inactive_deleted of static method User\\:\\:getSqlSearchResult\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Group_User.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between HTMLTableHeader and HTMLTableHeader will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableBase.php',
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
	'message' => '#^Call to an undefined method HTMLTableHeader\\:\\:getCompositeName\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/HTMLTableRow.php',
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
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 4,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with bool will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Expression on left side of \\?\\? is not nullable\\.$#',
	'identifier' => 'nullCoalesce.expr',
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
	'message' => '#^Parameter \\#1 \\$filename of function file_exists expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of static method Toolbox\\:\\:getTimestampTimeUnits\\(\\) expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of static method Html\\:\\:getPrefixedUrl\\(\\) expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:prepareArrayForInput\\(\\) expects array, int\\<min, \\-1\\>\\|int\\<1, max\\>\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$super of static method IPAddress\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/IPAddress.php',
];
$ignoreErrors[] = [
	'message' => '#^Method IPNetmask\\:\\:setNetmaskFromString\\(\\) should return false but returns true\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetmask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$address of static method IPAddress\\:\\:addValueToAddress\\(\\) expects array\\<int\\>, array\\<int, float\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
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
	'message' => '#^Parameter \\#1 \\$port of static method IPNetwork_Vlan\\:\\:showForIPNetwork\\(\\) expects IPNetwork, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/IPNetwork_Vlan.php',
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
	'message' => '#^Method ITILTemplate\\:\\:showCentralPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$withtypeandcategory of static method ITILTemplate\\:\\:getExtraAllowedFields\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withitemtype of static method ITILTemplate\\:\\:getExtraAllowedFields\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tt of static method ITILTemplateField\\:\\:showForITILTemplate\\(\\) expects ITILTemplate, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplateField.php',
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
	'message' => '#^Parameter \\#1 \\$tt of static method ITILTemplatePredefinedField\\:\\:showForITILTemplate\\(\\) expects ITILTemplate, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ITILTemplatePredefinedField.php',
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
	'message' => '#^Variable \\$total might not be defined\\.$#',
	'identifier' => 'variable.undefined',
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
	'message' => '#^Parameter \\#1 \\$item of static method Infocom\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cartridgeblackmatte\'\\|\'cartridgeblackphoto\' on array\\{tonerblack\\: array\\{\'tonerblack2\'\\}, tonerblackmax\\: array\\{\'tonerblack2max\'\\}, tonerblackused\\: array\\{\'tonerblack2used\'\\}, tonerblackremaining\\: array\\{\'tonerblack2remaining\'\\}\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cartridgematteblack\'\\|\'cartridgephotoblack\' on array\\{tonerblack\\: array\\{\'tonerblack2\'\\}, tonerblackmax\\: array\\{\'tonerblack2max\'\\}, tonerblackused\\: array\\{\'tonerblack2used\'\\}, tonerblackremaining\\: array\\{\'tonerblack2remaining\'\\}\\} in isset\\(\\) does not exist\\.$#',
	'identifier' => 'isset.offset',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$history of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/MainAsset.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$rules_id of method Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\:\\:rulepassed\\(\\) expects int, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Call to function method_exists\\(\\) with \\$this\\(Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\) and \'handleAggregations\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Empty array passed to foreach\\.$#',
	'identifier' => 'foreach.emptyArray',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$netports_id_2 of method Glpi\\\\Inventory\\\\Asset\\\\NetworkPort\\:\\:addPortsWiring\\(\\) expects int, false given\\.$#',
	'identifier' => 'argument.type',
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
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Glpi\\\\Inventory\\\\Asset\\\\InventoryAsset\\:\\:\\$main_asset \\(Glpi\\\\Inventory\\\\Asset\\\\MainAsset\\) in isset\\(\\) is not nullable\\.$#',
	'identifier' => 'isset.property',
	'count' => 2,
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/VirtualMachine.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$path of method Glpi\\\\Inventory\\\\Conf\\:\\:importContentFile\\(\\) expects string, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Conf.php',
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
	'message' => '#^Parameter \\#1 \\$cluster of static method Item_Cluster\\:\\:showItems\\(\\) expects Cluster, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Cluster.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$camera of static method Item_DeviceCamera_ImageFormat\\:\\:showItems\\(\\) expects DeviceCamera, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$camera of static method Item_DeviceCamera_ImageResolution\\:\\:showItems\\(\\) expects DeviceCamera, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$withcomment of static method Dropdown\\:\\:getDropdownName\\(\\) expects bool, int given\\.$#',
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
	'message' => '#^Method Item_Disk\\:\\:showForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Disk.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Item_Disk\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$enclosure of static method Item_Enclosure\\:\\:showItems\\(\\) expects Enclosure, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Enclosure.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$item of static method Problem\\:\\:showListForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$problem of static method Item_Problem\\:\\:showForProblem\\(\\) expects Problem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForMainItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$project of static method Item_Project\\:\\:showForProject\\(\\) expects Project, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$rack of static method Item_Rack\\:\\:showItems\\(\\) expects Rack, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$item of static method Item_RemoteManagement\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_RemoteManagement.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'e\'\\|\'g\'\\|\'i\'\\|\'l\'\\|\'o\'\\|\'s\'\\|\'u\' and \'_\' will always evaluate to false\\.$#',
	'identifier' => 'equal.alwaysFalse',
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
	'message' => '#^Parameter \\#1 \\$license of static method Item_SoftwareLicense\\:\\:showForLicense\\(\\) expects SoftwareLicense, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$license of static method Item_SoftwareLicense\\:\\:showForLicenseByEntity\\(\\) expects SoftwareLicense, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'d\'\\|\'e\'\\|\'g\'\\|\'i\'\\|\'l\'\\|\'o\'\\|\'s\'\\|\'u\'\\|\'v\' and \'_\' will always evaluate to false\\.$#',
	'identifier' => 'equal.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
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
	'message' => '#^Parameter \\#1 \\$software of static method Item_SoftwareVersion\\:\\:showForSoftware\\(\\) expects Software, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$version of static method Item_SoftwareVersion\\:\\:showForVersion\\(\\) expects SoftwareVersion, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$version of static method Item_SoftwareVersion\\:\\:showForVersionByEntity\\(\\) expects SoftwareVersion, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\!\\= between \'Monitor\'\\|\'Peripheral\'\\|\'Phone\'\\|\'Printer\' and \'Software\' will always evaluate to true\\.$#',
	'identifier' => 'notEqual.alwaysTrue',
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
	'message' => '#^Method Item_Ticket\\:\\:showForTicket\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of static method Item_Ticket\\:\\:showForTicket\\(\\) expects Ticket, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$right of static method Session\\:\\:haveRight\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Item_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$itemtype of static method Item_Ticket\\:\\:dropdownMyDevices\\(\\) expects string, null given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$itil of static method Itil_Project\\:\\:showForItil\\(\\) expects CommonITILObject, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$project of static method Itil_Project\\:\\:showForProject\\(\\) expects Project, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Itil_Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between \'ASC\' and \'ASC\' will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<0, 1\\> given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItemTranslation\\:\\:getNumberOfTranslationsForItem\\(\\) expects KnowbaseItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItemTranslation\\:\\:showTranslations\\(\\) expects KnowbaseItem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItemTranslation.php',
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
	'message' => '#^Method KnowbaseItem_Item\\:\\:dropdownAllTypes\\(\\) should return string but returns int\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Item.php',
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
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_KnowbaseItemCategory\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_KnowbaseItemCategory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method KnowbaseItem_Revision\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/KnowbaseItem_Revision.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$end of method Calendar\\:\\:getActiveTimeBetween\\(\\) expects string, DateTime given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type OlaLevel\\|SlaLevel is not subtype of native type static\\(LevelAgreementLevel\\)\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ola\\|sla of method OlaLevel\\:\\:showForParent\\(\\) expects OLA\\|SLA, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreementLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$check_once of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LineOperator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$message_type of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LineOperator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Link\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Link.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Link\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$part of method MailCollector\\:\\:getDecodedContent\\(\\) expects Laminas\\\\Mail\\\\Storage\\\\Message, Laminas\\\\Mail\\\\Storage\\\\Part given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$uid of method MailCollector\\:\\:buildTicket\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MailCollector.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$uid of method MailCollector\\:\\:deleteMails\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#1 \\$item of static method Link\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ManualLink\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/ManualLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_countable\\(\\) with array\\<array\\> will always evaluate to true\\.$#',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/MassiveAction.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$onlyone of method DBmysql\\:\\:updateOrInsert\\(\\) expects bool, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and \'comment\'\\|\'error\'\\|\'info\' will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Monitor\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method Computer_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Monitor.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method NetworkEquipment\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NetworkEquipment\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkEquipment.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$item of static method NetworkName\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkName.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$super of static method NetworkName\\:\\:getHTMLTableHeader\\(\\) expects HTMLTableSuperHeader\\|null, HTMLTableHeader given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method NetworkPort\\:\\:switchInstantiationType\\(\\) should return bool but returns NetworkPortInstantiation\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/NetworkPort.php',
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
	'message' => '#^Parameter \\#6 \\$meta of static method Search\\:\\:addLeftJoin\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$meta_type of static method Search\\:\\:addLeftJoin\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#1 \\$netport of method NetworkPortConnectionLog\\:\\:getCriteria\\(\\) expects NetworkPort, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPortConnectionLog.php',
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
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort_NetworkPort.php',
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
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/Notification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$tid of method NotificationTemplate\\:\\:getDataToSend\\(\\) expects string, int\\<min, \\-1\\>\\|int\\<1, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventAbstract.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to preg_quote\\(\\) is missing delimiter / to be effective\\.$#',
	'identifier' => 'argument.invalidPregQuote',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationEventMailing.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTarget\\:\\:showForGroup\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTarget.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$ID of method NotificationTarget\\:\\:getFromDBForTarget\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$array of function implode expects array\\<string\\>, array\\<array\\|string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/NotificationTargetCommonITILObject.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, \\(list\\<string\\>\\|string\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTargetSavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method NotificationTemplate\\:\\:getTemplateByLanguage\\(\\) should return int\\|false but returns non\\-falsy\\-string\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/NotificationTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$length of function array_slice expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/OlaLevel_Ticket.php',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$side of static method PDU_Rack\\:\\:getForRackSide\\(\\) expects int, array\\<int, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$pdu of static method Pdu_Plug\\:\\:showItems\\(\\) expects PDU, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method Peripheral\\:\\:getDcBreadcrumbSpecificValueToDisplay\\(\\) should return array but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Peripheral\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method Computer_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Peripheral.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Phone\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method Computer_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Phone.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Planning\\:\\:checkAvailability\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 3,
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
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
	'message' => '#^Parameter \\#1 \\$hour of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEvent.php',
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
	'message' => '#^Parameter \\#1 \\$hour of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PlanningExternalEventTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always false\\.$#',
	'identifier' => 'if.alwaysFalse',
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
	'count' => 1,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 11,
	'path' => __DIR__ . '/src/Plugin.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Printer\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method Computer_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Printer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method Problem\\:\\:showListForItem\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 2,
	'path' => __DIR__ . '/src/Problem.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProblemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$problem of static method Problem_Ticket\\:\\:showForProblem\\(\\) expects Problem, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of static method Problem_Ticket\\:\\:showForTicket\\(\\) expects Ticket, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Problem_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Profile.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\=\\= between 2 and 2 will always evaluate to true\\.$#',
	'identifier' => 'equal.alwaysTrue',
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
	'message' => '#^Method Project\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Project.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$project of static method ProjectCost\\:\\:showForProject\\(\\) expects Project, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method ProjectTask\\:\\:showFor\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$hour of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$is_recursive of function getEntitiesRestrictCriteria expects \'auto\'\\|bool, 1 given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method CommonDBRelation\\:\\:countForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$projecttask of static method ProjectTask_Ticket\\:\\:showForProjectTask\\(\\) expects ProjectTask, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ticket of static method ProjectTask_Ticket\\:\\:showForTicket\\(\\) expects Ticket, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ProjectTask_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'from\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'label\' does not exist on string\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/QueuedNotification.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$callback of function usort expects callable\\(mixed, mixed\\)\\: int, array\\{\'SimplePie\', \'sort_items\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
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
	'message' => '#^Parameter \\#1 \\$room of static method Rack\\:\\:showForRoom\\(\\) expects DCRoom, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method RefusedEquipment\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
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
	'message' => '#^Instanceof between static\\(Reminder\\) and ExtraVisibilityCriteria will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
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
	'message' => '#^Parameter \\#1 \\$hour of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of method DateTime\\:\\:setTime\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reminder.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return has invalid value \\(true;\\)\\: Unexpected token ";", expected TOKEN_HORIZONTAL_WS at offset 125 on line 6$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/ReminderTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ReminderTranslation\\:\\:getNumberOfTranslationsForItem\\(\\) expects Reminder, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReminderTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method ReminderTranslation\\:\\:showTranslations\\(\\) expects Reminder, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/ReminderTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Reservation\\:\\:showForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Reservation.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RichText/RichText.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Rule\\:\\:showForm\\(\\) with return type void returns true but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:canEdit\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of method Rule\\:\\:showAndAddRuleForm\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getImpactName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getImpactName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getPriorityName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getPriorityName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getStatus\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getStatus\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILObject\\:\\:getUrgencyName\\(\\) expects int, string given\\.$#',
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
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILValidation\\:\\:getStatus\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method CommonITILValidation\\:\\:getStatus\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Dropdown\\:\\:getGlobalSwitch\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Dropdown\\:\\:getValueWithUnit\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Ticket\\:\\:getTicketTypeName\\(\\) expects int, string given\\.$#',
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
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$id of static method Dropdown\\:\\:getDropdownName\\(\\) expects int, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
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
	'message' => '#^Method RuleCollection\\:\\:exportRulesToXML\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
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
	'message' => '#^Parameter \\#3 \\$global_result of method RuleCollection\\:\\:showTestResults\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftware.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleDictionnarySoftware.php',
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
	'message' => '#^Parameter \\#1 \\$options of method CommonDBTM\\:\\:getLink\\(\\) expects array, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/RuleMatchedLog.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$authtype of static method Auth\\:\\:getMethodName\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRight.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 1,
	'path' => __DIR__ . '/src/SNMPCredential.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$check_once of static method Session\\:\\:addMessageAfterRedirect\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$search of static method SavedSearch_Alert\\:\\:showForSavedSearch\\(\\) expects SavedSearch, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:deleteByCriteria\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SavedSearch_Alert.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
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
	'message' => '#^Loose comparison using \\!\\= between numeric\\-string and \'NULL\' will always evaluate to true\\.$#',
	'identifier' => 'notEqual.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison using \\!\\= between numeric\\-string and \'null\' will always evaluate to true\\.$#',
	'identifier' => 'notEqual.alwaysTrue',
	'count' => 1,
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
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 2 on array\\{string, string, string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$num of function abs expects float\\|int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$NOT of static method Search\\:\\:addHaving\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$end of method LevelAgreement\\:\\:getActiveTimeBetween\\(\\) expects DateTime, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$nott of static method Search\\:\\:addWhere\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<0, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$time of static method SavedSearch\\:\\:updateExecutionTime\\(\\) expects int, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$field of method DBmysql\\:\\:result\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$meta of static method Search\\:\\:addLeftJoin\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 22,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$meta of static method Search\\:\\:addWhere\\(\\) expects int, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#7 \\$meta_type of static method Search\\:\\:addLeftJoin\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 21,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between int\\|string and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'identifier' => 'deadCode.unreachable',
	'count' => 2,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$out might not be defined\\.$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Session\\:\\:loadLanguage\\(\\) with return type void returns mixed but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Laminas\\\\I18n\\\\Translator\\\\Translator\\:\\:\\$cache \\(Laminas\\\\Cache\\\\Storage\\\\StorageInterface\\|null\\) does not accept Glpi\\\\Cache\\\\I18nCache\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:can\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$withcriterias of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$withactions of method Rule\\:\\:getRuleWithCriteriasAndActions\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SlaLevel_Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type CommonDBTM supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
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
	'message' => '#^Parameter \\#1 \\$input of method RuleCollection\\:\\:processAllRules\\(\\) expects array, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 4,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$output of method RuleCollection\\:\\:processAllRules\\(\\) expects array, null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Software.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$software of static method SoftwareLicense\\:\\:showForSoftware\\(\\) expects Software, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareLicense.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$soft of static method SoftwareVersion\\:\\:showForSoftware\\(\\) expects Software, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/SoftwareVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
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
	'message' => '#^Parameter \\#2 \\$display_sec of static method Html\\:\\:timestampToString\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$odd of static method Search\\:\\:showNewLine\\(\\) expects bool, int\\<\\-1, 1\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of static method Search\\:\\:showItem\\(\\) expects string, \\(float\\|int\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 6,
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
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Supplier.php',
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
	'message' => '#^Parameter \\#3 \\$value of function curl_setopt expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Telemetry.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function array_key_exists\\(\\) with \\(int\\|string\\) and array\\{\\} will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
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
	'message' => '#^PHPDoc tag @var with type CommonDBTM is not subtype of native type Group\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 3,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$force_template of method CommonITILObject\\:\\:getITILTemplateToUse\\(\\) expects int, bool given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$item of static method Ticket\\:\\:showListForItem\\(\\) expects CommonDBTM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$is_deleted of static method KnowbaseItem_Item\\:\\:getMassiveActionsForItemtype\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Ticket.php',
];
$ignoreErrors[] = [
	'message' => '#^Method TicketTemplate\\:\\:showHelpdeskPreview\\(\\) with return type void returns false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$ticket_template of method Ticket\\:\\:showFormHelpdesk\\(\\) expects bool, int\\<min, \\-1\\>\\|int\\<1, max\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/TicketTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \'Session\' and \'getLoginUserID\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
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
	'message' => '#^Offset \'function\' on array\\{function\\: string, line\\?\\: int, file\\?\\: string, class\\?\\: class\\-string, type\\?\\: \'\\-\\>\'\\|\'\\:\\:\', args\\?\\: array\\<mixed\\>, object\\?\\: object\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
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
	'message' => '#^Parameter \\#1 \\$ID of method CommonDBTM\\:\\:getFromDB\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of static method CommonGLPI\\:\\:getFormURLWithID\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$level of method Monolog\\\\Logger\\:\\:addRecord\\(\\) expects 100\\|200\\|250\\|300\\|400\\|500\\|550\\|600, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$logger of static method Toolbox\\:\\:log\\(\\) expects Monolog\\\\Logger\\|null, Psr\\\\Log\\\\LoggerInterface given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of method DBmysql\\:\\:escape\\(\\) expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$is_recursive of static method Session\\:\\:changeActiveEntities\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Toolbox.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$subject of function preg_replace expects array\\<float\\|int\\|string\\>\\|string, float given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:addslashes_deep\\(\\) expects array\\<string\\>\\|string, array\\<string, int\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of static method Toolbox\\:\\:addslashes_deep\\(\\) expects array\\<string\\>\\|string, array\\<string, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$force of method CommonDBTM\\:\\:delete\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of function getAncestorsOf expects array\\|string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Transfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$printermodels_id of method CartridgeItem\\:\\:addCompatibleType\\(\\) expects int, string given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Method Unmanaged\\:\\:getInventoryAgent\\(\\) should return Agent\\|null but returns CommonDBTM\\|null\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Unmanaged.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$force of method CommonDBTM\\:\\:deleteFromDB\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Parameter \\#3 \\$subject of function str_replace expects array\\<string\\>\\|string, float given\\.$#',
	'identifier' => 'argument.type',
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
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'identifier' => 'booleanNot.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type array is not subtype of native type array\\{\\}\\|array\\{list\\<string\\>, list\\<string\\>\\}\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$user of static method AuthLDAP\\:\\:forceOneUserSynchronization\\(\\) expects User, CommonDBTM given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$action of static method AuthLDAP\\:\\:ldapImportUserByServerId\\(\\) expects bool, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$items_id of static method CommonDBConnexity\\:\\:getItemsAssociatedTo\\(\\) expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$right of static method Session\\:\\:haveRight\\(\\) expects int, string given\\.$#',
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
