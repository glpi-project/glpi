<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

Html::header(MailCollector::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config","mailcollector");

if (!Toolbox::canUseImapPop()) {
   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>" . _n('Receiver', 'Receivers', 2)."</th></tr>";
   echo "<tr class='tab_bg_2'>";
   echo "<td class='center red'>" . __('Your PHP parser was compiled without the IMAP functions');
   echo "</td></tr></table>";
   echo "</div>";
   Html::footer();
   exit();

} else {
   $mailcollector = new MailCollector();
   $mailcollector->title();
   Search::show('MailCollector');
   Html::footer();
}
?>