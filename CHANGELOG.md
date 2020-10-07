# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [9.5.2] 2020-10-07

### API changes

#### Removed

- Ability to use SQL expressions as string in criterion values in SQL iterator (replaced by usage of `QueryExpression`).
- Ability to delete a plugin image using `/front/pluginimage.send.php` script.

## [9.5.1] 2020-07-16

## [9.5.0] 2020-07-07

### Added

- Encrypted file systems support.
- Mails collected from suppliers can be marked as private on an entity basis.
- Ability to add custom CSS in entity configuration.
- CLI commands to enable and disable maintenance mode.
- Operating system links on Monitors, Peripherals, Phones and Printers.
- Add datacenter items to global search
- Project task search options for Projects
- Automatic action to purge closed tickets
- Ability to automatically calculate project's percent done
- Software link on Phones.
- Add and answer approvals from timeline
- Add lightbox with PhotoSwipe to timeline images
- Ability to copy tasks while merging tickets
- the API gives the ID of the user who logs in with initSession
- Kanban view for projects
- Network ports on Monitors
- Add warning when there are unsaved changes in forms
- Add ability to get information from the status endpoint in JSON format using Accept header
- Add `glpi:system:status` CLI command for getting the GLPI status

### Changed

- PHP error_reporting and display_errors configuration directives are no longer overrided by GLPI, unless in debug mode (which forces reporting and display of all errors).
- `scripts/migrations/racks_plugin.php` has been replaced by `glpi:migration:racks_plugin_to_core` command available using `bin/console`
- Encryption alogithm improved using libsodium

### API changes

#### Added

- Add translation functions `__()`,  `_n()`,  `_x()` and  `_nx()` in javascript in browser context.
- `Migration::renameItemtype()` method to update of database schema/values when an itemtype class is renamed
- Menu returned by `CommonGLPI::getMenuContent()` method override may now define an icon for each menu entry.
- `CommonDBConnexity::getItemsAssociatedTo()` method to get the items associated to the given one
- `CommonDBConnexity::getItemsAssociationRequest()` method to get the DB request to use to get the items associated to the given one
- `CommonDBTM::clone()` method to clone the current item
- `CommonDBTM::prepareInputForClone()` method to modify the input data that will be used for the cloning
- `CommonDBTM::post_clone()` method to perform other steps after an item has been cloned (like clone the elements it is associated to)

#### Changes

- jQuery library has been upgraded from 2.2.x to 3.4.x. jQuery Migrate is used to ensure backward compatibility in most cases.
- `DBmysqlIterator::handleOrderClause()` supports QueryExpressions
- Use Laminas instead of deprecated ZendFramework
- Database datetime fields have been replaced by timestamp fields to handle timezones support.
- Database integer/float fields values are now returned as number instead of strings from DB read operations.
- Field `domains_id` of Computer, NetworkEquipment and Printer has been dropped and data has been transfered into `glpi_domains_items` table.
- Plugin status hook can now be used to provide an array with more information about the plugin's status the status of any child services.
    - Returned array should contain a 'status' value at least (See status values in Glpi\System\Status\StatusChecker)
    - Old style returns are still supported

#### Deprecated

- `DBMysql::fetch_array()`
- `DBMysql::fetch_row()`
- `DBMysql::fetch_assoc()`
- `DBMysql::fetch_object()`
- `DBMysql::data_seek()`
- `DBMysql::insert_id()`
- `DBMysql::num_fields()`
- `DBMysql::field_name()`
- `DBMysql::list_fields()`
- `DBMysql::affected_rows()`
- `DBMysql::free_result()`
- `DBMysql::isMySQLStrictMode()`
- `getAllDatasFromTable` renamed to `getAllDataFromTable()`
- Usage of `$order` parameter in `getAllDataFromTable()` (`DbUtils::getAllDataFromTable()`)
- `Ticket::getTicketTemplateToUse()` renamed to `Ticket::getITILTemplateToUse()`
- `TicketTemplate::getFromDBWithDatas()` renamed to `TicketTemplate::getFromDBWithData()` (inherited from `ITILTemplate`)
- `Computer_SoftwareLicense` replaced by `Item_SoftwareLicense` and table `glpi_computers_softwarelicenses` renamed to `glpi_items_softwarelicenses`
- `Computer_SoftwareVersion` replaced by `Item_SoftwareVersion` and table `glpi_computers_softwareversions` renamed to `glpi_items_softwareversions`
- `Item_SoftwareVersion::updateDatasForComputer` renamed to `Item_SoftwareVersion::updateDatasForItem`
- `Item_SoftwareVersion::showForComputer` renamed to `Item_SoftwareVersion::showForItem`
- `Item_SoftwareVersion::softsByCategory` renamed to `Item_SoftwareVersion::softwareByCategory`
- `Item_SoftwareVersion::displaySoftsByLicense` renamed to `Item_SoftwareVersion::displaySoftwareByLicense`
- `Item_SoftwareVersion::cloneComputer` renamed to `Item_SoftwareVersion::cloneItem`
- `Transfer::transferComputerSoftwares` renamed to `Transfer::transferItemSoftwares`
- 'getRealQueryForTreeItem()'
- ``getCommonSelect`` and ``getCommonLeftJoin()`` from ``Ticket``, ``Change`` and ``Problem`` are replaced with ``getCommonCriteria()`` compliant with db iterator
- `Config::checkWriteAccessToDirs()`
- `Config::displayCheckExtensions()`
- `Toolbox::checkSELinux()`
- `Toolbox::userErrorHandlerDebug()`
- `Toolbox::userErrorHandlerNormal()`
- `Html::jsDisable()`
- `Html::jsEnable()`
- `Plugin::setLoaded()`
- `Plugin::setUnloaded()`
- `Plugin::setUnloadedByName()`
- Usage of `$LOADED_PLUGINS` global variable
- `CommonDBTM::getRawName()` replaced by `CommonDBTM::getFriendlyName()`
- `Calendar_Holiday::cloneCalendar()`
- `CalendarSegment::cloneCalendar()`
- `Computer_Item::cloneComputer()`
- `Computer_Item::cloneItem()`
- `ComputerAntivirus::cloneComputer()`
- `Contract::cloneItem()`
- `Contract_Item::cloneItem()`
- `ContractCost::cloneContract()`
- `Document_Item::cloneItem()`
- `Infocom::cloneItem()`
- `Item_Devices::cloneItem()`
- `Item_Disk::cloneItem()`
- `Item_OperatingSystem::cloneItem()`
- `Item_SoftwareLicense::cloneComputer()`
- `Item_SoftwareLicense::cloneItem()`
- `Item_SoftwareVersion::cloneComputer()`
- `Item_SoftwareVersion::cloneItem()`
- `Itil_Project::cloneItilProject()`
- `KnowbaseItem_Item::cloneItem()`
- `NetworkPort::cloneItem()`
- `Notepad::cloneItem()`
- `ProjectCost::cloneProject()`
- `ProjectTeam::cloneProjectTask()`
- `ProjectTask::cloneProjectTeam()`
- Usage of `GLPIKEY` constant
- `Toolbox::encrypt()` and `Toolbox::decrypt()` because they use the old encryption aglogithm

#### Removed

- Usage of string `$condition` parameter in `CommonDBTM::find()`
- Usage of string `$condition` parameter in `Dropdown::addNewCondition()`
- Usage of string in `$option['condition']` parameter in `Dropdown::show()`
- `KnowbaseItemCategory::showFirstLevel()`
- `Ticket::getTicketActors()`
- `NotificationTarget::getProfileJoinSql()`
- `NotificationTarget::getDistinctUserSql()`
- `NotificationTargetCommonITILObject::getProfileJoinSql()`
- `RuleCollection::getRuleListQuery()`
- `getNextItem()`
- `getPreviousItem()`
- `CommonDBChild::getSQLRequestToSearchForItem()`
- `CommonDBConnexity::getSQLRequestToSearchForItem()`
- `CommonDBRelation::getSQLRequestToSearchForItem()`
- `Project::addVisibility()`
- `Project::addVisibilityJoins()`
- `Plugin::hasBeenInit()`
- 'SELECT DISTINCT' and 'DISTINCT FIELDS' criteria in `DBmysqlIterator::buildQuery()`
- `CommonDBTM::getTablesOf()`
- `CommonDBTM::getForeignKeyFieldsOf()`
- `TicketFollowup`
- `getDateRequest` and `DbUtils::getDateRequest()`
- `Html::convertTagFromRichTextToImageTag()`
- `Transfer::createSearchConditionUsingArray()`
- Unused constants GLPI_FONT_FREESANS and GLPI_SCRIPT_DIR

## [9.4.6] 2020-05-05

## [9.4.5] 2019-12-18

## [9.4.4] 2019-09-24

### API changes

#### Changes
- For security reasons, autocompletion feature requires now to be authorized by a `'autocomplete' => true` flag in corresponding field search option.

## [9.4.3] 2019-06-20

### API changes

#### Deprecated

The following methods have been deprecated:

- `Html::convertTagFromRichTextToImageTag()`

## [9.4.2] 2019-04-11

### API changes

#### Deprecated

The following methods have been deprecated:

- `CommonDBTM::getTablesOf()`
- `CommonDBTM::getForeignKeyFieldsOf()`

## [9.4.1] 2019-03-15

### API changes

#### Added

- new display hook `timeline_actions` to add new buttons to timeline forms
- Ability to copy document links while merging tickets

#### Deprecated

The following methods have been deprecated:

- `Plugin::hasBeenInit()`
- Deprecate 'SELECT DISTINCT' and 'DISTINCT FIELDS' criteria in `DBmysqlIterator::buildQuery()`

#### Removed

- Drop `CommonITILObject::showSolutions()`.

## [9.4.0] 2019-02-11

### Added

- Ability to link project with problems and tickets.
- Ability to specify creation and modification dates during CommonDBTM object add method
- Add followups to Changes and Problems.
- Add timeline to Changes and Problems.
- CLI console to centralize CLI commands.
- Search on devices from Printers and Network equipments.
- Ability to merge and split tickets.
- Search on devices from Printers and Network equipments.
- Ability to specify creation and modification dates during CommonDBTM object add method.

### Changed
- `license_id` field in `glpi_items_operatingsystems` table has been renamed to `licenseid`
- `olas_tto_id` field in `glpi_tickets` table has been renamed to `olas_id_tto`
- `olas_ttr_id` field in `glpi_tickets` table has been renamed to `olas_id_ttr`
- `ttr_olalevels_id` field in `glpi_tickets` table has been renamed to `olalevels_id_ttr`
- `slas_tto_id` field in `glpi_tickets` table has been renamed to `slas_id_tto`
- `slas_ttr_id` field in `glpi_tickets` table has been renamed to `slas_id_ttr`
- `ttr_slalevels_id` field in `glpi_tickets` table has been renamed to `slalevels_id_ttr`
- `scripts/add_creation_date.php` has been replaced by `glpi:migration:build_missing_timestamps` command available using `bin/console`
- `scripts/checkdb.php` has been replaced by `glpi:database:check` command available using `bin/console`
- `scripts/cliinstall.php` has been replaced by `glpi:database:install` command available using `bin/console`
- `scripts/cliupdate.php` has been replaced by `glpi:database:update` command available using `bin/console`
- `scripts/ldap_mass_sync.php` has been replaced by `glpi:ldap:synchronize_users` command available using `bin/console`
- `scripts/innodb_migration.php` has been replaced by `glpi:migration:myisam_to_innodb` command available using `bin/console`
- `scripts/unlock_tasks.php` has been replaced by `glpi:task:unlock` command available using `bin/console`

### API changes

#### Changes
- Plugins are now loaded in ajax files.
- `TicketFollowup` has been replaced by `ITILFollowup`
- `$num` parameter has been removed from several `Search` class methods:
   - `addSelect()`,
   - `addOrderBy()`,
   - `addHaving()`,
   - `giveItem()`
- `NotificationTarget::getMode()` visibility is now `public`.
- Added `add_recipient_to_target` hook, triggered when a recipient is added to a notification.

#### Deprecated

- Remove `$CFG_GLPI['use_rich_text']` parameter. Will now be `true` per default.
- Remove `$CFG_GLPI['ticket_timeline']` parameter. Will now be `true` per default.
- Remove `$CFG_GLPI['ticket_timeline_keep_replaced_tabs']` parameter. Will now be `false` per default.
- Usage of `TicketFollowup` class has been deprecated.
- Usage of string `$condition` parameter in `CommonDBTM::find()` has been deprecated.
- Usage of string `$condition` parameter in `Dropdown::addNewCondition()` has been deprecated.
- Usage of string in `$option['condition']` parameter in `Dropdown::show()` has been deprecated.

The following methods have been deprecated:

- `KnowbaseItemCategory::showFirstLevel()`
- `Ticket::getTicketActors()`
- `Ticket::processMassiveActionsForOneItemtype()`
- `Ticket::showFormMassiveAction()`
- `Ticket::showMassiveActionsSubForm()`
- `NotificationTarget::getProfileJoinSql()`
- `NotificationTarget::getDistinctUserSql()`
- `NotificationTargetCommonITILObject::getProfileJoinSql()`
- `RuleCollection::getRuleListQuery()`
- `getNextItem()`
- `getPreviousItem()`
- `CommonDBChild::getSQLRequestToSearchForItem()`
- `CommonDBConnexity::getSQLRequestToSearchForItem()`
- `CommonDBRelation::getSQLRequestToSearchForItem()`
- `Project::addVisibility()`
- `Project::addVisibilityJoins()`

#### Removed

- Drop ability to use `JOIN` in `DBmysqlIterator::buildQuery()`
- Drop `NotificationTarget::datas` property
- Drop support of string `$filter` parameter in `Profileuser::getUserProfiles()`
- Drop support of string `$condition` parameter in `User::getFromDBbyEmail()`
- Drop support of string `$condition` parameter in `Group_User::getUserGroups()`
- Drop support of string `$condition` parameter in `Group_User::getGroupUsers()`
- Drop support of string `$condition` parameter in `countElementsInTable` (`DbUtils::countElementsInTable()`)
- Drop support of string `$condition` parameter in `countDistinctElementsInTable` (`DbUtils::countDistinctElementsInTable()`)
- Drop support of string `$condition` parameter in `countElementsInTableForMyEntities` (`DbUtils::countElementsInTableForMyEntities()`)
- Drop support of string `$condition` parameter in `countElementsInTableForEntity` (`DbUtils::countElementsInTableForEntity()`)
- Drop support of string `$condition` parameter in `getAllDatasFromTable` (`DbUtils::getAllDataFromTable()`)
- Drop ITIL Tasks, Followups and Solutions `showSummary()` and massive actions related methods that are replaced with timeline

- Drop class alias `Event` for `Glpi\Event`
- Drop `Zend\Loader\SplAutoloader` interface
- Drop all methods that have been deprecated in GLPI 9.2
  - `_e()`
  - `_ex()`
  - `FieldExists()`
  - `formatOutputWebLink()`
  - `TableExists()`
  - `CommonTreeDropodwn::recursiveCleanSonsAboveID()`
  - `DBMysql::optimize_tables()`
  - `NotificationTarget::addToAddressesList()`
  - `NotificationTarget::getAdditionalTargets()`
  - `NotificationTarget::getAddressesByGroup()`
  - `NotificationTarget::getAddressesByTarget()`
  - `NotificationTarget::getAdminAddress()`
  - `NotificationTarget::getEntityAdminAddress()`
  - `NotificationTarget::getItemAuthorAddress()`
  - `NotificationTarget::getItemGroupAddress()`
  - `NotificationTarget::getItemGroupSupervisorAddress()`
  - `NotificationTarget::getItemGroupTechInChargeAddress()`
  - `NotificationTarget::getItemGroupWithoutSupervisorAddress()`
  - `NotificationTarget::getItemOwnerAddress()`
  - `NotificationTarget::getItemTechnicianInChargeAddress()`
  - `NotificationTarget::getNotificationTargets()`
  - `NotificationTarget::getSpecificTargets()`
  - `NotificationTarget::getUserByField()`
  - `NotificationTarget::getUsersAddressesByProfile()`
  - `NotificationTargetCommonITILObject::getDatasForObject()`
  - `NotificationTargetCommonITILObject::getFollowupAuthor()`
  - `NotificationTargetCommonITILObject::getLinkedGroupByType()`
  - `NotificationTargetCommonITILObject::getLinkedGroupSupervisorByType()`
  - `NotificationTargetCommonITILObject::getLinkedGroupWithoutSupervisorByType()`
  - `NotificationTargetCommonITILObject::getLinkedUserByType()`
  - `NotificationTargetCommonITILObject::getOldAssignTechnicianAddress()`
  - `NotificationTargetCommonITILObject::getRecipientAddress()`
  - `NotificationTargetCommonITILObject::getSupplierAddress()`
  - `NotificationTargetCommonITILObject::getTaskAssignGroup()`
  - `NotificationTargetCommonITILObject::getTaskAssignUser()`
  - `NotificationTargetCommonITILObject::getTaskAuthor()`
  - `NotificationTargetCommonITILObject::getValidationApproverAddress()`
  - `NotificationTargetCommonITILObject::getValidationRequesterAddress()`
  - `NotificationTargetProjectTask::getTeamContacts()`
  - `NotificationTargetProjectTask::getTeamGroups()`
  - `NotificationTargetProjectTask::getTeamSuppliers()`
  - `NotificationTargetProjectTask::getTeamUsers()`
  - `QueuedNotification::sendMailById()`
  - `Ticket::convertContentForNotification()`
  - `User::getPersonalToken()`
  - `User::getUniquePersonalToken()`
- Drop all methods that have been deprecated in GLPI 9.3.0
  - `CommonDBTM::getFromDBByQuery()`
  - `CommonDBTM::getSearchOptions()`
  - `CommonDBTM::getSearchOptionsNew()`
  - `CommonDBTM::getSearchOptionsToAddNew()`
  - `CommonITILObject::getStatusIconURL()`
  - `DBMysql::list_tables()`
  - `Dropdown::showPrivatePublicSwitch()`
  - `NotificationTargetProjectTask::getTeamContacts()`
  - `NotificationTargetProjectTask::getTeamGroups()`
  - `NotificationTargetProjectTask::getTeamSuppliers()`
  - `NotificationTargetProjectTask::getTeamUsers()`
  - `Search::constructDatas()`
  - `Search::displayDatas()`
  - `Transfer::transferComputerDisks()`
- Drop all methods that have been deprecated in GLPI 9.3.1
  - `ComputerVirtualMachine::getUUIDRestrictRequest()`
  - `Config::getSQLMode()`
  - `DBMysql::checkForCrashedTables()`
  - `Html::checkAllAsCheckbox()`
  - `Html::scriptEnd()`
  - `Html::scriptStart()`
  - `Plugin::isAllPluginsCSRFCompliant()`
  - `Profile::getUnderActiveProfileRestrictRequest()`
  - `Toolbox::is_a()`
- Drop all constants that have been deprecated in GLPI 9.3.1
  - `CommonDBTM::ERROR_FIELDSIZE_EXCEEDED`
  - `CommonDBTM::HAS_DUPLICATE`
  - `CommonDBTM::NOTHING_TO_DO`
  - `CommonDBTM::SUCCESS`
  - `CommonDBTM::TYPE_MISMATCH`
- Drop all methods that have been deprecated in GLPI 9.3.2
 - `ITILSolution::removeForItem()`
 - `Session::isViewAllEntities()`

## [9.3.3] 2018-11-27

### Changed

- Fix some cache issues
- Fix reservation tab of an item
- Fix actors notifications massive action
- Improve racks plugins migration script

### API changes

No API changes.

## [9.3.2] 2018-10-26

### API changes

#### Changed

- `Rule::executePluginsActions()` signature has changed
- Javascript function `formatResult()` has been renamed to `templateResult()`

#### Deprecated

The following methods have been deprecated:

- `CommonITILTask::displayTabContentForItem()`
- `CommonITILTask::showFormMassiveAction()`
- `CommonITILTask::showSummary()`
- `ITILSolution::displayTabContentForItem()`
- `ITILSolution::removeForItem()`
- `ITILSolution::showSummary()`
- `Session::isViewAllEntities()`
- `TicketFollowup::processMassiveActionsForOneItemtype()`
- `TicketFollowup::showFormMassiveAction()`
- `TicketFollowup::showMassiveActionsSubForm()`
- `TicketFollowup::showSummary()`
- `Plugin::removeFromSession()`

## [9.3.1] 2018-09-12

### Added
- List receivers folders to choose imported/refused folders

### API changes

#### Deprecated

- Usage of string `$condition` parameter in `Group_User::getUserGroups()` has been deprecated
- Usage of string `$condition` parameter in `Group_User::getGroupUsers()` has been deprecated
- Usage of string `$condition` parameter in `countElementsInTable` (`DbUtils::countElementsInTable()`) has been deprecated
- Usage of string `$condition` parameter in `countDistinctElementsInTable` (`DbUtils::countDistinctElementsInTable()`) has been deprecated
- Usage of string `$condition` parameter in `countElementsInTableForMyEntities` (`DbUtils::countElementsInTableForMyEntities()`) has been deprecated
- Usage of string `$condition` parameter in `countElementsInTableForEntity` (`DbUtils::countElementsInTableForEntity()`) has been deprecated
- Usage of string `$condition` parameter in `getAllDatasFromTable` (`DbUtils::getAllDataFromTable()`) has been deprecated

The following methods have been deprecated:

- `Config::getSQLMode()`
- `DBMysql::checkForCrashedTables()`
- `Html::checkAllAsCheckbox()`
- `Html::scriptEnd()`
- `Html::scriptStart()`
- `Toolbox::is_a()`
- `ComputerVirtualMachine::getUUIDRestrictRequest()`
- `Plugin::isAllPluginsCSRFCompliant()`
- `Profile::getUnderActiveProfileRestrictRequest()`

The following constants have been deprecated:
- `CommonDBTM::ERROR_FIELDSIZE_EXCEEDED`
- `CommonDBTM::HAS_DUPLICATE`
- `CommonDBTM::NOTHING_TO_DO`
- `CommonDBTM::SUCCESS`
- `CommonDBTM::TYPE_MISMATCH`

## [9.3.0] 2018-06-28

### Added
- Add DCIM management
- Add OSM view to set locations and on Search
- Add login source selection
- Add logs purge
- Filter in items logs

### Changed
- Switch MySQL engine from MyIsam to Innodb
- Rework solutions for Tickets, Problems and Changes to support history
- Disks can be attached to network equipments and printers

### API changes

#### Changes
- Added `DB::insert()`, `DB::update()` and `DB::delete()` to replace raw SQL queries
- `CommonITILObject::showMassiveSolutionForm()` now takes a `CommonITILObject` as argument
- `Profileuser::getUserProfiles()` `$filter` parameter is now an array
- `User::getFromDBbyEmail()` `$condition` parameter is now an array
- Select2 javascript component has been upgraded to 4.0 version, see [Migrating from Select2 3.5](https://select2.org/upgrading/migrating-from-35)
- `CommonDevice::getItem_DeviceType()` has a new optional `$devicetype` parameter

#### Deprecated

- Usage of string `$filter` parameter in `Profileuser::getUserProfiles()` has been deprecated
- Usage of string `$condition` parameter in `User::getFromDBbyEmail()` has been deprecated

The following methods have been deprecated:

- `CommonDBTM::getFromDBByQuery()`
- `CommonDBTM::getSearchOptions()`
- `CommonDBTM::getSearchOptionsNew()`
- `CommonDBTM::getSearchOptionsToAddNew()`
- `CommonITILObject::getStatusIconURL()`
- `DBMysql::list_tables()`
- `Dropdown::showPrivatePublicSwitch()`
- `NotificationTargetProject::getTeamContacts()`
- `NotificationTargetProject::getTeamGroups()`
- `NotificationTargetProject::getTeamSuppliers()`
- `NotificationTargetProject::getTeamUsers()`
- `Search::constructDatas()`
- `Search::displayDatas()`
- `Transfer::transferComputerDisks()`

#### Removed

- `CommonITILValidation::isAllValidationsHaveSameStatusForTicket`
- `CommonITILValidation::getNumberValidationForTicket`
- PHPCas library is no longer provided (for licensing issues)

## [9.2.4] 2018-06-21

## [9.2.3] 2018-04-27

## [9.2.2] 2018-03-01


### Deprecated

- `CommonITILValidation::isAllValidationsHaveSameStatusForTicket`
- `CommonITILValidation::getNumberValidationForTicket`
- `DBMysql::optimize_tables()`

## [9.2.1] 2017-11-16

### Added

- Search engine, added ``itemtype_item_revert`` jointype

### Deprecated

- `Ticket::convertContentForNotification()`

## [9.2] 2017-09-25

### Added
- Link knowledge base entries with assets or tickets
- Revisions on knowledge base entries and their translations, with diff view
- Add recursive comments on knowledge base entries
- Direct links to KB article's title for a direct access
- Load minified CSS and JS files (core and plugins) that are generated on release
- Link beetween software licenses
- Alerts on saved searches
- Add ajax browsers notifications in addition to emails
- Plugins can now add new notifications types (xmpp, sms, telegram, ...) to be used along with standard notifications
- Simcard component
- Synchronization field for LDAP
- Improved performances on large entities databases
- Remember me on login
- Fuzzy search
- Paste images in rich text editor
- Add tasks in tickets templates
- Composite tickets (link on sons/parents)
- Telemetry
- Certificates component
- Firmwares components (BIOSes, firwmwares, ...)
- Add OLA management

### Changed
- Many bugs have been fixed
- Display knowledge base category items in tickets using a popup instead of a
new whole window
- Reviewed all richtext editor (tinymce) and their upload parts, now more simpler and intuitive
- Don't ask user to select a template if there is no configured template
- personal_token is not used anymore for api authentication, a new api_token field has been added (empty by default, you should regenerate it)
- Operating systems management has been improved
- Direct language change from any page
- Better icons harmonization

### API changes

#### Changes

- `CommonDBTM::getTable()` signature has changed
- `User::getFromDBbyToken()` signature has changed
- `Bookmark` has been renamed to `SavedSearch`
- Update to latest jsTree plugin
- `RuleDictionnarySoftwareCollection::versionExists()` signature has changed
- `NotificationTemplate::getDataToSend()` signature has changed
- `QueuedMail` has been renamed to `QueuedNotification`
- `CommonDBTM::mailqueueonaction()` has been renamed to `CommonDBTM::notificationqueueonaction()`
- `NotificationTarget::getSender()` no longer takes any parameters (was not used)
- `TableExists()` has been moved to `DBMysql::tableExists()`
- `FieldExists()` has been moved to `DBMysql::fieldExists()`
- `Profile_User::getUserEntitiesForRight()` signature has changed
- `NotificationTarget` property `datas` has been renamed to `data`

#### Deprecated

- Ability to use `JOIN` in `DBmysqlIterator::buildQuery()` has been deprecated
- Usage of `NotificationTarget::datas` property has been deprecated
- Usage of `Zend\Loader\SplAutoloader` interface has been deprecated

The following methods have been deprecated:

- `_e()`
- `_ex()`
- `Bookmark::mark_default()`
- `Bookmark::unmark_default()`
- `CommonTreeDropodwn::recursiveCleanSonsAboveID()`
- `NotificationTarget::addToAddressesList()`
- `NotificationTarget::getAdditionalTargets()`
- `NotificationTarget::getAddressesByGroup()`
- `NotificationTarget::getAddressesByTarget()`
- `NotificationTarget::getAdminAddress()`
- `NotificationTarget::getEntityAdminAddress()`
- `NotificationTarget::getItemAuthorAddress()`
- `NotificationTarget::getItemGroupAddress()`
- `NotificationTarget::getItemGroupSupervisorAddress()`
- `NotificationTarget::getItemGroupTechInChargeAddress()`
- `NotificationTarget::getItemGroupWithoutSupervisorAddress()`
- `NotificationTarget::getItemOwnerAddress()`
- `NotificationTarget::getItemTechnicianInChargeAddress()`
- `NotificationTarget::getNotificationTargets()`
- `NotificationTarget::getSpecificTargets()`
- `NotificationTarget::getUserByField()`
- `NotificationTarget::getUsersAddressesByProfile()`
- `NotificationTargetCommonITILObject::getDatasForObject()`
- `NotificationTargetCommonITILObject::getFollowupAuthor()`
- `NotificationTargetCommonITILObject::getLinkedGroupByType()`
- `NotificationTargetCommonITILObject::getLinkedGroupSupervisorByType()`
- `NotificationTargetCommonITILObject::getLinkedGroupWithoutSupervisorByType()`
- `NotificationTargetCommonITILObject::getLinkedUserByType()`
- `NotificationTargetCommonITILObject::getOldAssignTechnicianAddress()`
- `NotificationTargetCommonITILObject::getRecipientAddress()`
- `NotificationTargetCommonITILObject::getSupplierAddress()`
- `NotificationTargetCommonITILObject::getTaskAssignGroup()`
- `NotificationTargetCommonITILObject::getTaskAssignUser()`
- `NotificationTargetCommonITILObject::getTaskAuthor()`
- `NotificationTargetCommonITILObject::getValidationApproverAddress()`
- `NotificationTargetCommonITILObject::getValidationRequesterAddress()`
- `NotificationTargetProjectTask::getTeamContacts()`
- `NotificationTargetProjectTask::getTeamGroups()`
- `NotificationTargetProjectTask::getTeamSuppliers()`
- `NotificationTargetProjectTask::getTeamUsers()`
- `QueuedNotification::sendMailById()`
- `User::getPersonalToken()`
- `User::getUniquePersonalToken()`
- `formatOutputWebLink()`

#### Removals

The following methods have been dropped:

- `Ajax::displaySearchTextForDropdown()`
- `Ajax::getSearchTextForDropdown()`
- `Bookmark::changeBookmarkOrder()`
- `Bookmark::moveBookmark()`
- `CommonGLPI::addDivForTabs()`
- `CommonGLPI::showTabs()`
- `CommonGLPI::showNavigationHeaderOld()`
- `CommonGLPI::show()`
- `Dropdown::showInteger()`
- `DBMysql::field_flags()`
- `Html::showDateFormItem()`
- `Html::showDateTimeFormItem()`
- `Profile::dropdownNoneReadWrite()`
- `Toolbox::get_magic_quotes_runtime()`
- `Toolbox::get_magic_quotes_gpc()`
- `Dropdown::showAllItems()`

For older entries, please check [GLPI website](http://glpi-project.org).
