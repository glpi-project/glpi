# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [11.0.1] 2025-10-09

### Added

### Changed

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [11.0.0] 2025-10-01

### Added
- Business Rules for Changes
- Business Rules for Problems
- Configurable toast notification location
- `Link ITIL Object` and `Unlink ITIL Object` massive actions for Tickets, Changes, and Problems.
- Group approval requests (any user from group can approve).
- Approval requests are grouped by validation step. 
- Satisfaction surveys for Changes
- New right for assigning service levels to ITIL Objects (UPDATE right also still allows this).
- New right for impersonation of users with fewer rights (Config UPDATE right also still allows this globally).
- Marketplace availability configuration.
- Toast popup message location configuration.
- Datacenter room grid size configuration (per room).
- Approval reminder automatic action.
- Reservation massive actions.
- `alias` and `code` fields for Locations.
- Profile cloning.
- Associated elements for recurring ITIL Objects.
- Processes and Environment Variable tabs for automatically inventoried assets.
- Log viewer for logs in `files/_log` directory.
- Custom palette/theme support (uses `files/_themes` directory by default).
- Two-Factor Authentication (2FA) support via Time-based One-time Password (TOTP).
- `Deny login` authorization rule action to deny login for a user, but not prevent the import/existence of the user in GLPI.
- Directly capture screenshots or screen recordings from the "Add a document" form in tickets.
- With a clean install, dashboards now show fake/placeholder data by default with a message indicating you are viewing demonstration data and a button to disable it.
- Assets that can be assigned to users/groups have new "View assigned" and "Update assigned" rights which give read/update access to users and groups assigned to the asset.
- `ODS` and `XLS` export of search results.
- Support for the well-known `change-password" URI which can be used by some password managers to automatically (or assist with) changing a user's password.
- CLI commands for creating local GLPI users, enabling/disabling/deleting users, resetting local GLPI user passwords and granting profile assignments.
- Cloning templates (such as computer templates)
- Creating a template from an existing item (such as a computer). This action is only available from the Actions menu within the item form (bulk action not allowed).
- Massive action for users to reapply authorization assignment rules.
- Massive action for users to send a password reset email.

### Changed
- ITIL Objects can now be linked to any other ITIL Objects similar to the previous Ticket/Ticket links.
- Logs are now shown using the Search Engine
- The approval option previously named `Group` is now called `Group user(s)` to better reflect that it shows a list of users from a specific group rather than allow sending an approval for a group.
- The ticket business rule action `Ticket category from code` was renamed to `ITIL category from code`.
- The ticket business rule criteria `Code representing the ticket category` was renamed to `Code representing the ITIL category`.
- The ticket business rule criteria `Ticket location` was renamed to `Location`.
- ITIL Templates can now restrict available statuses.
- Improved offline mode for marketplace.
- Lines can be assigned directly to assets without needing a SIM card.
- Planning event occurances can be detached from a series.
- Locations are now displayed in Datacenter breadcrumbs.
- Marketplace CLI download command now supports downloading specific versions of plugins.
- Browse tab of Knowledgebase now uses the Search Engine.
- Satisfaction surveys can now be configured with a custom maximum score, default score, and if a comment is required.
- LDAP TLS version can now be specified.
- Kanabn view state can be reset from the Kanban if the user has the right to modify the view.
- Personal reminders in central view now include only non-public reminders created by the user.
- Public reminders in central view now include public reminders regardless of who created them.
- Project description field is now a rich text field.
- Entity, profile, debug mode flag, and language are restored after ending impersonation.
- Volumes now show `Used percentage` instead of `Free percentage`.
- Budget "Main" tab now shows negative values for "Total remaining in the budget" in parentheses instead of with a negative sign to align with typical accounting practices.
- Followups and Tasks no longer visible without the "See public" or "See private" rights even if the user has permission to be assigned the parent ITIL Object.
- Followups, Tasks and Solutions now check the `canView()` method of the parent ITIL Object rather than just the "See my/See author" right of the parent item.
  This means they now take into account "See all", "See group", etc. rights for the global permission check.
  Permission checks at the item-level have not been changed.
- External Links `Link or filename` and `File content` fields now use Twig templates instead of a custom tag syntax.
- Itemtypes associated with External links are now in the main form rather than a separate tab.
- The `Computer_Item` class has been replaced by the `\Glpi\Asset\Asset_PeripheralAsset` class.
- List of network ports in a VLAN form now shows the NetworkPort link in a breadcrumb manner (MyServer > eth0 where MyServer is a link to the computer and eth0 is a link to the port).
- Running `front/cron.php` or `bin/console` will attempt to check and block execution if running as root.
- Testing LDAP replicates now shows results as toast notifications rather than inside the replicate tab after a page reload.
- The debug tab that was present, for some items, when the debug mode was active, no longer exists. The corresponding features have been either moved, either removed.
- `Group` and `Group in charge` fields for assets may now contain multiple groups.
- "If software are no longer used" transfer option is now taken into account rather than always preserving.
- Notifications can now specify exclusions for recipients.
- Warranty expiration alerts no longer trigger for deleted items.
- New UI for searching for Ticket/Change/Problem solutions from the Knowledgebase.
- Validations are only allowed on Tickets and Changes that are not solved or closed.
- Searching project tasks in the legacy API is no longer restricted to only tasks the user is assigned to.
- Renamed `From email header` and `To email header` criteria in the mails receiver rules to `From email address` and `To email address` respectively.
- Replaced text mentions of "Validations" with "Approvals" to unify the terminology used.
- User passwords are no longer wiped when the authentication source/server doesn't actually change during "Change of the authentication method" action.
- Single item actions (Actions menu in item form) are now filtered by certain attributes of the item. For example, a Computer which has reservations enabled will not show the `Authorize reservations` action.
- Improved labels in component link dropdown to only include textual information and hide normally hidden data.
- Component types list is now categorized similar to the dropdown types list.

### Deprecated
- Survey URL tags `TICKETCATEGORY_ID` and `TICKETCATEGORY_NAME` are deprecated and replaced by `ITILCATEGORY_ID` and `ITILCATEGORY_NAME` respectively.

### Removed
- XML-RPC API.
- `Link tickets` massive action for Tickets (Use `Link ITIL Object` instead).
- `Link to a problem` massive action for Tickets (Use `Link ITIL Object` instead).
- Manage tab for Knowledgebase (Unpublished is now a toggle in the browse tab).
- The database "master" property in the status checker (/status.php and glpi:system:status), replaced by "main".
- The database "slaves" property in the status checker (/status.php and glpi:system:status), replaced by "replicas".
- API URL is no longer customizable within GLPI. Use alias/rewrite rules in your web server configuration instead if needed.
- `status.php` and `bin/console system:status` no longer supports plain-text output.
- `Glpi\System\Status\StatusChecker::getServiceStatus()` `as_array` parameter.
- `Sylk` export of search results.
- `full_width_adapt_column` option for fields macros has been removed.
- `Picture` search option (ID 150) for Users.

### API changes

#### Added
- `phpCAS` library is now bundled in GLPI, to prevent version compatibility issues.
- `Glpi\DBAL\QueryFunction` class with multiple static methods for building SQL query function strings in an abstract way.
- `fetchSessionMessages()` global JS function to display new session messages as toast notifications without requiring a page reload.
- `is_exclusion` column added to `glpi_notificationtargets` table.
- `CommonDBTM::getForbiddenMultipleMassiveActions()` method to allow specifying which actions should only be shown from the item form.

#### Changes
- Many methods have their signature changed to specify both their return type and the types of their parameters.
- `chartist` library has been replaced by `echarts`.
- `codemirror` library has been replaced by `monaco-editor`.
- `htmLawed` library has been replaced by `symfony/html-sanitizer`.
- `monolog/monolog` has been upgraded to version 3.3.
- `photoswipe` library has been upgraded to version 5.x.
- `phpmailer/phpmailer` library has been replaced by `symfony/mailer`.
- `true/punycode` library has been removed.
- `Symfony` libraries have been upgraded to version 6.0.
- `users_id_validate` field in `CommonITILValidation` will now have a `0` value until someone approves or refuses the validation.
  Approval targets (who the approval is for) is now indicated by `itemtype_target` and `items_id_target` fields.
- Notifications are not deduplicated anymore.
- Notifications with `Approver` recipient have had this recipient replaced with the new `Approval target` recipient to maintain previous behavior as much as possible.
  The previous recipient option still exists if needed. This replacement will only happen once during the upgrade.
- `GLPIMailer` mailer class does not extends anymore `PHPMailer\PHPMailer\PHPMailer`.
  We added a compatibility layer to handle main usages found in plugins, but we cannot ensure compatibility with all properties and methods that were inherited from `PHPMailer\PHPMailer\PHPMailer`.
- `CommonGLPI::$othertabs` static property has been made private.
- `CommonGLPI::createTabEntry()` signature changed.
- `CommonITILValidation::showSummary()` method has been made private.
- All types of rules are now sortable and ordered by ranking.
- Plugins console commands must now use the normalized prefix `plugins:XXX` where `XXX` is the plugin key.
- GLPI web root is now the `/public` directory and all web request to PHP scripts are proxified by `public/index.php` script.
- Usage of `DBmysql::query()` and `DBmysql::queryOrDie()` method are prohibited to ensure that legacy unsafe DB are no more executed.
  Building and executing raw queries using `DBmysql::request()`, `DBmysqlIterator::buildQuery()` and `DBmysqlIterator::execute()` methods is also prohibited.
  To execute DB queries, either `DBmysql::request()` can be used to craft query using the GLPI query builder,
  or `DBmysql::doQuery()` can be used for safe queries to execute DB query using a self-crafted a SQL string.
- The dynamic progress bar provided by the `Html` class no longer works. The `ProgressIndicator` JS module can be used as a replacement to display the progress of a process.
- `js/fuzzysearch.js` replaced with `FuzzySearch/Modal` Vue component.
- `Html::fuzzySearch()` replaced with `Html::getMenuFuzzySearchList()` function.
- `NotificationEvent::raiseEvent()` signature cahnged. A new `$trigger` parameter has been added at 4th position, and `$label` is now the 5th parameter.
- `NotificationEventInterface::raise()` has a new `$trigger` parameter.
- `QueryExpression` class moved to `Glpi\DBAL` namespace.
- `QueryParam` class moved to `Glpi\DBAL` namespace.
- `QuerySubQuery` class moved to `Glpi\DBAL` namespace.
- `QueryUnion` class moved to `Glpi\DBAL` namespace.
- `PrinterLog::getMetrics()` method has been made final.
- `SavedSearch::showSaveButton()` replaced with `pages/tools/savedsearch/save_button.html.twig` template.
- `showSystemInformations` method for `$CFG_GLPI['systeminformations_types']` types renamed to `getSystemInformation` and should return an array with a label and content.
- `DisplayPreference` config form POST handling moved to `ajax/displaypreference.php` script. The front file is for displaying the tabs only.
- `Document::send()` signature changed. The `$context` parameter has been removed.
- `title` property of Kanban items must be text only. HTML no longer supported.
- `kanban:filter` JS event now includes the columns in the event data. Filtering must set the `_filtered_out` property of cards to hide them instead of changing the elements in the DOM.
- `CommonITILActor::getActors()` signature changed. The `$items_id` parameter must strictly be an integer.
- The `date_mod` property for historical entries returned by `Log::getHistoryData` is no longer formatted based on the user's preferences.
- `Rule::dropdownRulesMatch()` has been made protected.
- `ITILTemplateField::showForITILTemplate()` method is no longer abstract.
- `CommonITILTask::getItilObjectItemType` is now static.
- The `Item_Ticket$1` tab should be used in replacement of the `Ticket$1` tab to display tickets associated with an item.
- Specifying the `ranking` of a rule during add/update now triggers `RuleCollection::moveRule` to manage the rankings of other rules to try to keep them valid and in order.
- `Lock::getLocksQueryInfosByItemType()` has been made private.
- `DBmysql::request()`, `DBmysqlIterator::buildQuery()` and `DBmysqlIterator::execute()` methods signatures changed.
-  Some values for the `$type` parameters of several `Stat` methods have changed to match English spelling (technicien -> technician).
- `showInstantiationForm()` method for Network Port classes are now expected to output HTML for a flex form instead of a table.
- `NetworkName::showFormForNetworkPort()` now outputs HTML for a flex form instead of a table.
- `NetworkPortInstantiation::showSocketField()`, `NetworkPortInstantiation::showMacField()`, `NetworkPortInstantiation::showNetworkCardField` now outputs HTML for a flex form instead of a table.
- `CommonGLPI::can*()` and `CommonDBTM::can*()` methods now have strict type hints for their parameters and return types.
- Multiple methods in `CommonDevice` and sub-classes now have return types defined (classes that extends these must match the new method signatures).
- `templates/password_form.html.twig` should no longer be used directly. Use `templates/forgotpassword.html.twig`, `templates/updatepassword.html.twig` or a custom template.
- Usage of `ajax/dropdownMassiveActionAddValidator.php` and `ajax/dropdownValidator.php` now requires a `validation_class` parameter.
- Usage of `ajax/dropdownValidator.php` with the `users_id_validate` parameter is no longer supported. Use `items_id_target` instead.
- `Glpi\Dashboard\Filters\AbstractFilter::field()` method has been made protected.
- Usage of `CommonITILValidation::dropdownValidator()` with the `name` and `users_id_validate` options are no longer supported. Use `prefix` and `itemtype_target`/`items_id_target` respectively instead.
- The `helper` property of form fields will not support anymore the presence of HTML code.
- `GLPI::initErrorHandler()` does not return any value anymore.
- The `inc/autoload.function.php`, `inc/based_config.php`, `inc/config.php`, `inc/db.function.php` and `inc/define.php` files have been removed and the `inc/includes.php` file has been almost emptied.
  The corresponding global functions, constants and variables are now loaded and initialized automatically and the corresponding GLPI boostraping logic is now executed automatically.
- `Plugin::init()` and `Plugin::checkStates()` methods signature changed. It is not anymore possible to exclude specific plugins.
- In a HTTP request context `$_SERVER` variables, like `PATH_INFO`, `PHP_SELF`, `SCRIPT_FILENAME` and `SCRIPT_NAME`, will no longer refer to the requested script, but will refer to the `public/index.php` script.
- Any class added to `$CFG_GLPI['directconnect_types']` must now use the `Glpi\Features\AssignableItem` trait as multi-group support is required.
- For assets, `groups_id` and `groups_id_tech` fields were changed from integers to arrays and are loaded into the `fields` array after `getFromDB`/`getEmpty`.
  If reading directly from the DB, you need to query the new linking table `glpi_groups_items`.
- `Group::getDataItems()` signature changed. The two first parameters `$types` and `$field` were replaced
  by a unique boolean `$tech` parameter that is used to compute the `$types` and `$field` values automatically.
- `CartridgeItem::addCompatibleType()` method is now static.
- `Rule::initRule()` has been made final and non static and its signature changed.
- `Clonable::clone()` and `Clonable::cloneMultiple()` methods now accept a `$clone_as_template` parameter to allow creating templates.
- `enable_partial_warnings` option removed from `SavedSearch::displayMine()`.
- `enable_partial_warnings` option removed from `SavedSearch::execute()`.
- `enable_partial_warnings` option removed from `SavedSearch::getMine()`.
- `Transfer` class is now final.
- `Transfer::addNotToBeTransfer()` method is now private.
- `Transfer::addToAlreadyTransfer()` method is now private.
- `Transfer::addToBeTransfer()` method is now private.
- `Transfer::cleanSoftwareVersions()` method is now private.
- `Transfer::copySingleSoftware()` method is now private.
- `Transfer::copySingleVersion()` method is now private.
- `Transfer::simulateTransfer()` method is now private.
- `Transfer::transferAffectedLicense()` method is now private.
- `Transfer::transferCertificates()` method is now private.
- `Transfer::transferCompatiblePrinters()` method is now private.
- `Transfer::transferContracts()` method is now private.
- `Transfer::transferDevices()` method is now private.
- `Transfer::transferDirectConnection()` method is now private.
- `Transfer::transferDocuments()` method is now private.
- `Transfer::transferDropdownLocation()` method is now private.
- `Transfer::transferDropdownSocket()` method is now private.
- `Transfer::transferHelpdeskAdditionalInformations()` method is now private.
- `Transfer::transferHistory()` method is now private.
- `Transfer::transferInfocoms()` method is now private.
- `Transfer::transferItem()` method is now private.
- `Transfer::transferItem_Disks()` method is now private.
- `Transfer::transferItemSoftwares()` method is now private.
- `Transfer::transferLinkedSuppliers()` method is now private.
- `Transfer::transferNetworkLink()` method is now private.
- `Transfer::transferPrinterCartridges()` method is now private.
- `Transfer::transferReservations()` method is now private.
- `Transfer::transferSingleSupplier()` method is now private.
- `Transfer::transferSoftwareLicensesAndVersions()` method is now private.
- `Transfer::transferSupplierContacts()` method is now private.
- `Transfer::transferTaskCategory()` method is now private.
- `Transfer::transferTickets()` method is now private.
- `linkoption` option has been removed from `CommonDBTM::getLink()`.
- `comments` and `icon` options have been removed from `CommonDBTM::getName()`.
- `comments` and `icon` options have been removed from `CommonDBTM::getNameID()`.
- The `$keepDb` parameter has been removed from `Html::footer()`.
- `DBConnection::createMainConfig()` signature changed. The `$allow_myisam` parameter has been removed.
- `DBConnection::createSlaveConnectionFile()` signature changed. The `$allow_myisam` parameter has been removed.
- `DBmysql::$allow_myisam` property has been removed.
- `Contract::getExpiredCriteria()` renamed to `Contract::getNotExpiredCriteria()` to match the actual behavior.
- `Migration::updateRight()` renamed to `Migration::replaceRight()`.
- `Search::getOptions()` no longer returns a reference.
- The `$target` parameter has been removed from the `AuthLDAP::showLdapGroups()` method.
- The `$target` parameter has been removed from the `Rule::showRulePreviewCriteriasForm()`, `Rule::showRulePreviewResultsForm()`, `RuleCollection::showRulesEnginePreviewCriteriasForm()`, and `RuleCollection::showRulesEnginePreviewResultsForm()` methods signature.
- `Hooks::SHOW_IN_TIMELINE`/`show_in_timeline` plugin hook has been renamed to `Hooks::TIMELINE_ITEMS`/`timeline_items`.
- `Auth::getMethodName()` now only returns the name without a link. Use `Auth::getMethodLink()` to get a HTML-safe link.
- `GLPI_STRICT_DEPRECATED` constant is now know as `GLPI_STRICT_ENV`
- `Software::merge()` method is now private.
- The refusal of the collected emails corresponding to a GLPI notification will now be made based on a default rule.
- The `$store_path` parameter has been removed from the `Dropdown::dropdownIcons()` method.
- The `PLUGINS_DIRECTORIES` constant has been renamed to `GLPI_PLUGINS_DIRECTORIES`.
- Most of the `Profile::show*()` methods have been made private.
- `server` parameter of `User::changeAuthMethod()` now defaults to '0' instead of '-1' which was an invalid value when using unsigned integers.
- `checkitem` parameter of `CommonDBTM::getMassiveActionsForItemtype()` is now the actual item being acted on when in single item mode.
  To identify the difference between the generic item instance given for multi-item mode, use the `isNewItem()` method.
- TinyMCE library is now loaded automatically on every page.

#### Deprecated
- Usage of the `/marketplace` path for plugins URLs. All plugins URLs should now start with `/plugins`.
- Usage of `GLPI_PLUGINS_PATH` javascript variable.
- Usage of the `GLPI_FORCE_MAIL` constant.
- Usage of `MAIL_SMTPSSL` constants.
- Usage of `name` and `users_id_validate` parameter in `ajax/dropdownValidator.php`.
- Usage of `users_id_validate` parameter in `front/commonitilvalidation.form.php`.
- `front/ticket_ticket.form.php` script usage.
- Usage of `users_id_validate` input in `CommonITILObject`.
- Defining "users_id_validate" field without defining "itemtype_target"/"items_id_target" in "CommonITILValidation".
- Usage of `name` and `users_id_validate` options in `CommonITILValidation::dropdownValidator()`.
- Usage of `get_plugin_web_dir` Twig function.
- Usage of `verbatim_value` Twig filter.
- `js/Forms/FaIconSelector.js` and therefore `window.GLPI.Forms.FaIconSelector` has been deprecated and replaced by `js/modules/Form/WebIconSelector.js`
- `linkuser_types`, `linkgroup_types`, `linkuser_tech_types`, `linkgroup_tech_types` configuration entries have been merged in a unique `assignable_types` configuration entry.
- Usage of the `front/dropdown.common.php` and the `dropdown.common.form.php` files. There is now a generic controller that will serve the search and form pages of any `Dropdown` class.
- Usage of the `$link` parameter in `formatUserName()` and `DbUtils::formatUserName()`. Use `formatUserLink()` or `DbUtils::formatUserLink()` instead.
- Usage of the `$link` parameter in `getUserName()` and `DbUtils::getUserName()`. Use `getUserLink()`, `DbUtils::getUserLink()`, or `User::getInfoCard()` instead.
- Usage of the `$withcomment` parameter in `getTreeValueCompleteName()`, `DbUtils::getTreeValueCompleteName()` and `Dropdown::getDropdownName()`. Use `Dropdown::getDropdownComments()` instead.
- `Auth::getErr()`
- `ComputerAntivirus` has been deprecated and replaced by `ItemAntivirus`
- `ComputerVirtualMachine` has been deprecated and replaced by `ItemVirtualMachine`
- `DBmysql::deleteOrDie()`. Use `DBmysql::delete()` instead.
- `DBmysql::doQueryOrDie()`. Use `DBmysql::doQuery()` instead.
- `DBmysql::insertOrDie()`. Use `DBmysql::insert()` instead.
- `DBmysql::truncate()`
- `DBmysql::truncateOrDie()`
- `DBmysql::updateOrDie()`. Use `DBmysql::update()` instead.
- `Document::send()`
- `Glpi\Application\View\Extension\DataHelpersExtension::getVerbatimValue()`
- `Glpi\Application\View\Extension\PluginExtension::getPluginWebDir()`
- `Glpi\Dashboard\Filter::getAll()`
- `Glpi\Http\Response::send()`
- `Glpi\Http\Response::sendContent()`
- `Glpi\Http\Response::sendError()`. Throw a `Glpi\Exception\Http\*HttpException` exception instead.
- `Glpi\Http\Response::sendHeaders()`
- `Glpi\Toolbox\Sanitizer::dbEscape()`
- `Glpi\Toolbox\Sanitizer::dbEscapeRecursive()`
- `Glpi\Toolbox\Sanitizer::dbUnescape()`
- `Glpi\Toolbox\Sanitizer::dbUnescapeRecursive()`
- `Glpi\Toolbox\Sanitizer::decodeHtmlSpecialChars()`
- `Glpi\Toolbox\Sanitizer::decodeHtmlSpecialCharsRecursive()`
- `Glpi\Toolbox\Sanitizer::encodeHtmlSpecialChars()`
- `Glpi\Toolbox\Sanitizer::encodeHtmlSpecialCharsRecursive()`
- `Glpi\Toolbox\Sanitizer::getVerbatimValue()`
- `Glpi\Toolbox\Sanitizer::isDbEscaped()`
- `Glpi\Toolbox\Sanitizer::isHtmlEncoded()`
- `Glpi\Toolbox\Sanitizer::isNsClassOrCallableIdentifier()`
- `Glpi\Toolbox\Sanitizer::sanitize()`
- `Glpi\Toolbox\Sanitizer::unsanitize()`
- `Html::ajaxFooter()`
- `Html::changeProgressBarMessage()`
- `Html::changeProgressBarPosition()`
- `Html::cleanInputText()`
- `Html::cleanPostForTextArea()`
- `Html::createProgressBar()`
- `Html::displayErrorAndDie()`. Throw a `Glpi\Exception\Http\BadRequestHttpException` exception instead.
- `Html::displayNotFoundError()`. Throw a `Glpi\Exception\Http\NotFoundHttpException` exception instead.
- `Html::displayProgressBar()`
- `Html::displayRightError()`. Throw a `Glpi\Exception\Http\AccessDeniedHttpException` exception instead.
- `Html::entities_deep()`
- `Html::entity_decode_deep()`
- `Html::glpi_flush()`
- `Html::jsGetElementbyID()`
- `Html::jsGetDropdownValue()`
- `Html::jsSetDropdownValue()`
- `Html::progressBar()`
- `HookManager::enableCSRF()`
- `ITILFollowup::ADDMYTICKET` constant. Use `ITILFollowup::ADDMY`.
- `ITILFollowup::ADDGROUPTICKET` constant. Use `ITILFollowup::ADD_AS_GROUP`.
- `ITILFollowup::ADDALLTICKET` constant. Use `ITILFollowup::ADDALLITEM`.
- `Migration::addNewMessageArea()`
- `Migration::displayError()`
- `Migration::displayTitle()`
- `Migration::displayWarning()`
- `Migration::setOutputHandler()`
- `Pdu_Plug` has been deprecated and replaced by `Item_Plug`
- `Plugin::getWebDir()`
- `Search::joinDropdownTranslations()`
- `Ticket` `link_to_problem` massive action is deprecated. Use `CommonITILObject_CommonITILObject` `add` massive action instead.
- `Ticket_Ticket` `add` massive action is deprecated. Use `CommonITILObject_CommonITILObject` `add` massive action instead.
- `Ticket_Ticket::getLinkedTicketsTo()`
- `Timer` class
- `Toolbox::addslashes_deep()`
- `Toolbox::seems_utf8()`
- `Toolbox::sendFile()`
- `Toolbox::stripslashes_deep()`

#### Removed
- `GLPI_USE_CSRF_CHECK`, `GLPI_USE_IDOR_CHECK`, `GLPI_KEEP_CSRF_TOKEN`, `GLPI_CSRF_EXPIRES`, `GLPI_CSRF_MAX_TOKENS` and `GLPI_IDOR_EXPIRES` constants.
- `GLPI_DEMO_MODE` constant.
- `GLPI_DUMP_DIR` constant.
- `GLPI_SQL_DEBUG` constant.
- `$AJAX_INCLUDE` global variable.
- `$CFG_GLPI_PLUGINS` global variable.
- `$DBCONNECTION_REQUIRED` and `$USEDBREPLICATE` global variables. Use `DBConnection::getReadConnection()` to get the most apporpriate connection for read only operations.
- `$dont_check_maintenance_mode` and `$skip_db_check` global variables.
- `$GLPI` global variable.
- `$LANG` global variable.
- `$PLUGINS_EXCLUDED` and `$PLUGINS_INCLUDED` global variables.
- `$SECURITY_STRATEGY` global variable.
- `$SQLLOGGER` global variable
- Usage of `$CFG_GLPI['itemdevices']` and `$CFG_GLPI['item_device_types']` configuration entries. Use `Item_Devices::getDeviceTypes()` to get the `Item_Devices` concrete class list.
- Usage of `csrf_compliant` plugins hook.
- Usage of `migratetypes` plugin hooks.
- Usage of `planning_scheduler_key` plugins hook.
- Logging within the `mail-debug.log` log file.
- `X-GLPI-Sanitized-Content` REST API header support.
- Handling of encoded/escaped value in `autoName()`.
- `closeDBConnections`
- `regenerateTreeCompleteName()`
- `Ajax::updateItemOnInputTextEvent()`
- `Appliance::getMassiveActionsForItemtype()`
- `AuthLDAP::ldapChooseDirectory()`
- `AuthLDAP::displayLdapFilter()`
- `AuthLDAP::dropdownUserDeletedActions()`
- `AuthLDAP::dropdownUserRestoredActions()`
- `AuthLDAP::getDefault()`
- `AuthLDAP::getLdapDeletedUserActionOptions()`
- `AuthLDAP::manageValuesInSession()`
- `AuthLDAP::showDateRestrictionForm()`
- `Cartridge::getNotificationParameters()`
- `CartridgeItem::showDebug()`
- `Certificate::showDebug()`
- `Change::showDebug()`
- `Change_Item::showForChange()`
- `CommonDBTM::$deduplicate_queued_notifications` property.
- `CommonDBTM::cleanLockedsOnAdd()`
- `CommonDBTM::getCacheKeyForFriendlyName()`
- `CommonDBTM::getSNMPCredential()`
- `CommonDBTM::hasSavedInput()`
- `CommonDBTM::showDebugInfo()`
- `CommonDevice::title()`
- `CommonDropdown::$first_level_menu`, `CommonDropdown::$second_level_menu` and `CommonDropdown::$third_level_menu` properties.
- `CommonDropdown::displayHeader()`
- `CommonGLPI::$type` property.
- `CommonGLPI::getAvailableDisplayOptions()`
- `CommonGLPI::getDisplayOptions()`
- `CommonGLPI::getDisplayOptionsLink()`
- `CommonGLPI::updateDisplayOptions()`
- `CommonGLPI::showDislayOptions()`
- `CommonITILActor::showUserNotificationForm()`
- `CommonITILActor::showSupplierNotificationForm()`
- `CommonITILObject::$userentity_oncreate` property.
- `CommonITILObject::getAssignName()`
- `CommonITILObject::getContentTemplatesParametersClass()`
- `CommonITILObject::isValidator()`
- `CommonITILObject::showActorAddFormOnCreate()`
- `CommonITILValidation::alreadyExists()`
- `CommonITILValidation::getTicketStatusNumber()`
- `CommonITILValidation::getValidationStats()`
- `CommonTreeDropdown::sanitizeSeparatorInCompletename()`
- `CommonTreeDropdown::unsanitizeSeparatorInCompletename()`
- `Computer_Item::countForAll()`
- `Computer_Item::disconnectForItem()`
- `Computer_Item::dropdownAllConnect()`
- `Computer_Item::showForComputer()`
- `Computer_Item::showForItem()`
- `ComputerAntivirus::showForComputer()`
- `ComputerVirtualMachine::showForComputer()`
- `Config::detectRootDoc()`
- `Config::getCurrentDBVersion()`
- `Config::getLibraries()`
- `Config::getLibraryDir()`
- `Config::showDebug()`
- `Config::showLibrariesInformation()`
- `Config::validatePassword()`
- `Consumable::showAddForm()`
- `Consumable::showForConsumableItem()`
- `ConsumableItem::showDebug()`
- `Contract::commonListHeader()`
- `Contract::getContractRenewalIDByName()`
- `Contract::showDebug()`
- `Contract::showShort()`
- `DbUtils::closeDBConnections()`
- `DbUtils::regenerateTreeCompleteName()`
- `DBConnection::displayMySQLError()`
- `DBmysql::error` property.
- `DBmysql::getLastQueryWarnings()`
- `Document::getImage()`
- `Document::showUploadedFilesDropdown()`
- `Document::uploadDocument()`
- `Document_Item::showSimpleAddForItem()`
- `Dropdown::showAdvanceDateRestrictionSwitch()`
- `DropdownTranslation::canBeTranslated()`. Translations are now always active.
- `DropdownTranslation::getTranslationByName()`
- `DropdownTranslation::isDropdownTranslationActive()`. Translations are now always active.
- `Entity::getDefaultContractValues()`
- `Entity::cleanEntitySelectorCache()`
- `Entity::title()`
- `FieldUnicity::checkBeforeInsert()`
- `FieldUnicity::showDebug()`
- `GLPI::getErrorHandler()`
- `GLPI::getLogLevel()`
- `GLPI::initErrorHandler()`
- `GLPI::initLogger()`
- `Glpi\Api\API::showDebug()`
- `Glpi\Api\API::returnSanitizedContent()`
- `Glpi\Application\ErrorHandler` class
- `Glpi\Cache\CacheManager::getInstallerCacheInstance()`
- `Glpi\Console\Command\ForceNoPluginsOptionCommandInterface` class
- `Glpi\Dashboard\Filter::dates()`
- `Glpi\Dashboard\Filter::dates_mod()`
- `Glpi\Dashboard\Filter::itilcategory()`
- `Glpi\Dashboard\Filter::requesttype()`
- `Glpi\Dashboard\Filter::location()`
- `Glpi\Dashboard\Filter::manufacturer()`
- `Glpi\Dashboard\Filter::group_tech()`
- `Glpi\Dashboard\Filter::user_tech()`
- `Glpi\Dashboard\Filter::state()`
- `Glpi\Dashboard\Filter::tickettype()`
- `Glpi\Dashboard\Filter::displayList()`
- `Glpi\Dashboard\Filter::field()`
- `Glpi\Dashboard\Widget::getCssGradientPalette()`
- `Glpi\Debug\Toolbar` class
- `Glpi\Event::showList()`
- `Glpi\Features\DCBreadcrumb::getDcBreadcrumb()`
- `Glpi\Features\DCBreadcrumb::getDcBreadcrumbSpecificValueToDisplay()`
- `Glpi\Features\DCBreadcrumb::isEnclosurePart()`
- `Glpi\Features\DCBreadcrumb::isRackPart()`
- `Glpi\Inventory\Conf::importFile()`
- `Glpi\Socket::executeAddMulti()`
- `Glpi\Socket::showNetworkPortForm()`
- `Glpi\System\Requirement\DataDirectoriesProtectedPath` class.
- `Glpi\System\Requirement\ProtectedWebAccess` class.
- `Glpi\System\Requirement\MysqliMysqlnd` class.
- `Glpi\System\Requirement\SafeDocumentRoot` class.
- `Glpi\System\Status\StatusChecker::getFullStatus()`
- `Group::title()`
- `Group_User` `is_userdelegate` field.
- `Html::autocompletionTextField()`
- `Html::clean()`
- `Html::closeArrowMassives()`
- `Html::displayAccessDeniedPage()`
- `Html::displayAjaxMessageAfterRedirect()`. The JS function is already provided by `js/misc.js`.
- `Html::displayItemNotFoundPage()`
- `Html::getCoreVariablesForJavascript()`
- `Html::jsConfirmCallback()`
- `Html::jsHide()`
- `Html::jsShow()`
- `Html::openArrowMassives()`
- `Html::showTimeField()`
- `Impact::buildNetwork()`
- `Infocom::addPluginInfos()`
- `Infocom::showDebug()`
- `IPNetwork::recreateTree()`
- `IPNetwork::title()`
- `Item_Problem::showForProblem()`
- `Item_Ticket::showForTicket()`
- `ITILTemplate::getSimplifiedInterfaceFields()`
- `Knowbase::getTreeCategoryList()`
- `Knowbase::showBrowseView()`
- `Knowbase::showManageView()`
- `KnowbaseItem::addToFaq()`
- `KnowbaseItem::addVisibilityJoins()`
- `KnowbaseItem::addVisibilityRestrict()`
- `KnowbaseItem::showBrowseForm()`
- `KnowbaseItem::showManageForm()`
- `KnowbaseItem_Comment::displayComments()`
- `KnowbaseItem_KnowbaseItemCategory::displayTabContentForItem()`
- `KnowbaseItem_KnowbaseItemCategory::getTabNameForItem()`
- `KnowbaseItem_KnowbaseItemCategory::showForItem()`
- `KnowbaseItemTranslation::canBeTranslated()`. Translations are now always active.
- `KnowbaseItemTranslation::isKbTranslationActive()`. Translations are now always active.
- `Link::showForItem()`
- `Link_Itemtype::showForLink()`
- `MailCollector::isMessageSentByGlpi()`
- `MailCollector::listEncodings()`
- `MailCollector::title()`
- `MassiveAction::updateProgressBars()`
- `ManualLink::showForItem()`
- `MigrationCleaner` class
- `Netpoint` class
- `NetworkAlias::getInternetNameFromID()`
- `NetworkName::getInternetNameFromID()`
- `NetworkPort::getAvailableDisplayOptions()`
- `NetworkPort::getNetworkPortInstantiationsWithNames()`
- `NetworkPort::getUnmanagedLink()`
- `NetworkPort::resetConnections()`
- `NetworkPortInstantiation::getGlobalInstantiationNetworkPortDisplayOptions()`
- `NetworkPortInstantiation::getInstantiationHTMLTable()` and all sub classes overrides.
- `NetworkPortInstantiation::getInstantiationHTMLTableHeaders()` and all sub classes overrides.
- `NetworkPortInstantiation::getInstantiationHTMLTableWithPeer()`
- `NetworkPortInstantiation::getInstantiationNetworkPortDisplayOptions()`
- `NetworkPortInstantiation::getInstantiationNetworkPortHTMLTable()`
- `NetworkPortInstantiation::getPeerInstantiationHTMLTable()` and all sub classes overrides.
- `NetworkPortMigration` class
- `NotificationEvent::debugEvent()`
- `NotificationTemplateTranslation::showDebug()`
- `OlaLevel::showForSLA()`. Replaced by `LevelAgreementLevel::showForLA()`.
- `PlanningExternalEvent::addVisibilityRestrict()`
- `PlanningRecall::specificForm()`
- `Plugin::haveImport()`
- `Plugin::migrateItemType()`
- `Plugin::unactivateAll()`
- `ProfileRight::updateProfileRightAsOtherRight()`
- `ProfileRight::updateProfileRightsAsOtherRights()`
- `Project::commonListHeader()`
- `Project::showDebug()`
- `Project::showShort()`
- `ProjectTask::showDebug()`
- `QuerySubQuery` class. Replaced by `Glpi\DBAL\QuerySubQuery`.
- `QueryUnion` class. Replaced by `Glpi\DBAL\QueryUnion`.
- `QueuedNotification::forceSendFor()`
- `Reminder::addVisibilityJoins()`
- `ReminderTranslation::canBeTranslated()`. Translations are now always active.
- `ReminderTranslation::isReminderTranslationActive()`. Translations are now always active.
- `Reservation::displayError()`
- `ReservationItem::showDebugResa()`
- `RSSFeed::addVisibilityJoins()`
- `RSSFeed::addVisibilityRestrict()`
- `RSSFeed::showDiscoveredFeeds()`
- `Rule::$can_sort` property.
- `Rule::$orderby` property.
- `Rule::getCollectionClassName()`
- `Rule::showDebug()`
- `Rule::showMinimalActionForm()`
- `Rule::showMinimalCriteriaForm()`
- `Rule::showMinimalForm()`
- `Rule::showNewRuleForm()`
- `RuleCollection::showTestResults()`
- `RuleRight::showNewRuleForm()`
- `RuleRightCollection::displayActionByName()`
- `RuleRightCollection::showTestResults()`
- `RuleImportComputer` class.
- `RuleImportComputerCollection` class.
- `RuleMatchedLog::showFormAgent()`.
- `RuleMatchedLog::showItemForm()`.
- `SavedSearch::prepareQueryToUse()`
- `Search::SYLK_OUTPUT` constant.
- `Search::computeTitle()`
- `Search::csv_clean()`
- `Search::findCriteriaInSession()`
- `Search::getDefaultCriteria()`
- `Search::getLogicalOperators()`
- `Search::getMetaReferenceItemtype()`
- `Search::outputData()`
- `Search::sylk_clean()`
- `Session::buildSessionName()`
- `Session::redirectIfNotLoggedIn()`
- `Session::redirectToLogin()`
- `SlaLevel::showForSLA()`. Replaced by `LevelAgreementLevel::showForLA()`.
- `SLM::setTicketCalendar()`
- `SoftwareLicense::getSonsOf()`
- `SoftwareLicense::showDebug()`
- `Transfer::$inittype` property.
- `Ticket::canDelegateeCreateTicket()`
- `Ticket::showDebug()`
- `Ticket::showFormHelpdesk()`
- `Ticket::showFormHelpdeskObserver()`
- `Ticket_Ticket::checkParentSon()`
- `Ticket_Ticket::countOpenChildren()`
- `Ticket_Ticket::manageLinkedTicketsOnSolved()`. Replaced by `CommonITILObject_CommonITILObject::manageLinksOnChange()`.
- `TicketTemplate::showHelpdeskPreview()`
- `Toolbox::canUseCas()`
- `Toolbox::checkValidReferer()`
- `Toolbox::clean_cross_side_scripting_deep()`
- `Toolbox::endsWith()`
- `Toolbox::filesizeDirectory()`
- `Toolbox::getHtmLawedSafeConfig()`
- `Toolbox::getHtmlToDisplay()`
- `Toolbox::handleProfileChangeRedirect()`
- `Toolbox::logError()`
- `Toolbox::logNotice()`
- `Toolbox::logSqlDebug()`
- `Toolbox::logSqlError()`
- `Toolbox::logSqlWarning()`
- `Toolbox::logWarning()`
- `Toolbox::showMailServerConfig()`
- `Toolbox::sodiumDecrypt()`
- `Toolbox::sodiumEncrypt()`
- `Toolbox::unclean_cross_side_scripting_deep()`
- `Transfer::manageConnectionComputer()`
- `Update::initSession()`
- `User::getDelegateGroupsForUser()`
- `User::showDebug()`
- `User::title()`
- `XML` class.
- Usage of `Search::addOrderBy` signature with ($itemtype, $ID, $order) parameters
- Javascript file upload functions `dataURItoBlob`, `extractSrcFromImgTag`, `insertImgFromFile()`, `insertImageInTinyMCE`, `isImageBlobFromPaste`, `isImageFromPaste`.
- `CommonDBTM::$fkfield` property.
- `getHTML` action for `ajax/fuzzysearch.php` endpoint.
- `DisplayPreference::showFormGlobal` `target` parameter.
- `DisplayPreference::showFormPerso` `target_id` parameter.
- `$_SESSION['glpiroot']` session variable.
- `$DEBUG_SQL, `$SQL_TOTAL_REQUEST`, `$TIMER_DEBUG` and `$TIMER` global variables.
- `$CFG_GLPI['debug_sql']` and `$CFG_GLPI['debug_vars']` configuration options.
- `addgroup` and `deletegroup` actions in `front/user.form.php`.
- `ajax/ldapdaterestriction.php` script.
- `ajax/ticketassigninformation.php` script. Use `ajax/actorinformation.php` instead.
- `ajax/planningcheck.php` script. Use `Planning::showPlanningCheck()` instead.
- `test_ldap` and `test_ldap_replicate` actions in `front/authldap.form.php`. Use `ajax/ldap.php` instead.
- `ajax/ticketsatisfaction.php` and `ajax/changesatisfaction.php` scripts. Access `ajax/commonitilsatisfaction.php` directly instead.
- Usage of the `$cut` parameter in `formatUserName()` and `DbUtils::formatUserName()`.
- Handling of the `delegate` right in `User::getSqlSearchResult()`.
- Usage of the `$link` and `$name` parameters in `Auth::getMethodName()`.


## [10.0.21] unreleased

### Added

### Changed
- It is again possible to "Merge as Followup" into resolved/closed tickets.

### Deprecated

### Removed

### API changes

#### Added

#### Changes

#### Deprecated

#### Removed


## [10.0.20] 2025-09-10

### Added

### Changed
- Assign contract Ticket rule action now works with update rules.

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
- `Dashboard` global javascript object has been moved to `GLPI.Dashboard`.

#### Deprecated
- Usage of `MyISAM` engine in database, in favor of `InnoDB` engine.
- Usage of `utf8mb3` charset/collation in database in favor of `utf8mb4` charset/collation.
- Usage of `datetime` field type in database, in favor of `timestamp` field type.
- Handling of encoded/escaped value in `autoName()`
- `Netpoint` has been deprecated and replaced by `Socket`
- `CommonDropdown::displayHeader()`, use `CommonDropdown::displayCentralHeader()` instead and make sure to override properly `first_level_menu`, `second_level_menu` and `third_level_menu`.
- `GLPI::getLogLevel()`
- `Glpi\System\Status\StatusChecker::getFullStatus()`
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
