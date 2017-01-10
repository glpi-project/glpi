<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief Check obsoleted function
*/

if (isset($_SERVER['argv'][1])) {
   $rep = $_SERVER['argv'][1];
} else {
   die("Missing option (directory to analyse)\n");
}

$obsoleted = array(
   // Functions
   'addConfirmationOnAction'           =>  'Html::addConfirmationOnAction',
   'addMessageAfterRedirect'           =>  'Session::addMessageAfterRedirect',
   'addslashes_deep'                   =>  'Toolbox::addslashes_deep',
   'addToNavigateListItems'            =>  'Session::addToNavigateListItems',
   'ajaxDisplaySearchTextForDropdown'  =>  'Ajax::displaySearchTextForDropdown',
   'ajaxFooter'                        =>  'Html::ajaxFooter',
   'ajaxUpdateItem'                    =>  'Ajax::updateItem' ,
   'ajaxUpdateItemJsCode'              =>  'Ajax::updateItemJsCode',
   'ajaxUpdateItemOnEvent'             =>  'Ajax::updateItemOnEvent',
   'ajaxUpdateItemOnEventJsCode'       =>  'Ajax::updateItemOnEventJsCode',
   'ajaxUpdateItemOnInputTextEvent'    =>  'Ajax::updateItemOnInputTextEvent',
   'ajaxUpdateItemOnSelectEvent'       =>  'Ajax::updateItemOnSelectEvent',
   'append_params'                     =>  'Toolbox::append_params',
   'autocompletionTextField'           =>  'Html::autocompletionTextField',
   'callCron'                          =>  'CronTask::callCron',
   'callCronForce'                     =>  'CronTask::callCronForce',
   'canUseImapPop'                     =>  'Toolbox::canUseImapPop',
   'canUseLdap'                        =>  'Toolbox::canUseLdap',
   'changeActiveEntities'              =>  'Session::changeActiveEntities',
   'changeProgressBarMessage'          =>  'Html::changeProgressBarMessage',
   'changeProgressBarPosition'         =>  'Html::changeProgressBarPosition',
   'changeProfile'                     =>  'Session::changeProfile',
   'checkCentralAccess'                =>  'Session::checkCentralAccess',
   'checkFaqAccess'                    =>  'Session::checkFaqAccess',
   'checkHelpdeskAccess'               =>  'Session::checkHelpdeskAccess',
   'checkLoginUser'                    =>  'Session::checkLoginUser',
   'checkNewVersionAvailable'          =>  'Toolbox::checkNewVersionAvailable',
   'checkSeveralRightsOr'              =>  'Session::checkSeveralRightsOr',
   'checkRight'                        =>  'Session::checkRight',
   'checkWriteAccessToDirs'            =>  'Config::checkWriteAccessToDirs',
   'html_clean'                        =>  'Html::clean',
   'clean_cross_side_scripting_deep'   =>  'Toolbox::clean_cross_side_scripting_deep',
   'cleanInputText'                    =>  'Html::cleanInputText',
   'cleanParametersURL'                =>  'Html::cleanParametersURL',
   'cleanPostForTextArea'              =>  'Html::cleanPostForTextArea',
   'closeArrowMassive'                 =>  'Html::closeArrowMassives',
   'commonCheckForUseGLPI'             =>  'Toolbox::commonCheckForUseGLPI',
   'commonDropdownUpdateItem'          =>  'Ajax::commonDropdownUpdateItem',
   'commonFooter'                      =>  'Html::footer',
   'commonHeader'                      =>  'Html::header',
   'constructMailServerConfig'         =>  'Toolbox::constructMailServerConfig',
   'convDate'                          =>  'Html::convDate',
   'convDateTime'                      =>  'Html::convDateTime',
   'createAjaxTabs'                    =>  'Ajax::createTabs',
   'createProgressBar'                 =>  'Html::createProgressBar',
   'decodeFromUtf8'                    =>  'Toolbox::decodeFromUtf8',
   'decrypt'                           =>  'Toolbox::decrypt',
   'deleteDir'                         =>  'Toolbox::deleteDir',
   'destroySession'                    =>  'Session::destroy',
   'displayBackLink'                   =>  'Html::displayBackLink',
   'displayDebugInfos'                 =>  'Html::displayDebugInfos',
   'displayErrorAndDie'                =>  'Html::displayErrorAndDie',
   'displayMessageAfterRedirect'       =>  'Html::displayMessageAfterRedirect',
   'displayNotFoundError'              =>  'Html::displayNotFoundError',
   'displayProgressBar'                =>  'Html::displayProgressBar',
   'displayRightError'                 =>  'Html::displayRightError',
   'displayTitle'                      =>  'Html::displayTitle',
   'doHook'                            =>  'Plugin::doHook',
   'doHookFunction'                    =>  'Plugin::doHookFunction',
   'doOneHook'                         =>  'Plugin::doOneHook',
   'encodeInUtf8'                      =>  'Toolbox::encodeInUtf8',
   'encrypt'                           =>  'Toolbox::encrypt',
   'filesizeDirectory'                 =>  'Toolbox::filesizeDirectory',
   'formatNumber'                      =>  'Html::formatNumber',
   'getActiveTab'                      =>  'Session::getActiveTab',
   'getAllReplicateForAMaster'         =>  'AuthLDAP::getAllReplicateForAMaster',
   'getCountLogin'                     =>  'Event::getCountLogin',
   'getItemTypeFormURL'                =>  'Toolbox::getItemTypeFormURL',
   'getItemTypeSearchURL'              =>  'Toolbox::getItemTypeSearchURL',
   'getItemTypeTabsURL'                =>  'Toolbox::getItemTypeTabsURL',
   'getLoginUserID'                    =>  'Session::getLoginUserID',
   'get_magic_quotes_gpc'              =>  'Toolbox::get_magic_quotes_gpc',
   'get_magic_quotes_runtime'          =>  'Toolbox::get_magic_quotes_runtime',
   'getMemoryLimit'                    =>  'Toolbox::getMemoryLimit',
   'getPluginSearchOptions'            =>  'Plugin::getPluginSearchOptions',
   'getPluginsDatabaseRelations'       =>  'Plugin::getPluginsDatabaseRelations',
   'getPluginsDropdowns'               =>  'Plugin::getPluginsDropdowns',
   'getRandomString'                   =>  'Toolbox::getRandomString',
   'getSize'                           =>  'Toolbox::getSize',
   'getTimestampTimeUnits'             =>  'Toolbox::getTimestampTimeUnits',
   'getURLContent'                     =>  'Toolbox::getURLContent',
   'getWarrantyExpir'                  =>  'Infocom::getWarrantyExpir',
   'glpi_flush'                        =>  'Html::glpi_flush',
   'glpi_header'                       =>  'Html::redirect',
   'haveAccessToAllOfEntities'         =>  'Session::haveAccessToAllOfEntities',
   'haveAccessToEntity'                =>  'Session::haveAccessToEntity',
   'haveAccessToOneOfEntities'         =>  'Session::haveAccessToOneOfEntities',
   'haveRecursiveAccessToEntity'       =>  'Session::haveRecursiveAccessToEntity',
   'haveRight'                         =>  'Session::haveRight',
   'header_nocache'                    =>  'Html::header_nocache',
   'helpFooter'                        =>  'Html::helpFooter',
   'helpHeader'                        =>  'Html::helpHeader',
   'html_entity_decode_deep'           =>  'Html::entity_decode_deep',
   'htmlentities_deep'                 =>  'Html::entities_deep',
   'includeCommonHtmlHeader'           =>  'Html::includeHeader',
   'initEditorSystem'                  =>  'Html::initEditorSystem',
   'initEntityProfiles'                =>  'Session::initEntityProfiles',
   'initNavigateListItems'             =>  'Session::initNavigateListItems',
   'initSession'                       =>  'Session::init',
   'isMultiEntitiesMode'               =>  'Session::isMultiEntitiesMode',
   'key_exists_deep'                   =>  'Toolbox::key_exists_deep',
   'ldap_get_entries_clean'            =>  'AuthLDAP::get_entries_clean',
   'listTemplates'                     =>  'CommonDBTM::listTemplates',
   'loadGroups'                        =>  'Session::loadGroups',
   'loadLanguage'                      =>  'Session::loadLanguage',
   'logDebug'                          =>  'Toolbox::logDebug',
   'logInFile'                         =>  'Toolbox::logInFile',
   'makeTextCriteria'                  =>  'Search::makeTextCriteria',
   'makeTextSearch'                    =>  'Search::makeTextSearch',
   'manageBeginAndEndPlanDates'        =>  'Toolbox::manageBeginAndEndPlanDates',
   'manageRedirect'                    =>  'Toolbox::manageRedirect',
   'nl2br_deep'                        =>  'Html::nl2br_deep',
   'nullFooter'                        =>  'Html::nullFooter',
   'nullHeader'                        =>  'Html::nullHeader',
   'openArrowMassive'                  =>  'Html::openArrowMassives',
   'optimize_tables'                   =>  'DBmysql::optimize_tables',
   'popFooter'                         =>  'Html::popFooter',
   'popHeader'                         =>  'Html::popHeader',
   'printAjaxPager'                    =>  'Html::printAjaxPager',
   'printCleanArray'                   =>  'Html::printCleanArray',
   'printPager'                        =>  'Html::printPager',
   'printPagerForm'                    =>  'Html::printPagerForm',
   'resume_text'                       =>  'Html::resume_text',
   'resume_name'                       =>  'Html::resume_name',
   'return_bytes_from_ini_vars'        =>  'Toolbox::return_bytes_from_ini_vars',
   'seems_utf8'                        =>  'Toolbox::seems_utf8',
   'sendFile'                          =>  'Toolbox::sendFile',
   'setActiveTab'                      =>  'Session::setActiveTab',
   'setGlpiSessionPath'                =>  'Session::setPath',
   'showDateFormItem'                  =>  'Html::showDateField',
   'showDateTimeFormItem'              =>  'Html::showDateTimeField',
   'showGenericDateTimeSearch'         =>  'Html::showGenericDateTimeSearch',
   'showMailServerConfig'              =>  'Toolbox::showMailServerConfig',
   'showOtherAuthList'                 =>  'Auth::showOtherAuthList',
   'showProfileSelecter'               =>  'Html::showProfileSelecter',
   'showToolTip'                       =>  'Html::showToolTip',
   'simpleHeader'                      =>  'Html::simpleHeader',
   'startGlpiSession'                  =>  'Session::start',
   'stripslashes_deep'                 =>  'Toolbox::stripslashes_deep',
   'testWriteAccessToDirectory'        =>  'Toolbox::testWriteAccessToDirectory',
   'timestampToString'                 =>  'Html::timestampToString',
   'utf8_str_pad'                      =>  'Toolbox::str_pad',
   'utf8_strlen'                       =>  'Toolbox::strlen',
   'utf8_strpos'                       =>  'Toolbox::strpos',
   'utf8_strtolower'                   =>  'Toolbox::strtolower',
   'utf8_strtoupper'                   =>  'Toolbox::strtoupper',
   'utf8_substr'                       =>  'Toolbox::substr',
   'unclean_cross_side_scripting_deep' =>  'Toolbox::unclean_cross_side_scripting_deep',
   'userErrorHandlerDebug'             =>  'Toolbox::userErrorHandlerDebug',
   'userErrorHandlerNormal'            =>  'Toolbox::userErrorHandlerNormal',
   'weblink_extract'                   =>  'Html::weblink_extract',

   // Constants
   'DROPDOWN_EMPTY_VALUE'              =>  'Dropdown::EMPTY_VALUE',
   'DEBUG_MODE'                        =>  'Session::DEBUG_MODE',
   'NORMAL_MODE'                       =>  'Session::NORMAL_MODE',
   'TRANSLATION_MODE'                  =>  'Session::TRANSLATION_MODE',
   'HISTORY_'                          =>  'Log::HISTORY_',
   'BOOKMARK_SEARCH'                   =>  'Bookmark::SEARCH'
);

$res = 0;
foreach ($obsoleted as $old => $new) {
   if (in_array('--debug', $_SERVER['argv'])) {
      echo "+ $old => $new\n";
   }
   passthru("grep -r '$old' '$rep' | grep -v '$new'", $res);
   if (!$res) {
      echo "**********\nCall of '$old' must be replaced by '$new'\n**********\n";
      if (in_array('--stop', $_SERVER['argv'])) {
         exit(1);
      }
   }
}
?>
