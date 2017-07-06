# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [9.2] Unreleased

### Added
- Link knowledge base entries with assets or tickets
- Revisions on knowledge base entries and their translations, with diff view
- Add recursive comments on knowledge base entries
- Load minified CSS and JS files (core and plugins) that are generated on release
- Link beetween software licenses
- Alerts on saved searches
- Add ajax browsers notifications in addition to emails
- Plugins can now add new notifications types (xmpp, sms, telegram, ...) to be used along with standard notifications
- Simcard component

### Changed
- Display knowledge base category items in tickets using a popup instead of a
new whole window
- Reviewed all richtext editor (tinymce) and their upload parts, now more simpler and intuitive
- Don't ask user to select a template if there is no configured template
- personal_token is not used anymore for api authentication, a new api_token field has been added (empty by default, you should regenerate it).

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

#### Deprecated

The following methods have been deprecated:

- `_e()`
- `_ex()`
- `Bookmark::mark_default()`
- `Bookmark::unmark_default()`
- `User::getUniquePersonalToken()`
- `User::getPersonalToken()`
- `NotificationTarget*::get*Address()`
- many `NotificationTarget*::get*()`
- `QueuedMail::sendMailById()`

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
- `DBMysql::field_flags()`
- `Html::showDateFormItem()`
- `Html::showDateTimeFormItem()`
- `Profile::dropdownNoneReadWrite()`
- `Toolbox::get_magic_quotes_runtime`
- `Toolbox::get_magic_quotes_gpc`


For older entries, please check [GLPI website](http://glpi-project.org).
