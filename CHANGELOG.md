# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [10.0.20] unreleased

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.19] 2025-07-16

### Added

### Changed
- Only unsolved/unclosed tickets will be shown in the dropdown when performing the "Merge as Followup" action.
- Domain records must be attached to a domain. Existing unattached records will remain but will require a domain if edited.
- Inactive suppliers are hidden in assigned technician dropdown results. This does not affect items already assigned to inactive suppliers.
- Requesting an item with ID 0 (except for entities) from the API will now return a 404 instead of listing all items of the itemtype.

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.18] 2025-02-12

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.17] 2024-11-06

### Added

### Changed
- Searching IDs in dropdowns now matches the beginning of the ID instead of anywhere in the ID.

### Deprecated

### Removed

### API changes

#### Added

- `NotificationTarget::canNotificationContentBeDisclosed()` method that can be overriden to indicates whether a notification contents should be undisclosed.

#### Changes

#### Deprecated

#### Removed


## [10.0.16] 2024-07-03

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.15] 2024-04-24

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.14] 2024-03-14

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.13] 2024-03-13

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes
- `condition` and `displaywith` parameters must now be added in IDOR token creation data when they are not empty.

#### Deprecated

#### Removed


## [10.0.12] 2024-02-01

### Added

### Changed
- Permissions for historical data and system logs (Administration > Logs) are now managed by "Historical (READ)" and "System Logs (READ)" respectively.

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated
- `Entity::cleanEntitySelectorCache()` no longer has any effect as the entity selector is no longer cached as a unique entry

#### Removed


## [10.0.11] 2023-12-13

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated
- Usage of the `DBmysql::query()` method is deprecated, for security reasons, as it is most of the time used in an insecure way.
  To execute DB queries, either `DBmysql::request()` can be used to craft query using the GLPI query builder,
  either `DBmysql::doQuery()` can be used for safe queries to execute DB query using a self-crafted SQL string.
  This deprecation will not trigger any error, unless the `GLPI_STRICT_DEPRECATED` constant is set to `true`, to avoid
  cluttering error logs.

#### Removed


## [10.0.10] 2023-09-25

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.9] 2023-07-11

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.8] 2023-07-05

### Added
- Unified Debug bar feature has been added to display debug information in the browser as a replacement and expansion on the previous, individual debug panels.

### Changed

### Deprecated

### Removed
- Debug panels and the toggle button to show/hide the primary debug panel that was next to the current user's name in the top right corner of the screen have been removed.
- `debug_tabs` plugin hook

### API changes

#### Added
- `CommonDBTM::getMessageReferenceEvent()` method that can be overridden to tweak notifications grouping in mail clients.

#### Changes

#### Deprecated
- `Html::displayDebugInfo()` method no longer has any effect. The functionality was replaced by the new Debug Bar feature.
- `Hooks::DEBUG_TABS`
- `$TIMER_DEBUG` global variable.
- `$DEBUG_SQL` global variable.
- `$SQL_TOTAL_REQUEST` global variable.
- `$DEBUG_SQL` global variable.
- `$CFG_GLPI['debug_sql']` configuration option.
- `$CFG_GLPI['debug_vars']` configuration option.

- Usage of parameter `$clean` in `AuthLDAP::getObjectByDn()` and `AuthLDAP::getUserByDn()`.

#### Removed


## [10.0.7] 2023-04-05

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes
- Itemtype that can be linked to a disk are now declared in `$CFG_GLPI['disk_types']`.

#### Deprecated
- `Glpi\Inventory\Conf::importFile()`
- `RSSFeed::showDiscoveredFeeds()`
- `Toolbox::checkValidReferer()`

#### Removed

## [10.0.6] 2023-01-24

### Added

### Changed
- `glpi:` command prefix has been removed from console commands canonical name.

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed

## [10.0.5] 2022-11-04

## [10.0.4] 2022-11-03

## [10.0.3] 2022-09-14

### API changes

#### Added

- `CommonDBTM::pre_addToDB()` added.

#### Removed

## [10.0.2] 2022-06-28

## [10.0.1] 2022-06-02

### Changed
- PDF export library has been changed back from `mPDF` to `TCPDF`.

### Removed
- Gantt feature has been moved into the `gantt` plugin.

### API changes

#### Added
- `plugin_xxx_activate()` and `plugin_xxx_deactivate` hooks support.

#### Changes
- `Glpi\Api\Api::initEndpoint()` visibility changed to `protected`.

#### Removed
- `GlpiGantt` javascript helper and `dhtmlx-gantt` library.
- `Glpi\Gantt` namespace and all corresponding classes.
- `Project::getDataToDisplayOnGantt()`
- `Project::showGantt()`
- `ProjectTask::getDataToDisplayOnGantt()`
- `ProjectTask::getDataToDisplayOnGanttForProject()`

## [10.0.0] 2022-04-20

### Added
- Added UUID to all other itemtypes that are related to Operating Systems (Phones, Printers, etc)
- Added a button to the General > System configuration tab to copy the system information

### Changed
- APCu and WinCache are not anymore use by GLPI, use `php bin/console cache:configure` command to configure cache system.
- PDF export library has been changed from `TCPDF` to `mPDF`.
- The search engine and search results page now support sorting by multiple fields.
- The search result lists now refresh/update without triggering a full page reload.
- Replaced user-facing cases of master/slave usage replaced with main/replica.

### Deprecated
- Usage of XML-RPC API is deprecated.
- The database "slaves" property in the status checker (/status.php and glpi:system:status) is deprecated. Use "replicas" instead,
- The database "master" property in the status checker (/status.php and glpi:system:status) is deprecated. Use "main" instead,

### Removed
- Autocomplete feature on text fields.
- Usage of alternative DB connection encoding (`DB::$dbenc` property).
- Deprecated `scripts/ldap_mass_sync.php` has been removed in favor of `glpi:ldap:synchronize_users` command available using `bin/console`
- Deprecated `scripts/compute_dictionary.php` has been removed in favor of `glpi:rules:replay_dictionnary_rules` command available using `bin/console`
- Deprecated `scripts/softcat_mass_compute.php` has been removed in favor of `glpi:rules:process_software_category_rules` command available using `bin/console`

### API changes

#### Added
- Added `DBMysql::setSavepoint()` to create savepoints within a transaction.
- Added `CommonDBTM::showForm()` to have a generic showForm for asset (based on a twig template).

#### Changes
- MySQL warnings are now logged in SQL errors log.
- `Guzzle` library has been upgraded to version 7.4.
- `Symfony\Console` library has been upgraded to version 5.4.
- `CommonGLPI` constructor signature has been declared in an interface (`CommonGLPIInterface`).
- `DBmysqlIterator` class compliancy with `Iterator` has been fixed (i.e. `DBmysqlIterator::next()` does not return current row anymore).
- `Domain` class inheritance changed from `CommonDropdown` to `CommonDBTM`.
- `showForm()` method of all classes inheriting `CommonDBTM` have been changed to match `CommonDBTM::showForm()` signature.
- Format of `Message-Id` header sent in Tickets notifications changed to match format used by other items.
- Added `DB::truncate()` to replace raw SQL queries
- Impact context `positions` field type changed from `TEXT` to `MEDIUMTEXT`
- Field `date` of KnowbaseItem has been renamed to `date_creation`.
- Field `date_creation` of KnowbaseItem_Revision has been renamed to `date`.
- Field `date_creation` of NetworkPortConnectionLog has been renamed to `date`.
- Field `date` of Notepad has been renamed to `date_creation`.
- Field `date_mod` of ObjectLock has been renamed to `date`.
- Field `date` of ProjectTask has been renamed to `date_creation`.
- Table `glpi_netpoints` has been renamed to `glpi_sockets`.
- `GLPI_FORCE_EMPTY_SQL_MODE` constant has been removed in favor of `GLPI_DISABLE_ONLY_FULL_GROUP_BY_SQL_MODE` usage.
- `CommonDBTM::clone()`, `CommonDBTM::prepareInputForClone()` and `CommonDBTM::post_clone()` has been removed. Clonable objects must now use `Glpi\Features\Clonable` trait.
- `CommonDBTM::notificationqueueonaction` property has been removed in favor of `CommonDBTM::deduplicate_queued_notifications` property.
- `CommonDropdown::displaySpecificTypeField()` has a new `$options` parameter.
- `DBMysql::rollBack` supports a `name` parameter for rolling back to a savepoint.
- `Knowbase::getJstreeCategoryList()` as been replaced by `Knowbase::getTreeCategoryList()`.
- `NetworkPortInstantiation::showNetpointField()` has been renamed to `NetworkPortInstantiation::showSocketField()`.
- `NotificationSettingConfig::showForm()` renamed to `NotificationSettingConfig::showConfigForm()`.
- `RuleMatchedLog::showForm()` renamed to `RuleMatchedLog::showItemForm()`.
- `Search::addOrderBy()` signature changed.
- `TicketSatisfaction::showForm()` renamed to `TicketSatisfaction::showSatisfactionForm()`.
- `Transfer::transferDropdownNetpoint()` has been renamed to `Transfer::transferDropdownSocket()`.

#### Deprecated
- Usage of `MyISAM` engine in database, in favor of `InnoDB` engine.
- Usage of `utf8mb3` charset/collation in database in favor of `utf8mb4` charset/collation.
- Usage of `datetime` field type in database, in favor of `timestamp` field type.
- Handling of encoded/escaped value in `autoName()`
- `Netpoint` has been deprecated and replaced by `Socket`
- `CommonDropdown::displayHeader()`, use `CommonDropdown::displayCentralHeader()` instead and make sure to override properly `first_level_menu`, `second_level_menu` and `third_level_menu`.
- `GLPI::getLogLevel()`
- `Html::clean()`
- `MailCollector::listEncodings()`
- `RuleImportComputer` class
- `RuleImportComputerCollection` class
- `SLM::setTicketCalendar()`
- `Toolbox::clean_cross_side_scripting_deep()`
- `Toolbox::endsWith()`
- `Toolbox::filesizeDirectory()`
- `Toolbox::getHtmlToDisplay()`
- `Toolbox::logError()`
- `Toolbox::logNotice()`
- `Toolbox::logWarning()`
- `Toolbox::sodiumDecrypt()`
- `Toolbox::sodiumEncrypt()`
- `Toolbox::startsWith()`
- `Toolbox::unclean_cross_side_scripting_deep()`

#### Removed
- `jQueryUI` has been removed in favor of `twbs/bootstrap`. This implies removal of following widgets: `$.accordion`, `$.autocomplete`,
  `$.button`, `$.dialog`, `$.draggable`, `$.droppable`, `$.progressbar`, `$.resizable`, `$.selectable`, `$.sortable`, `$.tabs`, `$.tooltip`.
- Usage of `$order` parameter in `getAllDataFromTable()` (`DbUtils::getAllDataFromTable()`)
- Usage of `table` parameter in requests made to `ajax/comments.php`
- Usage of `GLPI_FORCE_EMPTY_SQL_MODE` constant
- Usage of `GLPI_PREVER` constant
- Support of `doc_types`, `helpdesk_types` and `netport_types` keys in `Plugin::registerClass()`
- `$CFG_GLPI['layout_excluded_pages']` entry
- `$CFG_GLPI['transfers_id_auto']` entry
- `$CFG_GLPI['use_ajax_autocompletion']` entry
- `$DEBUG_AUTOLOAD` global variable
- `$LOADED_PLUGINS` global variable
- `$PHP_LOG_HANDLER` global variable
- `$SQL_LOG_HANDLER` global variable
- `CommonDBTM::notificationqueueonaction` property
- `NotificationTarget::html_tags` property
- `getAllDatasFromTable()`
- `getRealQueryForTreeItem()`
- `Ajax::createFixedModalWindow()`
- `Ajax::createSlidePanel()`
- `Calendar_Holiday::cloneCalendar()`
- `Calendar::duplicate()`
- `CalendarSegment::cloneCalendar()`
- `Change::getCommonLeftJoin()`
- `Change::getCommonSelect()`
- `Change::showAnalysisForm()`
- `Change::showPlanForm()`
- `CommonDBTM::clone()`
- `CommonDBTM::getRawName()`
- `CommonDBTM::prepareInputForClone()`
- `CommonDBTM::post_clone()`
- `CommonDBTM::showDates()`
- `CommonGLPI::isLayoutExcludedPage()`
- `CommonGLPI::isLayoutWithMain()`
- `CommonGLPI::showPrimaryForm()`
- `CommonITILObject::displayHiddenItemsIdInput()`
- `CommonITILObject::filterTimeline()`
- `CommonITILObject::getActorIcon()`
- `CommonITILObject::getSplittedSubmitButtonHtml()`
- `CommonITILObject::showActorsPartForm()`
- `CommonITILObject::showFormHeader()`
- `CommonITILObject::showGroupsAssociated()`
- `CommonITILObject::showSupplierAddFormOnCreate()`
- `CommonITILObject::showSuppliersAssociated()`
- `CommonITILObject::showTimeline()`
- `CommonITILObject::showTimelineForm()`
- `CommonITILObject::showTimelineHeader()`
- `CommonITILObject::showUsersAssociated()`
- `Computer_Item::cloneComputer()`
- `Computer_Item::cloneItem()`
- `Computer_SoftwareLicense` class
- `Computer_SoftwareVersion` class
- `ComputerAntivirus::cloneComputer()`
- `Contract::cloneItem()`
- `Contract_Item::cloneItem()`
- `ContractCost::cloneContract()`
- `Config::agreeDevMessage()`
- `Config::checkWriteAccessToDirs()`
- `Config::displayCheckExtensions()`
- `Config::getCache()`
- `DBMysql::affected_rows()`
- `DBMysql::areTimezonesAvailable()`
- `DBMysql::data_seek()`
- `DBMysql::fetch_array()`
- `DBMysql::fetch_assoc()`
- `DBMysql::fetch_object()`
- `DBMysql::fetch_row()`
- `DBMysql::field_name()`
- `DBMysql::free_result()`
- `DBmysql::getTableSchema()`
- `DBMysql::insert_id()`
- `DBMysql::isMySQLStrictMode()`
- `DBMysql::list_fields()`
- `DBMysql::notTzMigrated()`
- `DBMysql::num_fields()`
- `DbUtils::getRealQueryForTreeItem()`
- `Dropdown::getDropdownNetpoint()`
- `DCBreadcrumb::showDcBreadcrumb()`
- `Document_Item::cloneItem()`
- `Entity::showSelector()`
- `Glpi\Marketplace\Api\Plugins::getNewPlugins()`
- `Glpi\Marketplace\Api\Plugins::getPopularPlugins()`
- `Glpi\Marketplace\Api\Plugins::getTopPlugins()`
- `Glpi\Marketplace\Api\Plugins::getTrendingPlugins()`
- `Glpi\Marketplace\Api\Plugins::getUpdatedPlugins()`
- `Html::autocompletionTextField()`
- `Html::displayImpersonateBanner()`
- `Html::displayMainMenu()`
- `Html::displayMenuAll()`
- `Html::displayTopMenu()`
- `Html::fileForRichText()`
- `Html::generateImageName()`
- `Html::imageGallery()`
- `Html::jsDisable()`
- `Html::jsEnable()`
- `Html::nl2br_deep()`
- `Html::replaceImagesByGallery()`
- `Html::resume_name()`
- `Html::setSimpleTextContent()`
- `Html::setRichTextContent()`
- `Html::showProfileSelecter()`
- `Html::weblink_extract()`
- `Infocom::cloneItem()`
- `Itil_Project::cloneItilProject()`
- `ITILFollowup::showApprobationForm()`
- `ITILTemplate::getBeginHiddenFieldText()`
- `ITILTemplate::getBeginHiddenFieldValue()`
- `ITILTemplate::getEndHiddenFieldText()`
- `ITILTemplate::getEndHiddenFieldValue()`
- `Item_Devices::cloneItem()`
- `Item_Disk::cloneItem()`
- `Item_OperatingSystem::cloneItem()`
- `Item_SoftwareLicense::cloneComputer()`
- `Item_SoftwareLicense::cloneItem()`
- `Item_SoftwareVersion::cloneComputer()`
- `Item_SoftwareVersion::cloneItem()`
- `Item_SoftwareVersion::showForComputer()`
- `Item_SoftwareVersion::updateDatasForComputer()`
- `KnowbaseItem_Item::cloneItem()`
- `LevelAgreement::showForTicket()`
- `NetworkPort::cloneItem()`
- `Notepad::cloneItem()`
- `NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()`
- `Plugin::getGlpiPrever()`
- `Plugin::isGlpiPrever()`
- `Plugin::setLoaded()`
- `Plugin::setUnloaded()`
- `Plugin::setUnloadedByName()`
- `Problem::getCommonLeftJoin()`
- `Problem::getCommonSelect()`
- `Problem::showAnalysisForm()`
- `ProjectCost::cloneProject()`
- `ProjectTeam::cloneProjectTask()`
- `ProjectTask::cloneProjectTeam()`
- `Reservation::displayReservationDay()`
- `Reservation::displayReservationsForAnItem()`
- `Search::isDeletedSwitch()`
- `Ticket::getCommonLeftJoin()`
- `Ticket::getCommonSelect()`
- `Ticket::getTicketTemplateToUse()`
- `Ticket::showDocumentAddButton()`
- `Ticket_Ticket::displayLinkedTicketsTo()`
- `TicketTemplate::getFromDBWithDatas()`
- `Toolbox::canUseImapPop()`
- `Toolbox::checkSELinux()`
- `Toolbox::commonCheckForUseGLPI()`
- `Toolbox::convertImageToTag()`
- `Toolbox::decrypt()`
- `Toolbox::doubleEncodeEmails()`
- `Toolbox::encrypt()`
- `Toolbox::getGlpiSecKey()`
- `Toolbox::removeHtmlSpecialChars()`
- `Toolbox::sanitize()`
- `Toolbox::throwError()`
- `Toolbox::unclean_html_cross_side_scripting_deep()`
- `Toolbox::useCache()`
- `Toolbox::userErrorHandlerDebug()`
- `Toolbox::userErrorHandlerNormal()`
- `Transfer::transferComputerSoftwares()`
- `Update::declareOldItems()`
- `User::showPersonalInformation()`

## [9.5.7] 2022-01-27

## [9.5.6] 2021-09-15

### Changed

- `X-Forwarded-For` header value is no longer used during API access controls, API requests passing through proxies may be refused for security reasons.

### API changes

#### Changed

- All POST request made to `/ajax/` scripts are now requiring a valid CSRF token in their `X-Glpi-Csrf-Token` header.
Requests done using jQuery are automatically including this header, from the moment that the page header is built using
`Html::includeHeader()` method and the `js/common.js` script is loaded.

#### Deprecated

- Usage of "followups" option in `CommonITILObject::showShort()`
- `CommonITILTask::showInObjectSumnary()`
- `ITILFollowup::showShortForITILObject()`

## [9.5.5] 2021-04-13

### API changes

#### Changed

- Remove deprecation of `Search::getMetaReferenceItemtype()`

## [9.5.4] 2021-03-02

### Changed

- `iframe` elements are not anymore allowed in rich text unless `GLPI_ALLOW_IFRAME_IN_RICH_TEXT` constant is defined to `true`

### API changes

#### Deprecated

- `Search::getMetaReferenceItemtype()`

## [9.5.3] 2020-11-25

### Deprecated
- Usage of alternative DB connection encoding (`DB::$dbenc` property).

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
- `Toolbox::encrypt()` and `Toolbox::decrypt()` because they use the old encryption algorithm

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
- `CommonDBTM::mailqueueonaction` has been renamed to `CommonDBTM::notificationqueueonaction`
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
