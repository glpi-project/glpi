<?php
/*
 * @version $Id: document.class.php 9112 2009-10-13 20:17:16Z moyo $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class TicketCategory extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(TICKETCATEGORY_TYPE);
   }

   function getAdditionalFields() {
      global $LANG;

      return array (array('name'  => 'users_id',
                          'label' => $LANG['common'][10],
                          'type'  => 'dropdownUsersID',
                          'list'  => true),
                    array('name'  => 'groups_id',
                          'label' => $LANG['common'][35],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'knowbaseitemscategories_id',
                          'label' => $LANG['title'][5],
                          'type'  => 'dropdownValue',
                          'list'  => true));
   }

   function getTypeName() {
      global $LANG;

      return $LANG['setup'][79];
   }
}

class TaskCategory extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(TASKCATEGORY_TYPE);
   }


   function getTypeName() {
      global $LANG;

      return $LANG['setup'][98];
   }
}

class Location extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(LOCATION_TYPE);
   }


   function getAdditionalFields() {
      global $LANG;

      return array (array('name'  => 'building',
                          'label' => $LANG['setup'][99],
                          'type'  => 'text',
                          'list'  => true),
                    array('name'  => 'room',
                          'label' => $LANG['setup'][100],
                          'type'  => 'text',
                          'list'  => true));
   }

   function getTypeName() {
      global $LANG;

      return $LANG['common'][15];
   }
}

?>
