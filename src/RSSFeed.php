<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\URL;

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
 * @since 0.84
 **/
class RSSFeed extends CommonDBVisible implements ExtraVisibilityCriteria
{
   // From CommonDBTM
    public $dohistory                   = true;

    public static $rightname    = 'rssfeed_public';

    const PERSONAL = 128;

    public static function getTypeName($nb = 0)
    {

        if (Session::haveRight('rssfeed_public', READ)) {
            return _n('RSS feed', 'RSS feed', $nb);
        }
        return _n('Personal RSS feed', 'Personal RSS feed', $nb);
    }


    public static function canCreate()
    {

        return (Session::haveRightsOr(self::$rightname, [CREATE, self::PERSONAL]));
    }


    public static function canView()
    {

        return (Session::haveRightsOr(self::$rightname, [READ, self::PERSONAL]));
    }


    public function canViewItem()
    {

       // Is my rssfeed or is in visibility
        return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight('rssfeed_public', READ)
                  && $this->haveVisibilityAccess()));
    }


    public function canCreateItem()
    {
       // Is my rssfeed
        return ($this->fields['users_id'] == Session::getLoginUserID());
    }


    public function canUpdateItem()
    {

        return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight('rssfeed_public', UPDATE)
                  && $this->haveVisibilityAccess()));
    }


    /**
     * @since 0.85
     * for personal rss feed
     **/
    public static function canUpdate()
    {
        return (Session::haveRightsOr(self::$rightname, [UPDATE, self::PERSONAL]));
    }


    /**
     * @since 0.85
     * for personal rss feed
     **/
    public static function canPurge()
    {
        return (Session::haveRightsOr(self::$rightname, [PURGE, self::PERSONAL]));
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::canPurgeItem()
     **/
    public function canPurgeItem()
    {

        return (($this->fields['users_id'] == Session::getLoginUserID())
              || (Session::haveRight(self::$rightname, PURGE)
                  && $this->haveVisibilityAccess()));
    }


    public function post_getFromDB()
    {

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
    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Entity_RSSFeed::class,
                Group_RSSFeed::class,
                Profile_RSSFeed::class,
                RSSFeed_User::class,
            ]
        );
    }

    public function haveVisibilityAccess()
    {
        if (!self::canView()) {
            return false;
        }

        return parent::haveVisibilityAccess();
    }

    /**
     * Return visibility joins to add to SQL
     *
     * @param $forceall force all joins (false by default)
     *
     * @return string joins to add
     **/
    public static function addVisibilityJoins($forceall = false)
    {
       //not deprecated because used in Search
        /** @var \DBmysql $DB */
        global $DB;

       //get and clean criteria
        $criteria = self::getVisibilityCriteria();
        unset($criteria['WHERE']);
        $criteria['FROM'] = self::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = trim(str_replace(
            'SELECT * FROM ' . $DB->quoteName(self::getTable()),
            '',
            $sql
        ));
        return $sql;
    }


    /**
     * Return visibility SQL restriction to add
     *
     * @return string restrict to add
     **/
    public static function addVisibilityRestrict()
    {
       //not deprecated because used in Search

       //get and clean criteria
        $criteria = self::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = self::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = preg_replace('/.*WHERE /', '', $sql);

        return $sql;
    }

    /**
     * Return visibility joins to add to DBIterator parameters
     *
     * @since 9.4
     *
     * @param boolean $forceall force all joins (false by default)
     *
     * @return array
     */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        $where = [self::getTable() . '.users_id' => Session::getLoginUserID()];
        $join = [];

        if (!self::canView()) {
            return [
                'LEFT JOIN' => $join,
                'WHERE'     => $where
            ];
        }

       //JOINs
       // Users
        $join['glpi_rssfeeds_users'] = [
            'ON' => [
                'glpi_rssfeeds_users'   => 'rssfeeds_id',
                'glpi_rssfeeds'         => 'id'
            ]
        ];

        $where = [
            'OR' => [
                self::getTable() . '.users_id'   => Session::getLoginUserID(),
                'glpi_rssfeeds_users.users_id'   => Session::getLoginUserID()
            ]
        ];
        $orwhere = [];

       // Groups
        if (
            $forceall
            || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))
        ) {
            $join['glpi_groups_rssfeeds'] = [
                'ON' => [
                    'glpi_groups_rssfeeds'  => 'rssfeeds_id',
                    'glpi_rssfeeds'         => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
            $restrict = getEntitiesRestrictCriteria('glpi_groups_rssfeeds', '', '', true);
            $orwhere[] = [
                'glpi_groups_rssfeeds.groups_id' => count($_SESSION["glpigroups"])
                                                      ? $_SESSION["glpigroups"]
                                                      : [-1],
                'OR' => [
                    'glpi_groups_rssfeeds.no_entity_restriction' => 1,
                ] + $restrict
            ];
        }

       // Profiles
        if (
            $forceall
            || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))
        ) {
            $join['glpi_profiles_rssfeeds'] = [
                'ON' => [
                    'glpi_profiles_rssfeeds'   => 'rssfeeds_id',
                    'glpi_rssfeeds'            => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
            $restrict = getEntitiesRestrictCriteria('glpi_entities_rssfeeds', '', '', true);
            if (!count($restrict)) {
                $restrict = [true];
            }
            $ors = [
                'glpi_profiles_rssfeeds.no_entity_restriction' => 1,
                $restrict
            ];

            $orwhere[] = [
                'glpi_profiles_rssfeeds.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                'OR' => $ors
            ];
        }

       // Entities
        if (
            $forceall
            || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))
        ) {
            $join['glpi_entities_rssfeeds'] = [
                'ON' => [
                    'glpi_entities_rssfeeds'   => 'rssfeeds_id',
                    'glpi_rssfeeds'            => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
           // Force complete SQL not summary when access to all entities
            $restrict = getEntitiesRestrictCriteria('glpi_entities_rssfeeds', '', '', true, true);
            if (count($restrict)) {
                $orwhere[] = $restrict;
            }
        }

        $where['OR'] = array_merge($where['OR'], $orwhere);
        $criteria = ['LEFT JOIN' => $join];
        if (count($where)) {
            $criteria['WHERE'] = $where;
        }

        return $criteria;
    }

    /**
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
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
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'refresh_rate':
                return Planning::dropdownState($name, $values[$field], false);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => __('Creator'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'right'              => 'all'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'url',
            'name'               => __('URL'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
            'massiveaction'      => true
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'have_error',
            'name'               => __('Error'),
            'datatype'           => 'bool',
            'massiveaction'      => true
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'max_items',
            'name'               => __('Number of items displayed'),
            'datatype'           => 'number',
            'min'                => 5,
            'max'                => 100,
            'step'               => 5,
            'toadd'              => [1],
            'massiveaction'      => true
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'refresh_rate',
            'name'               => __('Refresh rate'),
            'datatype'           => 'timestamp',
            'min'                => HOUR_TIMESTAMP,
            'max'                => DAY_TIMESTAMP,
            'step'               => HOUR_TIMESTAMP,
            'toadd'              => [
                5 * MINUTE_TIMESTAMP,
                15 * MINUTE_TIMESTAMP,
                30 * MINUTE_TIMESTAMP,
                45 * MINUTE_TIMESTAMP
            ],
            'display_emptychoice' => false,
            'massiveaction'      => true,
            'searchtype'         => 'equals'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }


    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (self::canView()) {
            $nb = 0;
            switch (get_class($item)) {
                case RSSFeed::class:
                    $showtab = [1 => __('Content')];
                    if (Session::haveRight('rssfeed_public', UPDATE)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = $item->countVisibilities();
                        }
                        $showtab[2] = self::createTabEntry(_n(
                            'Target',
                            'Targets',
                            Session::getPluralNumber()
                        ), $nb);
                    }
                    return $showtab;
            }
        }
        return '';
    }


    /**
     * @see CommonGLPI::defineTabs()
     **/
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    /**
     * @param $item         CommonGLPI object
     * @param $tabnum       (default 1)
     * @param $withtemplate (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case RSSFeed::class:
                switch ($tabnum) {
                    case 1:
                        $item->showFeedContent();
                        return true;

                    case 2:
                        $item->showVisibility();
                        return true;
                }
        }
        return false;
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->checkUrlInput($input['url'])) {
            return false;
        }

        $current_user_id = Session::getLoginUserID();
        if ($current_user_id === false) {
            // RSSFeeds are not supposed to be created in a sessionless context.
            return false;
        }
        $input['users_id'] = $current_user_id;

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

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('url', $input) && !$this->checkUrlInput($input['url'])) {
            return false;
        }

        if (
            empty($input['name'])
            && isset($input['url'])
            && ($feed = self::getRSSFeed($input['url']))
        ) {
            $input['name'] = addslashes($feed->get_title());
            if (empty($input['comment'])) {
                $input['comment'] = addslashes($feed->get_description());
            }
        }

        // Owner cannot be changed
        unset($input['users_id']);

        return $input;
    }

    /**
     * Check URL given in input.
     * @param string $url
     * @return bool
     */
    private function checkUrlInput(string $url): bool
    {
        if (parse_url($url) === false) {
            Session::addMessageAfterRedirect(__('Feed URL is invalid.'), false, ERROR);
            return false;
        } elseif (!Toolbox::isUrlSafe($url)) {
            Session::addMessageAfterRedirect(sprintf(__('URL "%s" is not allowed by your administrator.'), $url), false, ERROR);
            return false;
        }

        return true;
    }


    public function post_getEmpty()
    {

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
    public function showForm($ID, array $options = [])
    {
       // Test _rss cache directory. I permission trouble : unable to edit
        if (Toolbox::testWriteAccessToDirectory(GLPI_RSS_DIR) > 0) {
            echo "<div class='center'>";
            printf(__('Check permissions to the directory: %s'), GLPI_RSS_DIR);
            echo "<p class='red b'>" . __('Error') . "</p>";
            echo "</div>";
            return false;
        }

        $this->initForm($ID, $options);

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
            echo "<td>" . __('Name') . "</td>";
            echo "<td>";
            echo Html::input('name', ['value' => $this->fields['name']]);
            echo "</td><td colspan ='2'>&nbsp;</td></tr>\n";
        }

        echo "<tr class='tab_bg_1'><td>" . __('URL') . "</td>";
        echo "<td colspan='3'>";
        echo "<input type='text' name='url' size='100' value='" . htmlspecialchars($this->fields["url"], ENT_QUOTES) . "' class='form-control'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('By') . "</td>";
        echo "<td>";
        echo getUserName($this->fields["users_id"]);
        echo "</td>";
        echo "<td rowspan='$rowspan'>" . __('Comments') . "</td>";
        echo "<td rowspan='$rowspan' class='middle'>";
        echo "<textarea  class='form-control' rows='" . ($rowspan + 3) . "' name='comment' >" . $this->fields["comment"] .
           "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Refresh rate') . "</td>";
        echo "<td>";
        Dropdown::showTimeStamp(
            "refresh_rate",
            ['value'                => $this->fields["refresh_rate"],
                'min'                  => HOUR_TIMESTAMP,
                'max'                  => DAY_TIMESTAMP,
                'step'                 => HOUR_TIMESTAMP,
                'display_emptychoice'  => false,
                'toadd'                => [5 * MINUTE_TIMESTAMP,
                    15 * MINUTE_TIMESTAMP,
                    30 * MINUTE_TIMESTAMP,
                    45 * MINUTE_TIMESTAMP
                ]
            ]
        );
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Number of items displayed') . "</td>";
        echo "<td>";
        Dropdown::showNumber("max_items", ['value'                => $this->fields["max_items"],
            'min'                  => 5,
            'max'                  => 100,
            'step'                 => 5,
            'toadd'                => [1],
            'display_emptychoice'  => false
        ]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Error retrieving RSS feed') . "</td>";
        echo "<td>";
        if ($this->fields['have_error'] && !Toolbox::isUrlSafe($this->fields['url'])) {
            echo sprintf(__('URL "%s" is not allowed by your administrator.'), $this->fields['url']);
        } else {
            echo Dropdown::getYesNo($this->fields['have_error']);
        }
        echo "</td>";
        echo "<td colspan='2'>&nbsp;</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }


    /**
     * Set error field
     *
     * @param $error   (false by default
     **/
    public function setError($error = false)
    {

        if (!isset($this->fields['id']) && !isset($this->fields['have_error'])) {
            return;
        }

       // Set error if not set
        if ($error && !$this->fields['have_error']) {
            $this->update(['id'         => $this->fields['id'],
                'have_error' => 1
            ]);
        }
       // Unset error if set
        if (!$error && $this->fields['have_error']) {
            $this->update(['id'         => $this->fields['id'],
                'have_error' => 0
            ]);
        }
    }


    /**
     * Show the feed content
     **/
    public function showFeedContent()
    {

        if (!$this->canViewItem()) {
            return false;
        }
        $rss_feed = [
            'items'  => []
        ];
        echo "<div class='firstbloc'>";
        if ($feed = self::getRSSFeed($this->fields['url'], $this->fields['refresh_rate'])) {
            $this->setError(false);
            $rss_feed['title'] = $feed->get_title();
            foreach ($feed->get_items(0, $this->fields['max_items']) as $item) {
                $rss_feed['items'][] = [
                    'title'     => $item->get_title(),
                    'link'      => URL::sanitizeURL($item->get_permalink()),
                    'timestamp' => Html::convDateTime($item->get_date('Y-m-d H:i:s')),
                    'content'   => RichText::getSafeHtml($item->get_content()),
                ];
            }
        } else {
            $rss_feed['error'] = !Toolbox::isUrlSafe($this->fields['url'])
                ? sprintf(__('URL "%s" is not allowed by your administrator.'), $this->fields['url'])
                : __('Error retrieving RSS feed');
            $this->setError(true);
        }

        TemplateRenderer::getInstance()->display('components/rss_feed.html.twig', [
            'rss_feed'  => $rss_feed
        ]);
    }


    /**
     * Show discovered feeds
     *
     * @return void
     *
     * @deprecated
     **/
    public function showDiscoveredFeeds()
    {
        Toolbox::deprecated();
        if (!Toolbox::isUrlSafe($this->fields['url'])) {
            return;
        }

        $feed = new SimplePie();
        $feed->set_cache_location(GLPI_RSS_DIR);
        $feed->enable_cache(false);
        $feed->set_feed_url($this->fields['url']);
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            return;
        }

        foreach ($feed->get_all_discovered_feeds() as $f) {
            $newurl  = $f->url;
            $newfeed = self::getRSSFeed($newurl);
            if ($newfeed && !$newfeed->error()) {
                $link = URL::sanitizeURL($newfeed->get_permalink());
                if (!empty($link)) {
                     echo "<a href='" . htmlspecialchars($newurl, ENT_QUOTES) . "'>" . $newfeed->get_title() . "</a>&nbsp;";
                     Html::showSimpleForm(
                         $this->getFormURL(),
                         'update',
                         _x('button', 'Use'),
                         ['id'  => $this->getID(),
                             'url' => $newurl
                         ]
                     );
                     echo "<br>";
                }
            }
        }
    }


    /**
     * Get a specific RSS feed.
     *
     * @param string    $url            URL of the feed or array of URL
     * @param int       $cache_duration Cache duration, in seconds
     *
     * @return SimplePie|false
     **/
    public static function getRSSFeed($url, $cache_duration = DAY_TIMESTAMP)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        if (Sanitizer::isHtmlEncoded($url)) {
            $url = Sanitizer::decodeHtmlSpecialChars($url);
        }

        // Fetch feed data, unless it is already cached
        $cache_key = sha1($url);
        $update_cache = false;
        if (($raw_data = $GLPI_CACHE->get($cache_key)) === null) {
            if (!Toolbox::isUrlSafe($url)) {
                return false;
            }

            $error_msg  = null;
            $curl_error = null;
            $raw_data = Toolbox::callCurl($url, [], $error_msg, $curl_error, true);
            if (empty($raw_data)) {
                return false;
            }

            $doc = new DOMDocument();
            if (!@$doc->loadXML($raw_data)) {
                // Prevent exception on invalid XML (see https://github.com/simplepie/simplepie/pull/747)
                return false;
            }

            $update_cache = true;
        }

        $feed = new SimplePie();
        $feed->enable_cache(false);
        $feed->set_raw_data($raw_data);
        $feed->force_feed(true);
        // Initialize the whole SimplePie object. Read the feed, process it, parse it, cache it, and
        // all that other good stuff. The feed's information will not be available to SimplePie before
        // this is called.
        $feed->init();

        if ($feed->error()) {
            return false;
        }

        if ($update_cache) {
            $GLPI_CACHE->set($cache_key, $raw_data, $cache_duration);
        }

        return $feed;
    }


    /**
     * Show list for central view
     *
     * @param boolean $personal display rssfeeds created by me?
     * @param boolean $display  if false, return html
     *
     * @return false|void|string
     **/
    public static function showListForCentral(bool $personal = true, bool $display = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $users_id             = Session::getLoginUserID();

        $table = self::getTable();
        $criteria = [
            'SELECT'   => "$table.*",
            'DISTINCT' => true,
            'FROM'     => $table,
            'ORDER'    => "$table.name"
        ];

        if ($personal) {
           /// Personal notes only for central view
            if (Session::getCurrentInterface() == 'helpdesk') {
                return false;
            }

            $criteria['WHERE']["$table.users_id"] = $users_id;
            $criteria['WHERE']["$table.is_active"] = 1;

            $titre = "<a href='" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php'>" .
                    _n('Personal RSS feed', 'Personal RSS feeds', Session::getPluralNumber()) . "</a>";
        } else {
           // Show public rssfeeds / not mines : need to have access to public rssfeeds
            if (!self::canView()) {
                return false;
            }

            $criteria = $criteria + self::getVisibilityCriteria();

           // Only personal on central so do not keep it
            if (Session::getCurrentInterface() == 'central') {
                $criteria['WHERE']["$table.users_id"] = ['<>', $users_id];
            }

            if (Session::getCurrentInterface() == 'central') {
                $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" .
                       _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber()) . "</a>";
            } else {
                $titre = _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber());
            }
        }

        $iterator = $DB->request($criteria);
        $nb = count($iterator);
        $items   = [];
        $rssfeed = new self();
        foreach ($iterator as $data) {
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

        $output = "";
        $output .= "<table class='table table-striped table-hover card-table'>";
        $output .= "<thead>";
        $output .= "<tr class='noHover'><th colspan='2'><div class='relative'><span>$titre</span>";

        if (
            ($personal && self::canCreate())
            || (!$personal && Session::haveRight('rssfeed_public', CREATE))
        ) {
            $output .= "<span class='float-end'>";
            $output .= "<a href='" . RSSFeed::getFormURL() . "'>";
            $output .= "<img src='" . $CFG_GLPI["root_doc"] . "/pics/plus.png' alt='" . __s('Add') . "' title=\"" .
                __s('Add') . "\"></a></span>";
        }

        $output .= "</div></th></tr>";
        $output .= "</thead>";

        if ($nb) {
            usort($items, ['SimplePie', 'sort_items']);
            foreach ($items as $item) {
                $output .= "<tr class='tab_bg_1'><td>";
                $output .= Html::convDateTime($item->get_date('Y-m-d H:i:s'));
                $output .= "</td><td>";
                $feed_link = URL::sanitizeURL($item->feed->get_permalink());
                if (empty($feed_link)) {
                    $output .= $item->feed->get_title();
                } else {
                    $output .= '<a target="_blank" href="' . htmlspecialchars($feed_link) . '">' . $item->feed->get_title() . '</a>';
                }

                $item_link = URL::sanitizeURL($item->get_permalink());
                $rand = mt_rand();
                $output .= "<div id='rssitem$rand'>";
                if (!empty($item_link)) {
                    $output .= '<a target="_blank" href="' . htmlspecialchars($item_link) . '">';
                }
                $output .= $item->get_title();
                if (!empty($item_link)) {
                    $output .= "</a>";
                }
                $output .= "</div>";
                $output .= Html::showToolTip(RichText::getEnhancedHtml($item->get_content()), [
                    'applyto' => "rssitem$rand",
                    'display' => false
                ]);
                $output .= "</td></tr>";
            }
        }
        $output .= "</table>";

        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        if ($interface == 'helpdesk') {
            $values = [READ => __('Read')];
        } else {
            $values = parent::getRights();
            $values[self::PERSONAL] = __('Manage personal');
        }
        return $values;
    }


    public static function getIcon()
    {
        return "ti ti-rss";
    }
}
