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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// TicketsCategory class
class TicketCategory extends CommonTreeDropdown {

   function canCreate() {
      return haveRight('entity_dropdown','w');
   }

   function canView() {
      return haveRight('entity_dropdown','r');
   }

   function getAdditionalFields() {
      global $LANG;

      return  array(array('name'  => $this->getForeignKeyField(),
                          'label' => $LANG['setup'][75],
                          'type'  => 'parent',
                          'list'  => false),
                   array('name'  => 'users_id',
                          'label' => $LANG['common'][10],
                          'type'  => 'UserDropdown',
                          'list'  => true),
                    array('name'  => 'groups_id',
                          'label' => $LANG['common'][35],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'knowbaseitemcategories_id',
                          'label' => $LANG['title'][5],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'is_helpdeskvisible',
                          'label' => $LANG['tracking'][39],
                          'type'  => 'bool',
                          'list'  => true));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][10];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[2]['table']     = 'glpi_ticketcategories';
      $tab[2]['field']     = 'is_helpdeskvisible';
      $tab[2]['linkfield'] = 'is_helpdeskvisible';
      $tab[2]['name']      = $LANG['tracking'][39];
      $tab[2]['datatype']  = 'bool';

      return $tab;
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][79];
   }

   static function dropdown($options=array()) {

      global $DB,$CFG_GLPI,$LANG;

      $item = new TicketCategory;

      $params['table']=$item->getTable();
      $params['name']=$item->getForeignKeyField();

      $params['value']='';
      $params['comments']=1;
      $params['entity']=-1;
      $params['used']=array();
      $params['auto_submit']=0;
      $params['toupdate']='';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key]=$val;
         }
      }


      $rand=mt_rand();
      $name="------";
      $comment="";
      $limit_length=$_SESSION["glpidropdown_chars_limit"];


      if (strlen($params['value'])==0) {
         $params['value']=-1;
      }

      if ($params['value'] > 0) {
         $tmpname=Dropdown::getDropdownName($params['table'],$params['value'],1);
         if ($tmpname["name"]!="&nbsp;") {
            $name=$tmpname["name"];
            $comment=$tmpname["comment"];

            if (utf8_strlen($name) > $_SESSION["glpidropdown_chars_limit"]) {
               $pos = strrpos($name,">");
               $limit_length=max(utf8_strlen($name)-$pos,$_SESSION["glpidropdown_chars_limit"]);
               if (utf8_strlen($name)>$limit_length) {
                  $name = "&hellip;".utf8_substr($name,-$limit_length);
               }
            } else {
               $limit_length = $_SESSION["glpidropdown_chars_limit"];
            }
         }
      }

      $use_ajax=false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb=0;
         if (!($params['entity']<0)) {
            $nb=countElementsInTableForEntity($params['table'],$params['entity']);
         } else {
            $nb=countElementsInTableForMyEntities($params['table']);
         }
         $nb -= count($params['used']);
         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }

      $param=array('searchText'        => '__VALUE__',
                    'value'            => $params['value'],
                    'table'            => $params['table'],
                    'itemtype'         => $item->getType(),
                    'myname'           => $params['name'],
                    'limit'            => $limit_length,
                    'comment'          => $params['comments'],
                    'rand'             => $rand,
                    'entity_restrict'  => $params['entity'],
                    'update_item'      => $params['toupdate'],
                    'is_helpdeskvisible'=> ((isset($params['is_helpdeskvisible'])
                                             && $params['is_helpdeskvisible'])?true:false),
                    'helpdesk_restrict'        => ((isset($params['interface'])
                                             && $params['interface'] == 'helpdesk')?true:false),
                    'auto_submit'      => $params['auto_submit']);

   $default="<select name='".$params['name']."' id='dropdown_".$params['name'].$rand."'>";
   $default.="<option value='".$params['value']."'>$name</option></select>";
   ajaxDropdown($use_ajax,"/ajax/dropdownTicketCategoriesValue.php",$param,$default,$rand);

      echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png'
             onmouseout=\"cleanhide('comment_".$params['name']."$rand')\"
             onmouseover=\"cleandisplay('comment_".$params['name']."$rand')\" >";
      echo "<span class='over_link' id='comment_".$params['name']."$rand'>".nl2br($comment)."</span>";

      if ($item->canCreate()) {

            echo "<img alt='' title='".$LANG['buttons'][8]."' src='".$CFG_GLPI["root_doc"].
                  "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'  onClick=\"var w = window.open('".
                  $item->getFormURL().
                  "?popup=1&amp;rand=$rand' ,'glpipopup', 'height=400, ".
                  "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
      }

   return $rand;
   }

   function post_getEmpty () {
      $this->fields['is_helpdeskvisible'] = 1;
   }
}

?>