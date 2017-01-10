<?php
/*
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
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/** GLPIPhpMailer class
 *
 * @since version 0.85
**/
class GLPIMailer extends PHPMailer {

   /**
    * Constructor
    *
   **/
   function __construct() {
      global $CFG_GLPI;

      $this->WordWrap           = 80;

      $this->CharSet            = "utf-8";

      // Comes from config
      $this->SetLanguage("en", Config::getLibraryDir("PHPMailer") . "/language/");

      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $this->Mailer = "smtp";
         $this->Host   = $CFG_GLPI['smtp_host'].':'.$CFG_GLPI['smtp_port'];

         if ($CFG_GLPI['smtp_username'] != '') {
            $this->SMTPAuth = true;
            $this->Username = $CFG_GLPI['smtp_username'];
            $this->Password = Toolbox::decrypt($CFG_GLPI['smtp_passwd'], GLPIKEY);
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPSSL) {
            $this->SMTPSecure = "ssl";
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPTLS) {
            $this->SMTPSecure = "tls";
         }

         if (!$CFG_GLPI['smtp_check_certificate']) {
            $this->SMTPOptions = array('ssl' => array('verify_peer'       => false,
                                                      'verify_peer_name'  => false,
                                                      'allow_self_signed' => true));
         }
      }

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $this->do_debug = 3;
      }
   }

}
