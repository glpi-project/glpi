<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class GLPINetwork {

   public static function showInstallMessage() {
      return nl2br(sprintf(__("You need help to integrate GLPI in your IT, have a bug fixed or benefit from pre-configured rules or dictionaries?\n\n".
         "We provide the %s space for you.\n".
         "GLPI-Network is a commercial product that includes a subscription for tier 3 support, ensuring the correction of bugs encountered with a commitment time.\n\n".
         "In this same space, you will be able to <b>contact an official partner</b> to help you with your GLPI integration.\n\n".
         "Or, support the GLPI development effort by <b>donating</b>."),
         "<a href='".GLPI_NETWORK_SERVICES."' target='_blank'>".GLPI_NETWORK_SERVICES."</a>"));
   }

   public static function getErrorMessage() {
      return nl2br(sprintf("Having troubles setting up an advanced GLPI module?\n".
         "We can help you solve them. Sign up for support on %s.",
         "<a href='".GLPI_NETWORK_SERVICES."' target='_blank'>".GLPI_NETWORK_SERVICES."</a>"));
   }

   public static function addErrorMessageAfterRedirect() {
      Session::addMessageAfterRedirect(self::getErrorMessage(), false, ERROR);
   }
}