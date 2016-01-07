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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
// $feed = new SimplePie();
// $feed->set_cache_location('../files/_rss');
// $feed->set_cache_duration(3600);
// $feed->set_feed_url('http://linuxfr.org/news.atom');
// $feed->force_feed(true);
// // Initialize the whole SimplePie object.  Read the feed, process it, parse it, cache it, and
// // all that other good stuff.  The feed's information will not be available to SimplePie before
// // this is called.
// $success = $feed->init();
//
// // We'll make sure that the right content type and character encoding gets set automatically.
// // This function will grab the proper character encoding, as well as set the content type to text/html.
// $feed->handle_content_type();
// if ($feed->error())
// {
//    echo "ERROR";
// } else {
//    echo $feed->get_title();
//    echo $feed->get_link();
// }

/**
 * RSSFeed Class
 *
 * @since version 0.84
**/
class RSSFeed extends CommonDBTM {

   // For visibility checks
   protected $users     = array();
   protected $groups    = array();
   protected $profiles  = array();
   protected $entities  = array();

   static $rightname    = 'rssfeed_public';



   static function getTypeName($nb=0) {

      if (Session::haveRight('rssfeed_public',READ)) {
         return _n('RSS feed', 'RSS feed', $nb);
      }
      return _n('Personal RSS feed', 'Personal RSS feed', $nb);
   }


   static function canCreate() {

      return (Session::haveRight(self::$rightname, CREATE)
              || ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'));
   }


   static function canView() {

      return (Session::haveRight('rssfeed_public', READ)
              || ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'));
   }


   function canViewItem() {

      // Is my rssfeed or is in visibility
      return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight('rssfeed_public', READ)
                  && $this->haveVisibilityAccess()));
   }


   function canCreateItem() {
      // Is my rssfeed
      return ($this->fields['users_id'] == Session::getLoginUserID());
   }


   function canUpdateItem() {

      return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight('rssfeed_public', UPDATE)
                  && $this->haveVisibilityAccess()));
   }


   /**
    * @since 0.85
    * for personal rss feed
   **/
   static function canUpdate() {
      return ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
   }


   /**
    * @since 0.85
    * for personal rss feed
   **/
   static function canPurge() {
      return ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::canPurgeItem()
   **/
   function canPurgeItem() {

      return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight(self::$rightname, PURGE)
                  && $this->haveVisibilityAccess()));
   }


   function post_getFromDB() {

      // Users
      $this->users    = RSSFeed_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = Entity_RSSFeed::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = Group_RSSFeed::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = Profile_RSSFeed::getProfiles($this->fields['id']);
   }


   /**
    * @see CommonDBTM::cleanDBonPurge()
   **/
   function cleanDBonPurge() {

      $class = new RSSFeed_User();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Entity_RSSFeed();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Group_RSSFeed();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Profile_RSSFeed();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }



   function countVisibilities() {

      return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
   }


   /**
    * Is the login user have access to rssfeed based on visibility configuration
    *
    * @return boolean
   **/
   function haveVisibilityAccess() {

      // No public rssfeed right : no visibility check
      if (!self::canView()) {
         return false;
      }

      // Author
      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }

      // Users
      if (isset($this->users[Session::getLoginUserID()])) {
         return true;
      }

      // Groups
      if (count($this->groups)
          && isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         foreach ($this->groups as $key => $data) {
            foreach ($data as $group) {
               if (in_array($group['groups_id'], $_SESSION["glpigroups"])) {
                  // All the group
                  if ($group['entities_id'] < 0) {
                     return true;
                  }
                  // Restrict to entities
                  $entities    = array($group['entities_id']);
                  if ($group['is_recursive']) {
                     $entities = getSonsOf('glpi_entities', $group['entities_id']);
                  }
                  if (Session::haveAccessToOneOfEntities($entities, true)) {
                     return true;
                  }
               }
            }
         }
      }

      // Entities
      if (count($this->entities)
          && isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         foreach ($this->entities as $key => $data) {
            foreach ($data as $entity) {
               $entities    = array($entity['entities_id']);
               if ($entity['is_recursive']) {
                  $entities = getSonsOf('glpi_entities', $entity['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      // Profiles
      if (count($this->profiles)
          && isset($_SESSION["glpiactiveprofile"])
          && isset($_SESSION["glpiactiveprofile"]['id'])) {
         if (isset($this->profiles[$_SESSION["glpiactiveprofile"]['id']])) {
            foreach ($this->profiles[$_SESSION["glpiactiveprofile"]['id']] as $profile) {
               // All the profile
               if ($profile['entities_id'] < 0) {
                  return true;
               }
               // Restrict to entities
               $entities    = array($profile['entities_id']);
               if ($profile['is_recursive']) {
                  $entities = getSonsOf('glpi_entities',$profile['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      return false;
   }


   /**
    * Return visibility joins to add to SQL
    *
    * @param $forceall force all joins (false by default)
    *
    * @return string joins to add
   **/
   static function addVisibilityJoins($forceall=false) {

      if (!self::canView()) {
         return '';
      }

      // Users
      $join = " LEFT JOIN `glpi_rssfeeds_users`
                     ON (`glpi_rssfeeds_users`.`rssfeeds_id` = `glpi_rssfeeds`.`id`) ";

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join .= " LEFT JOIN `glpi_groups_rssfeeds`
                        ON (`glpi_groups_rssfeeds`.`rssfeeds_id` = `glpi_rssfeeds`.`id`) ";
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join .= " LEFT JOIN `glpi_profiles_rssfeeds`
                        ON (`glpi_profiles_rssfeeds`.`rssfeeds_id` = `glpi_rssfeeds`.`id`) ";
      }

      // Entities
      if ($forceall
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join .= " LEFT JOIN `glpi_entities_rssfeeds`
                        ON (`glpi_entities_rssfeeds`.`rssfeeds_id` = `glpi_rssfeeds`.`id`) ";
      }

      return $join;

   }


   /**
    * Return visibility SQL restriction to add
    *
    * @return string restrict to add
   **/
   static function addVisibilityRestrict() {

      $restrict = "`glpi_rssfeeds`.`users_id` = '".Session::getLoginUserID()."' ";

      if (!self::canView()) {
         return $restrict;
      }

      // Users
      $restrict .= " OR `glpi_rssfeeds_users`.`users_id` = '".Session::getLoginUserID()."' ";

      // Groups
      if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         $restrict .= " OR (`glpi_groups_rssfeeds`.`groups_id`
                                 IN ('".implode("','",$_SESSION["glpigroups"])."')
                            AND (`glpi_groups_rssfeeds`.`entities_id` < 0
                                 ".getEntitiesRestrictRequest(" OR", "glpi_groups_rssfeeds", '', '',
                                                              true).")) ";
      }

      // Profiles
      if (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
         $restrict .= " OR (`glpi_profiles_rssfeeds`.`profiles_id`
                                 = '".$_SESSION["glpiactiveprofile"]['id']."'
                            AND (`glpi_profiles_rssfeeds`.`entities_id` < 0
                                 ".getEntitiesRestrictRequest(" OR", "glpi_profiles_rssfeeds", '',
                                                              '', true).")) ";
      }

      // Entities
      if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         // Force complete SQL not summary when access to all entities
         $restrict .= getEntitiesRestrictRequest("OR","glpi_entities_rssfeeds", '', '', true, true);
      }

      return '('.$restrict.')';
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'refresh_rate':
            return Html::timestampToString($values[$field], false);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
    **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'refresh_rate' :
            return Planning::dropdownState($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   function getSearchOptions() {

      $tab                           = array();
      $tab['common']                 = __('Characteristics');

      $tab[1]['table']               = $this->getTable();
      $tab[1]['field']               = 'name';
      $tab[1]['name']                = __('Name');
      $tab[1]['datatype']            = 'itemlink';
      $tab[1]['massiveaction']       = false;
      $tab[1]['forcegroupby']        = true;

      $tab[2]['table']               = 'glpi_users';
      $tab[2]['field']               = 'name';
      $tab[2]['name']                = __('Creator');
      $tab[2]['datatype']            = 'dropdown';
      $tab[2]['massiveaction']       = false;
      $tab[2]['right']               = 'all';

      $tab[3]['table']               = $this->getTable();
      $tab[3]['field']               = 'url';
      $tab[3]['name']                = __('URL');
      $tab[3]['datatype']            = 'string';
      $tab[3]['massiveaction']       = false;

      $tab[4]['table']               = $this->getTable();
      $tab[4]['field']               = 'is_active';
      $tab[4]['name']                = __('Active');
      $tab[4]['datatype']            = 'bool';
      $tab[4]['massiveaction']       = true;

      $tab[6]['table']               = $this->getTable();
      $tab[6]['field']               = 'have_error';
      $tab[6]['name']                = __('Error');
      $tab[6]['datatype']            = 'bool';
      $tab[6]['massiveaction']       = true;

      $tab[7]['table']               = $this->getTable();
      $tab[7]['field']               = 'max_items';
      $tab[7]['name']                = __('Number of items displayed');
      $tab[7]['datatype']            = 'number';
      $tab[7]['min']                 = 5;
      $tab[7]['max']                 = 100;
      $tab[7]['step']                = 5;
      $tab[7]['toadd']               = array(1);
      $tab[7]['massiveaction']       = true;

      $tab[16]['table']              = $this->getTable();
      $tab[16]['field']              = 'comment';
      $tab[16]['name']               = __('Comments');
      $tab[16]['datatype']           = 'text';

      $tab[5]['table']               = $this->getTable();
      $tab[5]['field']               = 'refresh_rate';
      $tab[5]['name']                = __('Refresh rate');
      $tab[5]['datatype']            = 'timestamp';
      $tab[5]['min']                 = HOUR_TIMESTAMP;
      $tab[5]['max']                 = DAY_TIMESTAMP;
      $tab[5]['step']                = HOUR_TIMESTAMP;
      $tab[5]['toadd']               = array(5*MINUTE_TIMESTAMP,
                                             15*MINUTE_TIMESTAMP,
                                             30*MINUTE_TIMESTAMP,
                                             45*MINUTE_TIMESTAMP);
      $tab[5]['display_emptychoice'] = false;
      $tab[5]['massiveaction']       = true;
      $tab[5]['searchtype']          = 'equals';

      $tab[19]['table']               = $this->getTable();
      $tab[19]['field']               = 'date_mod';
      $tab[19]['name']                = __('Last update');
      $tab[19]['datatype']            = 'datetime';
      $tab[19]['massiveaction']       = false;

      return $tab;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (self::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'RSSFeed' :
               $showtab = array(1 => __('Content'));
               if (session::haveRight('rssfeed_public', UPDATE)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $item->countVisibilities();
                  }
                  $showtab[2] = self::createTabEntry(_n('Target','Targets',
                                                        Session::getPluralNumber()), $nb);
               }
               return $showtab;
         }
      }
      return '';
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'RSSFeed' :
            switch ($tabnum) {
               case 1 :
                  $item->showFeedContent();
                  return true;

               case 2 :
                  $item->showVisibility();
                  return true;
            }
      }
      return false;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if ($feed = self::getRSSFeed($input['url'])) {
         $input['have_error'] = 0;
         $input['name']       = addslashes($feed->get_title());
         if (empty($input['comment'])) {
            $input['comment'] = addslashes($feed->get_description());
         }
      } else {
         $input['have_error'] = 1;
         $input['name']       = '';
      }
      $input["name"] = trim($input["name"]);

      if (empty($input["name"])) {
         $input["name"] = __('Without title');
      }
      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForUpdate($input) {

      if (empty($input['name'])
          && isset($input['url'])
          && ($feed = self::getRSSFeed($input['url']))) {
         $input['name'] = addslashes($feed->get_title());
         if (empty($input['comment'])) {
            $input['comment'] = addslashes($feed->get_description());
         }
      }
      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if (($this->fields['users_id'] == 0)
          && ($uid = Session::getLoginUserID())) {
         $this->fields['users_id'] = $uid;
         $this->updates[]          = "users_id";
      }
   }


   function post_getEmpty() {

      $this->fields["name"]         = __('New note');
      $this->fields["users_id"]     = Session::getLoginUserID();
      $this->fields["refresh_rate"] = DAY_TIMESTAMP;
      $this->fields["max_items"]    = 20;
   }


   /**
    * Print the rssfeed form
    *
    * @param $ID        integer  Id of the item to print
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      // Test _rss cache directory. I permission trouble : unable to edit
      if (Toolbox::testWriteAccessToDirectory(GLPI_RSS_DIR) > 0) {
         echo "<div class='center'>";
         printf(__('Check permissions to the directory: %s'), GLPI_RSS_DIR);
         echo "<p class='red b'>".__('Error')."</p>";
         echo "</div>";
         return false;
      }

      $this->initForm($ID, $options);

      $canedit = $this->can($ID, UPDATE);

      $this->showFormHeader($options);

      $rowspan = 4;

      if (!$this->isNewID($ID)) {
         // Force getting feed :
         $feed = self::getRSSFeed($this->fields['url'], $this->fields['refresh_rate']);
         if (!$feed || $feed->error()) {
            $this->setError(true);
         } else {
            $this->setError(false);
         }
         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Name')."</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "name",
                                       array('entity' => -1,
                                             'user'   => $this->fields["users_id"]));
         echo "</td><td colspan ='2'>&nbsp;</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td>" . __('URL') . "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='url' size='100' value='".$this->fields["url"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('By')."</td>";
      echo "<td>";
      echo getUserName($this->fields["users_id"]);
      echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>\n";
      echo "</td>";
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".$this->fields["comment"].
           "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Refresh rate')."</td>";
      echo "<td>";
      Dropdown::showTimeStamp("refresh_rate",
                              array('value'                => $this->fields["refresh_rate"],
                                    'min'                  => HOUR_TIMESTAMP,
                                    'max'                  => DAY_TIMESTAMP,
                                    'step'                 => HOUR_TIMESTAMP,
                                    'display_emptychoice'  => false,
                                    'toadd'                => array(5*MINUTE_TIMESTAMP,
                                                                    15*MINUTE_TIMESTAMP,
                                                                    30*MINUTE_TIMESTAMP,
                                                                    45*MINUTE_TIMESTAMP)));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Number of items displayed')."</td>";
      echo "<td>";
      Dropdown::showNumber("max_items", array('value'                => $this->fields["max_items"],
                                              'min'                  => 5,
                                              'max'                  => 100,
                                              'step'                 => 5,
                                              'toadd'                => array(1),
                                              'display_emptychoice'  => false));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Error retrieving RSS feed')."</td>";
      echo "<td>";
      echo Dropdown::getYesNo($this->fields['have_error']);
      echo "</td>";
      if ($this->fields['have_error']) {
         echo "<td>".__('RSS feeds found');
         echo "</td><td>";
         $this->showDiscoveredFeeds();
         echo "</td>\n";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";
      $this->showFormButtons($options);

      return true;
   }


   /**
    * Set error field
    *
    * @param $error   (false by default
    **/
   function setError($error=false) {

      if (!isset($this->fields['id']) && !isset($this->fields['have_error'])) {
         return;
      }

      // Set error if not set
      if ($error && !$this->fields['have_error']) {
         $this->update(array('id'         => $this->fields['id'],
                             'have_error' => 1));
      }
      // Unset error if set
      if (!$error && $this->fields['have_error']) {
         $this->update(array('id'         => $this->fields['id'],
                             'have_error' => 0));
      }
   }


   /**
    * Show the feed content
    **/
   function showFeedContent() {

      if (!$this->canViewItem()) {
         return false;
      }
      $feed = self::getRSSFeed($this->fields['url'], $this->fields['refresh_rate']);
      echo "<div class='firstbloc'>";
      if (!$feed || $feed->error()) {
         _e('Error retrieving RSS feed');
         $this->setError(true);
      } else {
         $this->setError(false);
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th colspan='3'>".$feed->get_title()."</th>";
         foreach ($feed->get_items(0,$this->fields['max_items']) as $item) {
            $link = $item->get_permalink();
            echo "<tr class='tab_bg_1'><td>";
            echo HTML::convDateTime($item->get_date('Y-m-d H:i:s'));
            echo "</td><td>";
            if (!is_null($link)) {
               echo "<a target='_blank' href='$link'>".$item->get_title().'</a>';
            } else {
               $item->get_title();
            }
            echo "</td><td>";
            $rand = mt_rand();
            echo "<span id='rssitem$rand' class='pointer'>";
            echo Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($item->get_content())),
                                   1000);
            echo "</span>";
            Html::showToolTip(Toolbox::unclean_html_cross_side_scripting_deep($item->get_content()),
                               array('applyto' => "rssitem$rand",
                                     'display' => true));
            echo "</td></tr>";
         }
         echo "</table>";

      }
      echo "</div>";
   }


   /**
    * Show discovered feeds
    *
    * @return nothin
    **/
   function showDiscoveredFeeds() {

      $feed = new SimplePie();
      $feed->set_cache_location(GLPI_RSS_DIR);
      $feed->enable_cache(false);
      $feed->set_feed_url($this->fields['url']);
      $feed->init();
      $feed->handle_content_type();

      if ($feed->error()) {
         return false;
      }

      foreach ($feed->get_all_discovered_feeds() as $f) {
         $newurl  = $f->url;
         $newfeed = self::getRSSFeed($newurl);
         if ($newfeed && !$newfeed->error()) {
            $link = $newfeed->get_permalink();
            if (!empty($link)) {
               echo "<a href='$newurl'>".$newfeed->get_title()."</a>&nbsp;";
               Html::showSimpleForm($this->getFormURL(),'update', _x('button', 'Use'),
                                    array('id'  => $this->getID(),
                                          'url' => $newurl));
               echo "<br>";
            }
         }
      }

   }


   /**
    * Get a specific RSS feed
    *
    * @param $url             string/array   URL of the feed or array of URL
    * @param $cache_duration  timestamp      cache duration (default DAY_TIMESTAMP)
    *
    * @return feed object
   **/
   static function getRSSFeed($url, $cache_duration=DAY_TIMESTAMP) {

      $feed = new SimplePie();
      $feed->set_cache_location(GLPI_RSS_DIR);
      $feed->set_cache_duration($cache_duration);

      $feed->enable_cache(true);
      $feed->set_feed_url($url);
      $feed->force_feed(true);
      // Initialize the whole SimplePie object.  Read the feed, process it, parse it, cache it, and
      // all that other good stuff.  The feed's information will not be available to SimplePie before
      // this is called.
      $feed->init();

      // We'll make sure that the right content type and character encoding gets set automatically.
      // This function will grab the proper character encoding, as well as set the content type to text/html.
      $feed->handle_content_type();
      if ($feed->error()) {
         return false;
      }
      return $feed;
   }


   /**
    * Show list for central view
    *
    * @param $personal boolean   display rssfeeds created by me ? (true by default)
    *
    * @return Nothing (display function)
    **/
   static function showListForCentral($personal=true) {
      global $DB, $CFG_GLPI;

      $users_id             = Session::getLoginUserID();
      $today                = date('Y-m-d');
      $now                  = date('Y-m-d H:i:s');

      if ($personal) {

         /// Personal notes only for central view
         if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
            return false;
         }

         $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds`
                   WHERE `glpi_rssfeeds`.`users_id` = '$users_id'
                         AND `glpi_rssfeeds`.`is_active` = '1'
                   ORDER BY `glpi_rssfeeds`.`name`";

         $titre = "<a href='".$CFG_GLPI["root_doc"]."/front/rssfeed.php'>".
                    _n('Personal RSS feed', 'Personal RSS feeds', Session::getPluralNumber())."</a>";

      } else {
         // Show public rssfeeds / not mines : need to have access to public rssfeeds
         if (!self::canView()) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $restrict_user = "`glpi_rssfeeds`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds` ".
                   self::addVisibilityJoins()."
                   WHERE $restrict_user
                         AND ".self::addVisibilityRestrict()."
                   ORDER BY `glpi_rssfeeds`.`name`";

         if ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
            $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/rssfeed.php\">".
                       _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber())."</a>";
         } else {
            $titre = _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber());
         }
      }

      $result  = $DB->query($query);
      $items   = array();
      $rssfeed = new self();
      if ($nb = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if ($rssfeed->getFromDB($data['id'])) {
               // Force fetching feeds
               if ($feed = self::getRSSFeed($data['url'], $data['refresh_rate'])) {
                  // Store feeds in array of feeds
                  $items = array_merge($items, $feed->get_items(0, $data['max_items']));
                  $rssfeed->setError(false);
               } else {
                  $rssfeed->setError(true);
               }
            }
         }
      }

      echo "<br><table class='tab_cadrehov'>";
      echo "<tr class='noHover'><th colspan='2'><div class='relative'><span>$titre</span>";

      if (($personal && self::canCreate())
            || (!$personal && Session::haveRight('rssfeed_public', CREATE))) {
         echo "<span class='floatright'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/rssfeed.form.php'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/plus.png' alt='".__s('Add')."' title=\"".
                __s('Add')."\"></a></span>";
      }

      echo "</div></th></tr>\n";

      if ($nb) {
         usort($items, array('SimplePie', 'sort_items'));
         foreach ($items as $item) {
            echo "<tr class='tab_bg_1'><td>";
            echo HTML::convDateTime($item->get_date('Y-m-d H:i:s'));
            echo "</td><td>";
            $link = $item->feed->get_permalink();
            if (empty($link)) {
               echo $item->feed->get_title();
            } else {
               echo "<a target='_blank' href='$link'>".$item->feed->get_title().'</a>';
            }
            $link = $item->get_permalink();
//                echo "<br>";
//                echo $item->get_title();
//                echo "</td><td>";

            $rand = mt_rand();
            echo "<div id='rssitem$rand' class='pointer rss'>";
            if (!is_null($link)) {
               echo "<a target='_blank' href='$link'>";
            }
            echo $item->get_title();
//                echo Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($item->get_content())), 300);
            if (!is_null($link)) {
               echo "</a>";
            }
            echo "</div>";
            Html::showToolTip(Toolbox::unclean_html_cross_side_scripting_deep($item->get_content()),
                                                                        array('applyto' => "rssitem$rand",
                                                                              'display' => true));
            echo "</td></tr>";
         }
      }
      echo "</table>\n";

   }


   /**
    * Show visibility config for a rssfeed
   **/
   function showVisibility() {
      global $DB, $CFG_GLPI;

      $ID      = $this->fields['id'];
      $canedit = $this->canEdit($ID);

      $rand = mt_rand();

      $nb   = count($this->users) + count($this->groups) + count($this->profiles)
              + count($this->entities);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='rssfeedvisibility_form$rand' id='rssfeedvisibility_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('RSSFeed')."'>";
         echo "<input type='hidden' name='rssfeeds_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Add a target')."</tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

         $types   = array('Entity', 'Group', 'Profile', 'User');

         $addrand = Dropdown::showItemTypes('_type', $types);
         $params  = array('type'  => '__VALUE__',
                          'right' => 'rssfeed_public');

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand,"visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php", $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'
                              => $nb,
                           'container'
                              => 'mass'.__CLASS__.$rand,
                           'specific_actions'
                              => array('delete' => _x('button', 'Delete permanently')));

         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $massiveactionparams['confirm']
               = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
         }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Recipient', 'Recipients', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      // Users
      if (count($this->users)) {
         foreach ($this->users as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('RSSFeed_User',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('User')."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Group_RSSFeed',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Group')."</td>";

               $names   = Dropdown::getDropdownName('glpi_groups', $data['groups_id'],1);
               $entname = sprintf(__('%1$s %2$s'), $names["name"],
                                  Html::showToolTip($names["comment"], array('display' => false)));
               if ($data['entities_id'] >= 0) {
                  $entname .= sprintf(__('%1$s / %2$s'), $entname,
                                      Dropdown::getDropdownName('glpi_entities',
                                                               $data['entities_id']));
                  if ($data['is_recursive']) {
                     //TRANS: R for Recursive
                     $entname .= sprintf(__('%1$s %2$s'),
                                         $entname, "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Entity_RSSFeed',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Entity')."</td>";
               $names   = Dropdown::getDropdownName('glpi_entities', $data['entities_id'],1);
               $tooltip = Html::showToolTip($names["comment"], array('display' => false));
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['is_recursive']) {
                  $entname .= sprintf(__('%1$s %2$s'), $entname,
                                      "<span class='b'>(".__('R').")</span>");
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Profile_RSSFeed',$data["id"]);
                  echo "</td>";
               }
               echo "<td>"._n('Profile', 'Profiles', 1)."</td>";

               $names   = Dropdown::getDropdownName('glpi_profiles',$data['profiles_id'],1);
               $tooltip = Html::showToolTip($names["comment"], array('display' => false));
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['entities_id'] >= 0) {
                  $entname .= sprintf(__('%1$s / %2$s'), $entname,
                                      Dropdown::getDropdownName('glpi_entities',
                                                                $data['entities_id']));
                  if ($data['is_recursive']) {
                     $entname .= sprintf(__('%1$s %2$s'), $entname,
                                         "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      if ($interface == 'helpdesk') {
         $values = array(READ => __('Read'));
      } else {
         $values = parent::getRights();
      }
      return $values;
   }
}
?>
