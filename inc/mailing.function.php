<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

function testMail() {
   global $CFG_GLPI,$LANG;

   $mmail=new glpi_phpmailer();
   $mmail->From=$CFG_GLPI["admin_email"];
   $mmail->FromName=$CFG_GLPI["admin_email"];
   $mmail->AddAddress($CFG_GLPI["admin_email"], "GLPI");
   $mmail->Subject="[GLPI] ".$LANG['mailing'][32];
   $mmail->Body=$LANG['mailing'][31]."\n-- \n".$CFG_GLPI["mailing_signature"];

   if(!$mmail->Send()) {
      addMessageAfterRedirect($LANG['setup'][206],false,ERROR);
   } else {
      addMessageAfterRedirect($LANG['setup'][205]);
   }
}



/**
 * Determine if email is valid
 * @param $email email to check
 * @param $checkdns check dns entry
 * @return boolean
 * from http://www.linuxjournal.com/article/9585
 */
function isValidEmail($email,$checkdns=false) {

   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex) {
      $isValid = false;
   } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
         // local part length exceeded
         $isValid = false;
      } else if ($domainLen < 1 || $domainLen > 255) {
         // domain part length exceeded
         $isValid = false;
      } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
         // local part starts or ends with '.'
         $isValid = false;
      } else if (preg_match('/\\.\\./', $local)) {
         // local part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
         // character not valid in domain part
         $isValid = false;
      } else if (preg_match('/\\.\\./', $domain)) {
         // domain part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                             str_replace("\\\\","",$local))) {
         // character not valid in local part unless
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
            $isValid = false;
         }
      }
      if ($checkdns) {
         if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
         }
      } else if (!preg_match('/\\./', $domain)|| !preg_match("/[a-zA-Z0-9]$/", $domain)) {
         // domain has no dots or do not end by alphenum char
         $isValid = false;
      }
   }
   return $isValid;
}

/*
/**
 * Determine if email is valid
 * @param $email email to check
 * @return boolean
 *
function isValidEmail($email="") {

   if ( !preg_match( "/^" .
                     "[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
                     "@" .
                     "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
                     "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld
                     "$/i", $email)) {
      return false;
   } else {
      return true;
   }
}
*/
function isAuthorMailingActivatedForHelpdesk() {
   global $DB,$CFG_GLPI;

   if ($CFG_GLPI['use_mailing']) {
      $query="SELECT COUNT(id)
              FROM `glpi_mailingsettings`
              WHERE `type` IN ('new','followup','update','finish')
                    AND `mailingtype` = '".USER_MAILING_TYPE."'
                    AND `items_id` = '".AUTHOR_MAILING."'";
      if ($result=$DB->query($query)) {
         if ($DB->result($result,0,0)>0) {
            return true;
         }
      }
   }
   return false;
}

?>