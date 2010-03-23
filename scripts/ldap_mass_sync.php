<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

if ($argv) {
   for ($i=1;$i<count($argv);$i++)
   {
      //To be able to use = in search filters, enter \= instead in command line
      //Replace the \= by ° not to match the split function
      $arg=str_replace('\=','°',$argv[$i]);
      $it = explode("=",$arg);
      $it[0] = preg_replace('/^--/','',$it[0]);

      //Replace the ° by = the find the good filter
      $it=str_replace('°','=',$it);
      $_GET[$it[0]] = $it[1];
   }
}

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

// Default action : synchro
// - possible option :
//   - 0 : import new users
//  - 1 : synchronize users
//  - 2 : force synchronization of all the users (even if ldap timestamp wasn't modified)
if (!isset($_GET["action"])) $_GET["action"]=1;

//If no ldap_server ID is given, then use all the available servers
if (!isset($_GET["server_id"])) $_GET["server_id"]='';

//If not filter given
if (!isset($_GET["filter"])) $_GET["filter"]='';

//Get the ldap server's id by his name
if ($_GET["server_id"] != '')
   $sql = "SELECT id, name from glpi_auth_ldap WHERE id=" . $_GET["server_id"];
else
   $sql = "SELECT id, name from glpi_auth_ldap";

$result = $DB->query($sql);
if ($DB->numrows($result) == 0 && $_GET["server_id"] != '')
   echo "LDAP Server not found";
else
{
   while ($datas = $DB->fetch_array($result))
      import ($_GET["action"],$datas,$_GET["filter"]);

}

/**
 * Function to import or synchronise all the users from an ldap directory
 * @param action the action to perform (add/sync)
 * @param datas the ldap connection's datas
 */
function import($action, $datas,$filter='')
{
   //The ldap server id is passed in the script url (parameter server_id)
   $action['ldapservers_id'] = $datas["id"];
   $action['ldap_filter'] = $filter;
   $users = AuthLdap::getAllLdapUsers($action);

   foreach ($users as $user) {
      AuthLdap::ldapImportUserByServerId(array('method'=>AuthLDAP::IDENTIFIER_LOGIN,
                                               'value'=>$user["user"]), $action, $datas["id"]);
      echo ".";
   }
}
?>