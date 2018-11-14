# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [9.4] unreleased

### Added

- Ability to link project with problems and tickets.
- Add followups to Changes and Problems
- Add timeline to Changes and Problems
- Search on devices from Printers and Network equipments

### Changed
- `license_id` field in `glpi_items_operatingsystems` table has been renamed to `licenseid`

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

#### Deprecated

- Remove `$CFG_GLPI['use_rich_text']` parameter. Will now be `true` per default.
- Remove `$CFG_GLPI['ticket_timeline']` parameter. Will now be `true` per default.
- Remove `$CFG_GLPI['ticket_timeline_keep_replaced_tabs']` parameter. Will now be `false` per default.
- Usage of `TicketFollowup` class has been deprecated.
- Usage of string `$condition` parameter in `CommonDBTM::find()` has been deprecated

The following methods have been deprecated:

- `KnowbaseItemCategory::showFirstLevel()`
- `Ticket::getTicketActors()`
- `Ticket::processMassiveActionsForOneItemtype()`
- `Ticket::showFormMassiveAction()`
- `Ticket::showMassiveActionsSubForm()`
- `NotificationTarget::getProfileJoinSql()`
- `NotificationTarget::getDistinctUserSql()`
- `RuleCollection::getRuleListQuery()`
- `getNextItem()`
- `getPreviousItem()`

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


## [9.3.2] unreleased

### API changes

#### Changed

- `Rule::executePluginsActions()` signature has changed
- Javascript function `formatResult()` has been renamed to `templateResult()`

#### Deprecated

The following methods have been deprecated:

- `ITILSolution::removeForItem()`
- `Session::isViewAllEntities()`
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
