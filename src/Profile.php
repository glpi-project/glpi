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

use Glpi\Event;
use Glpi\Toolbox\ArrayNormalizer;

/**
 * Profile class
 **/
class Profile extends CommonDBTM
{
   // Specific ones

   /// Helpdesk fields of helpdesk profiles
    public static $helpdesk_rights = [
        'create_ticket_on_login',
        'changetemplates_id',
        'followup',
        'helpdesk_hardware',
        'helpdesk_item_type',
        'knowbase',
        'password_update',
        'personalization',
        'problemtemplates_id',
        'reminder_public',
        'reservation',
        'rssfeed_public',
        'show_group_hardware',
        'task',
        'ticket',
        'ticket_cost',
        'ticket_status',
        'tickettemplates_id',
        'ticketvalidation',
    ];


   /// Common fields used for all profiles type
    public static $common_fields  = ['id', 'interface', 'is_default', 'name'];

    public $dohistory             = true;

    public static $rightname             = 'profile';

    /**
     * Profile rights to update after profile update.
     * @var array
     */
    private $profileRight;

    public function __get(string $property)
    {
        $value = null;
        switch ($property) {
            case 'profileRight':
                Toolbox::deprecated(sprintf('Reading private property %s::%s is deprecated', __CLASS__, $property));
                $value = $this->$property;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
        return $value;
    }

    public function __set(string $property, $value)
    {
        switch ($property) {
            case 'profileRight':
                Toolbox::deprecated(sprintf('Writing private property %s::%s is deprecated', __CLASS__, $property));
                $this->$property = $value;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'clone';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Profile', 'Profiles', $nb);
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Profile_User', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            switch (get_class($item)) {
                case __CLASS__:
                    if ($item->fields['interface'] == 'helpdesk') {
                        $ong[3] = __('Assistance'); // Helpdesk
                        $ong[4] = __('Life cycles');
                        $ong[6] = __('Tools');
                        $ong[8] = __('Setup');
                    } else {
                        $ong[2] = _n('Asset', 'Assets', Session::getPluralNumber());
                        $ong[3] = __('Assistance');
                        $ong[4] = __('Life cycles');
                        $ong[5] = __('Management');
                        $ong[6] = __('Tools');
                        $ong[7] = __('Administration');
                        $ong[8] = __('Setup');
                    }
                    return $ong;
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (get_class($item) == __CLASS__) {
            $item->cleanProfile();
            switch ($tabnum) {
                case 2:
                    $item->showFormAsset();
                    break;

                case 3:
                    if ($item->fields['interface'] == 'helpdesk') {
                        $item->showFormTrackingHelpdesk();
                    } else {
                        $item->showFormTracking();
                    }
                    break;

                case 4:
                    if ($item->fields['interface'] == 'helpdesk') {
                        $item->showFormLifeCycleHelpdesk();
                    } else {
                        $item->showFormLifeCycle();
                    }
                    break;

                case 5:
                    $item->showFormManagement();
                    break;

                case 6:
                    if ($item->fields['interface'] == 'helpdesk') {
                        $item->showFormToolsHelpdesk();
                    } else {
                        $item->showFormTools();
                    }
                    break;

                case 7:
                    $item->showFormAdmin();
                    break;

                case 8:
                    if ($item->fields['interface'] == 'helpdesk') {
                        $item->showFormSetupHelpdesk();
                    } else {
                        $item->showFormSetup();
                    }
                    break;
            }
        }
        return true;
    }


    public function post_updateItem($history = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (count($this->profileRight) > 0) {
            ProfileRight::updateProfileRights($this->getID(), $this->profileRight);
            $this->profileRight = null;
        }

        if (in_array('is_default', $this->updates) && ($this->input["is_default"] == 1)) {
            $DB->update(
                $this->getTable(),
                [
                    'is_default' => 0
                ],
                [
                    'id' => ['<>', $this->input['id']]
                ]
            );
        }

       // To avoid log out and login when rights change (very useful in debug mode)
        if (
            isset($_SESSION['glpiactiveprofile']['id'])
            && $_SESSION['glpiactiveprofile']['id'] == $this->input['id']
        ) {
            if (in_array('helpdesk_item_type', $this->updates)) {
                $_SESSION['glpiactiveprofile']['helpdesk_item_type'] = importArrayFromDB($this->input['helpdesk_item_type']);
            }

            if (in_array('managed_domainrecordtypes', $this->updates)) {
                $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] = importArrayFromDB($this->input['managed_domainrecordtypes']);
            }

           ///TODO other needed fields
        }
    }


    public function post_addItem()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $rights = ProfileRight::getAllPossibleRights();
        ProfileRight::updateProfileRights($this->fields['id'], $rights);
        $this->profileRight = null;

        if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
            $DB->update(
                $this->getTable(),
                [
                    'is_default' => 0
                ],
                [
                    'id' => ['<>', $this->fields['id']]
                ]
            );
        }
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                KnowbaseItem_Profile::class,
                Profile_Reminder::class,
                Profile_RSSFeed::class,
                Profile_User::class,
                ProfileRight::class,
            ]
        );

        Rule::cleanForItemAction($this);
       // PROFILES and UNIQUE_PROFILE in RuleMailcollector
        Rule::cleanForItemCriteria($this, 'PROFILES');
        Rule::cleanForItemCriteria($this, 'UNIQUE_PROFILE');
    }


    public function prepareInputForUpdate($input)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isset($input["_helpdesk_item_types"])) {
            if ((!isset($input["helpdesk_item_type"])) || (!is_array($input["helpdesk_item_type"]))) {
                $input["helpdesk_item_type"] = [];
            }
            $input["helpdesk_item_type"] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input["helpdesk_item_type"], 'strval')
            );
        }

        if (isset($input["_managed_domainrecordtypes"])) {
            if ((!isset($input["managed_domainrecordtypes"])) || (!is_array($input["managed_domainrecordtypes"]))) {
                $input["managed_domainrecordtypes"] = [];
            }
            if (in_array(-1, $input['managed_domainrecordtypes'])) {
               //when all selected, keep only all
                $input['managed_domainrecordtypes'] = [-1];
            }
            $input["managed_domainrecordtypes"] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input["managed_domainrecordtypes"], 'intval')
            );
        }

        if (isset($input['helpdesk_hardware']) && is_array($input['helpdesk_hardware'])) {
            $helpdesk_hardware = 0;
            foreach ($input['helpdesk_hardware'] as $right => $value) {
                if ($value) {
                    $helpdesk_hardware += $right;
                }
            }
            $input['helpdesk_hardware'] = $helpdesk_hardware;
        }

        if (isset($input["_cycle_ticket"])) {
            $tab   = array_keys(Ticket::getAllStatusArray());
            $cycle = [];
            foreach ($tab as $from) {
                foreach ($tab as $dest) {
                    if (
                        ($from != $dest)
                        && (!isset($input["_cycle_ticket"][$from][$dest])
                        || ($input["_cycle_ticket"][$from][$dest] == 0))
                    ) {
                        $cycle[$from][$dest] = 0;
                    }
                }
            }
            $input["ticket_status"] = exportArrayToDB($cycle);
        }

        if (isset($input["_cycle_problem"])) {
            $tab   = Problem::getAllStatusArray();
            $cycle = [];
            foreach ($tab as $from => $label) {
                foreach ($tab as $dest => $label2) {
                    if (
                        ($from !== $dest)
                        && ($input["_cycle_problem"][$from][$dest] == 0)
                    ) {
                        $cycle[$from][$dest] = 0;
                    }
                }
            }
            $input["problem_status"] = exportArrayToDB($cycle);
        }

        if (isset($input["_cycle_change"])) {
            $tab   = Change::getAllStatusArray();
            $cycle = [];
            foreach ($tab as $from => $label) {
                foreach ($tab as $dest => $label2) {
                    if (
                        ($from !== $dest)
                        && ($input["_cycle_change"][$from][$dest] == 0)
                    ) {
                        $cycle[$from][$dest] = 0;
                    }
                }
            }
            $input["change_status"] = exportArrayToDB($cycle);
        }

       // keep only unnecessary rights when switching from standard to self-service interface
        if (!isset($input["_ticket"]) && isset($input['interface']) && $input['interface'] == "helpdesk") {
            $ticket = new Ticket();
            $ss_rights = $ticket->getRights("helpdesk");
            $ss_rights = array_keys($ss_rights);

            $input["_ticket"] = [];
            foreach ($ss_rights as $right) {
                $input["_ticket"][$right] = ($this->fields['ticket'] & $right) ? 1 : 0;
            }
        }


        // Check if profile edit right was removed
        $can_edit_profile = $this->fields['profile'] & UPDATE == UPDATE;
        $updated_value = $input['_profile'][UPDATE . "_0"] ?? null;
        $update_profiles_right_was_removed = $updated_value !== null && !(bool) $updated_value;
        if (
            $can_edit_profile
            && $update_profiles_right_was_removed
            && $this->isLastSuperAdminProfile()
        ) {
            Session::addMessageAfterRedirect(
                __("Can't remove update right on this profile as it is the only remaining profile with this right."),
                false,
                ERROR
            );
            unset($input['_profile']);
        }

        if (isset($input['interface']) && $input['interface'] == 'helpdesk' && $this->isLastSuperAdminProfile()) {
            Session::addMessageAfterRedirect(
                __("Can't change the interface on this profile as it is the only remaining profile with rights to modify profiles with this interface."),
                false,
                ERROR
            );
            unset($input['interface']);
        }

        // If the profile is used as the "Profile to be used when locking items",
        // it can't be set to the "helpdesk" interface.
        if (
            isset($input['interface'])
            && $input['interface'] === "helpdesk"
            && $this->fields['id'] === (int) $CFG_GLPI['lock_lockprofile_id']
        ) {
            Session::addMessageAfterRedirect(
                __("This profile can't be moved to the simplified interface as it is used for locking items."),
                false,
                ERROR
            );
            unset($input['interface']);
        }

        // KEEP AT THE END
        $this->profileRight = [];
        foreach (array_keys(ProfileRight::getAllPossibleRights()) as $right) {
            if (isset($input['_' . $right])) {
                if (!is_array($input['_' . $right])) {
                    $input['_' . $right] = ['1' => $input['_' . $right]];
                }
                $newvalue = 0;
                foreach ($input['_' . $right] as $value => $valid) {
                    if ($valid) {
                        if (($underscore_pos = strpos($value, '_')) !== false) {
                            $value = substr($value, 0, $underscore_pos);
                        }
                        $newvalue += $value;
                    }
                }
               // Update rights only if changed
                if (!isset($this->fields[$right]) || ($this->fields[$right] != $newvalue)) {
                    $this->profileRight[$right] = $newvalue;
                }
                unset($input['_' . $right]);
            }
        }
        return $input;
    }


    /**
     * check right before delete
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public function pre_deleteItem()
    {
        if (
            ($this->fields['profile'] & DELETE)
            && (countElementsInTable(
                "glpi_profilerights",
                ['name' => 'profile', 'rights' => ['&', DELETE]]
            ))
        ) {
            Session::addMessageAfterRedirect(
                __("This profile is the last with write rights on profiles"),
                false,
                ERROR
            );
            Session::addMessageAfterRedirect(__("Deletion refused"), false, ERROR);
            return false;
        }
        return true;
    }


    public function prepareInputForAdd($input)
    {

        if (isset($input["helpdesk_item_type"])) {
            $input["helpdesk_item_type"] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input["helpdesk_item_type"], 'strval')
            );
        }

        if (isset($input["managed_domainrecordtypes"])) {
            $input["managed_domainrecordtypes"] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input["managed_domainrecordtypes"], 'intval')
            );
        }

        $this->profileRight = [];
        foreach (array_keys(ProfileRight::getAllPossibleRights()) as $right) {
            if (isset($input[$right])) {
                $this->profileRight[$right] = $input[$right];
                unset($input[$right]);
            }
        }

       // Set default values, only needed for helpdesk
        $interface = isset($input['interface']) ? $input['interface'] : "";
        if ($interface == "helpdesk" && !isset($input["_cycle_ticket"])) {
            $tab   = array_keys(Ticket::getAllStatusArray());
            $cycle = [];
            foreach ($tab as $from) {
                foreach ($tab as $dest) {
                    if ($from != $dest) {
                        $cycle[$from][$dest] = 0;
                    }
                }
            }
            $input["ticket_status"] = exportArrayToDB($cycle);
        }

        return $input;
    }


    /**
     * Unset unused rights for helpdesk
     **/
    public function cleanProfile()
    {

        if (isset($this->fields['interface']) && $this->fields["interface"] == "helpdesk") {
            foreach ($this->fields as $key => $val) {
                if (
                    !in_array($key, self::$common_fields)
                    && !in_array($key, self::$helpdesk_rights)
                ) {
                    unset($this->fields[$key]);
                }
            }
        }

       // decode array
        if (
            isset($this->fields["helpdesk_item_type"])
            && !is_array($this->fields["helpdesk_item_type"])
        ) {
            $this->fields["helpdesk_item_type"] = importArrayFromDB($this->fields["helpdesk_item_type"]);
        }

       // Empty/NULL case
        if (
            !isset($this->fields["helpdesk_item_type"])
            || !is_array($this->fields["helpdesk_item_type"])
        ) {
            $this->fields["helpdesk_item_type"] = [];
        }

       // decode array
        if (
            isset($this->fields["managed_domainrecordtypes"])
            && !is_array($this->fields["managed_domainrecordtypes"])
        ) {
            $this->fields["managed_domainrecordtypes"] = importArrayFromDB($this->fields["managed_domainrecordtypes"]);
        }

       // Empty/NULL case
        if (
            !isset($this->fields["managed_domainrecordtypes"])
            || !is_array($this->fields["managed_domainrecordtypes"])
        ) {
            $this->fields["managed_domainrecordtypes"] = [];
        }

       // Decode status array
        $fields_to_decode = ['ticket_status', 'problem_status', 'change_status'];
        foreach ($fields_to_decode as $val) {
            if (isset($this->fields[$val]) && !is_array($this->fields[$val])) {
                $this->fields[$val] = importArrayFromDB($this->fields[$val]);
               // Need to be an array not a null value
                if (is_null($this->fields[$val])) {
                    $this->fields[$val] = [];
                }
            }
        }
    }


    /**
     * Get SQL restrict criteria to determine profiles with less rights than the active one
     *
     * @since 9.3.1
     *
     * @return array
     **/
    public static function getUnderActiveProfileRestrictCriteria()
    {

       // Not logged -> no profile to see
        if (!isset($_SESSION['glpiactiveprofile'])) {
            return [0];
        }

       // Profile right : may modify profile so can attach all profile
        if (Profile::canCreate()) {
            return [1];
        }

        $criteria = ['glpi_profiles.interface' => Session::getCurrentInterface()];

       // First, get all possible rights
        $right_subqueries = [];
        foreach (ProfileRight::getAllPossibleRights() as $key => $default) {
            $val = isset($_SESSION['glpiactiveprofile'][$key]) ? $_SESSION['glpiactiveprofile'][$key] : 0;

            if (
                !is_array($val) // Do not include entities field added by login
                && (Session::getCurrentInterface() == 'central'
                 || in_array($key, self::$helpdesk_rights))
            ) {
                $right_subqueries[] = [
                    'glpi_profilerights.name'     => $key,
                    'RAW'                         => [
                        '(' . DBmysql::quoteName('glpi_profilerights.rights') . ' | ' . DBmysql::quoteValue($val) . ')' => $val
                    ]
                ];
            }
        }

        $sub_query = new QuerySubQuery([
            'FROM'   => 'glpi_profilerights',
            'COUNT'  => 'cpt',
            'WHERE'  => [
                'glpi_profilerights.profiles_id' => new \QueryExpression(\DBmysql::quoteName('glpi_profiles.id')),
                'OR'                             => $right_subqueries
            ]
        ]);
        $criteria[] = new \QueryExpression(count($right_subqueries) . " = " . $sub_query->getQuery());

        if (Session::getCurrentInterface() == 'central') {
            return [
                'OR'  => [
                    'glpi_profiles.interface' => 'helpdesk',
                    $criteria
                ]
            ];
        }

        return $criteria;
    }


    /**
     * Is the current user have more right than all profiles in parameters
     *
     * @param $IDs array of profile ID to test
     *
     * @return boolean true if have more right
     **/
    public static function currentUserHaveMoreRightThan($IDs = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (Session::isCron()) {
            return true;
        }
        if (count($IDs) == 0) {
           // Check all profiles (means more right than all possible profiles)
            return (countElementsInTable('glpi_profiles')
                     == countElementsInTable(
                         'glpi_profiles',
                         self::getUnderActiveProfileRestrictCriteria()
                     ));
        }
        $under_profiles = [];

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => self::getUnderActiveProfileRestrictCriteria()
        ]);

        foreach ($iterator as $data) {
            $under_profiles[$data['id']] = $data['id'];
        }

        foreach ($IDs as $ID) {
            if (!isset($under_profiles[$ID])) {
                return false;
            }
        }
        return true;
    }


    public function showLegend()
    {

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'><td width='70' style='text-decoration:underline' class='b'>";
        echo __('Caption') . "</td>";
        echo "<td class='tab_bg_4' width='15' style='border:1px solid black'></td>";
        echo "<td class='b'>" . __('Global right') . "</td></tr>\n";
        echo "<tr class='tab_bg_2'><td></td>";
        echo "<td class='tab_bg_2' width='15' style='border:1px solid black'></td>";
        echo "<td class='b'>" . __('Entity right') . "</td></tr>";
        echo "</table></div>\n";
    }


    public function post_getEmpty()
    {
        $this->fields["interface"] = "helpdesk";
        $this->fields["name"]      = __('Without name');
        ProfileRight::cleanAllPossibleRights();
        $this->fields = array_merge($this->fields, ProfileRight::getAllPossibleRights());
    }


    public function post_getFromDB()
    {
        $this->fields = array_merge($this->fields, ProfileRight::getProfileRights($this->getID()));
    }

    /**
     * Print the profile form headers
     *
     * @param $ID        integer : Id of the item to print
     * @param $options   array of possible options
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {

        $onfocus = "";
        $new     = false;
        $rowspan = 4;
        if ($ID > 0) {
            $rowspan++;
            $this->check($ID, READ);
        } else {
           // Create item
            $this->check(-1, CREATE);
            $onfocus = "onfocus=\"if (this.value=='" . $this->fields["name"] . "') this.value='';\"";
            $new     = true;
        }

        $rand = mt_rand();

        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
        echo "<td><input type='text' name='name' class='form-control' value=\"" . $this->fields["name"] . "\" $onfocus></td>";
        echo "<td rowspan='$rowspan' class='middle right'>" . __('Comments') . "</td>";
        echo "<td class='center middle' rowspan='$rowspan'>";
        echo "<textarea class='form-control' rows='4' name='comment' class='form-control'>" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Default profile') . "</td><td>";
        Html::showCheckbox(['name'    => 'is_default',
            'checked' => $this->fields['is_default']
        ]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __("Profile's interface") . "</td>";
        echo "<td>";
        Dropdown::showFromArray(
            'interface',
            self::getInterfaces(),
            [
                'value' => $this->fields["interface"],
                'readonly' => $this->isLastSuperAdminProfile() && $this->fields['interface'] == 'central'
            ]
        );
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Update own password') . "</td><td>";
        Html::showCheckbox(['name'    => '_password_update',
            'checked' => $this->fields['password_update']
        ]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Ticket creation form on login') . "</td><td>";
        Html::showCheckbox(['name'    => 'create_ticket_on_login',
            'checked' => $this->fields['create_ticket_on_login']
        ]);
        echo "</td></tr>\n";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Get all rights to display for a specific form and interface.
     *
     * This is only used for GLPI core rights and not rights added by plugins.
     *
     * @param string $form The tab/form name
     * @phpstan-param non-empty-string $form
     * @param string $interface The interface name
     * @phpstan-param 'all'|'central'|'helpdesk' $interface
     * @return array
     * @phpstan-type RightDefinition = array{rights: array{}, label: string, field: string, scope: string}
     * @phpstan-return $interface == 'all' ? array<string, array<string, array<string, RightDefinition[]>>> : ($form == 'all' ? array<string, array<string, RightDefinition[]>> : ($group == 'all' ? array<string, RightDefinition[]> : RightDefinition[]))
     * @internal BC not guaranteed. Only public so it can be used in tests to ensure search options are made for all rights.
     */
    public static function getRightsForForm(string $interface = 'all', string $form = 'all', string $group = 'all'): array
    {
        /**
         * Helper function to streamline rights definition
         * @param class-string<CommonDBTM>|null $itemtype
         * @param string $interface
         * @param array $options
         * @return array
         */
        $fn_get_rights = static function (?string $itemtype, string $interface, array $options = []) {
            $options = array_replace([
                'field' => null,
                'label' => null,
                'rights' => null,
                'scope' => 'entity'
            ], $options);

            return [
                'rights' => $options['rights'] ?? Profile::getRightsFor($itemtype, $interface),
                'label'  => $options['label'] ?? $itemtype::getTypeName(Session::getPluralNumber()),
                'field'  => $options['field'] ?? $itemtype::$rightname,
                'scope' => $options['scope']
            ];
        };

        static $all_rights = null;

        if ($all_rights === null) {
            $dropdown_rights = (new Profile())->getRights();
            unset($dropdown_rights[DELETE]);
            unset($dropdown_rights[UNLOCK]);

            $all_rights = [
                'central' => [
                    'tracking' => [
                        'itilobjects' => [
                            $fn_get_rights(TicketTemplate::class, 'central', [
                                'label' => _n('Template', 'Templates', Session::getPluralNumber())
                            ]),
                            $fn_get_rights(PendingReason::class, 'central'),
                        ],
                        'tickets' => [
                            $fn_get_rights(Ticket::class, 'central'),
                            $fn_get_rights(TicketCost::class, 'central'),
                            $fn_get_rights(TicketRecurrent::class, 'central'),
                        ],
                        'followups_tasks' => [
                            $fn_get_rights(ITILFollowup::class, 'central'),
                            $fn_get_rights(TicketTask::class, 'central'),
                        ],
                        'validations' => [
                            $fn_get_rights(TicketValidation::class, 'central'),
                        ],
                        'visibility' => [
                            $fn_get_rights(Stat::class, 'central'),
                            $fn_get_rights(Planning::class, 'central'),
                        ],
                        'planning' => [
                            $fn_get_rights(PlanningExternalEvent::class, 'central'),
                        ],
                        'problems' => [
                            $fn_get_rights(Problem::class, 'central'),
                        ],
                        'changes' => [
                            $fn_get_rights(Change::class, 'central'),
                            $fn_get_rights(ChangeValidation::class, 'central'),
                            $fn_get_rights(RecurrentChange::class, 'central'),
                        ],
                    ],
                    'tools' => [
                        'general' => [
                            $fn_get_rights(Reminder::class, 'central', [
                                'label' => _n('Public reminder', 'Public reminders', Session::getPluralNumber())
                            ]),
                            $fn_get_rights(RSSFeed::class, 'central', [
                                'label' => _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber())
                            ]),
                            $fn_get_rights(SavedSearch::class, 'central', [
                                'label' => _n('Public saved search', 'Public saved searches', Session::getPluralNumber())
                            ]),
                            $fn_get_rights(Report::class, 'central'),
                            $fn_get_rights(KnowbaseItem::class, 'central'),
                            $fn_get_rights(ReservationItem::class, 'central'),
                        ],
                        'projects' => [
                            $fn_get_rights(Project::class, 'central'),
                            $fn_get_rights(ProjectTask::class, 'central'),
                        ]
                    ],
                    'assets' => [
                        'general' => [
                            $fn_get_rights(Computer::class, 'central'),
                            $fn_get_rights(Monitor::class, 'central'),
                            $fn_get_rights(Software::class, 'central'),
                            $fn_get_rights(NetworkEquipment::class, 'central'),
                            $fn_get_rights(Printer::class, 'central'),
                            $fn_get_rights(Cartridge::class, 'central'),
                            $fn_get_rights(Consumable::class, 'central'),
                            $fn_get_rights(Phone::class, 'central'),
                            $fn_get_rights(Peripheral::class, 'central'),
                            $fn_get_rights(NetworkName::class, 'central', [
                                'label' => __('Internet')
                            ]),
                            $fn_get_rights(DeviceSimcard::class, 'central', [
                                'label' => __('Simcard PIN/PUK'),
                                'field' => 'devicesimcard_pinpuk'
                            ]),
                        ],
                    ],
                    'management' => [
                        'general' => [
                            $fn_get_rights(SoftwareLicense::class, 'central'),
                            $fn_get_rights(Contact::class, 'central', [
                                'label' => _n('Contact', 'Contacts', Session::getPluralNumber()) . " / " .
                                    _n('Supplier', 'Suppliers', Session::getPluralNumber())
                            ]),
                            $fn_get_rights(Document::class, 'central'),
                            $fn_get_rights(Contract::class, 'central'),
                            $fn_get_rights(Infocom::class, 'central'),
                            $fn_get_rights(Budget::class, 'central'),
                            $fn_get_rights(Line::class, 'central'),
                            $fn_get_rights(Certificate::class, 'central'),
                            $fn_get_rights(Datacenter::class, 'central'),
                            $fn_get_rights(Cluster::class, 'central'),
                            $fn_get_rights(Domain::class, 'central'),
                            $fn_get_rights(Appliance::class, 'central'),
                            $fn_get_rights(DatabaseInstance::class, 'central'),
                            $fn_get_rights(Cable::class, 'central'),
                        ],
                    ],
                    'admin' => [
                        'general' => [
                            $fn_get_rights(User::class, 'central'),
                            $fn_get_rights(Entity::class, 'central', ['scope' => 'global']),
                            $fn_get_rights(Group::class, 'central', ['scope' => 'global']),
                            $fn_get_rights(__CLASS__, 'central', ['scope' => 'global']),
                            $fn_get_rights(QueuedNotification::class, 'central', ['scope' => 'global']),
                            $fn_get_rights(Log::class, 'central', ['scope' => 'global']),
                            $fn_get_rights(Event::class, 'central', [
                                'scope' => 'global',
                                'label' => __('System logs')
                            ]),
                        ],
                        'inventory' => [
                            $fn_get_rights(\Glpi\Inventory\Conf::class, 'central', [
                                'label' => __('Inventory'),
                                'field' => 'inventory',
                                'scope' => 'global'
                            ]),
                            $fn_get_rights(Lockedfield::class, 'central', [
                                'rights' => [
                                    CREATE => __('Create'), // For READ / CREATE
                                    UPDATE => __('Update'), //for CREATE / PURGE global lock
                                ],
                                'scope' => 'global'
                            ]),
                            $fn_get_rights(SNMPCredential::class, 'central', ['scope' => 'global']),
                            $fn_get_rights(RefusedEquipment::class, 'central', [
                                'rights' => [
                                    READ  => __('Read'),
                                    UPDATE  => __('Update'),
                                    PURGE   => [
                                        'short' => __('Purge'),
                                        'long'  => _x('button', 'Delete permanently')
                                    ]
                                ],
                                'scope' => 'global'
                            ]),
                            $fn_get_rights(Unmanaged::class, 'central', [
                                'rights' => [
                                    READ  => __('Read'),
                                    UPDATE  => __('Update'),
                                    DELETE => [
                                        'short' => __('Delete'),
                                        'long'  => _x('button', 'Put in trashbin')
                                    ],
                                    PURGE   => [
                                        'short' => __('Purge'),
                                        'long'  => _x('button', 'Delete permanently')
                                    ]
                                ],
                                'scope' => 'global'
                            ]),
                            $fn_get_rights(Agent::class, 'central', [
                                'rights' => [
                                    READ  => __('Read'),
                                    UPDATE  => __('Update'),
                                    PURGE   => [
                                        'short' => __('Purge'),
                                        'long'  => _x('button', 'Delete permanently')
                                    ]
                                ],
                                'scope' => 'global'
                            ]),
                        ],
                        'rules' => [
                            $fn_get_rights(RuleRight::class, 'central', [
                                'label'     => __('Authorizations assignment rules'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleImportAsset::class, 'central', [
                                'label'     => __('Rules for assigning a computer to an entity'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleLocation::class, 'central', [
                                'label'     => __('Rules for assigning a computer to a location'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleMailCollector::class, 'central', [
                                'label'     => __('Rules for assigning a ticket created through a mails receiver'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleSoftwareCategory::class, 'central', [
                                'label'     => __('Rules for assigning a category to a software'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleTicket::class, 'central', [
                                'label'     => __('Business rules for tickets (entity)'),
                            ]),
                            $fn_get_rights(RuleAsset::class, 'central', [
                                'label'     => __('Business rules for assets'),
                            ]),
                            $fn_get_rights(Transfer::class, 'central', [
                                'label'     => __('Transfer'),
                                'scope'     => 'global'
                            ]),
                        ],
                        'dictionaries' => [
                            $fn_get_rights(RuleDictionnaryDropdown::class, 'central', [
                                'label'     => __('Dropdowns dictionary'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleDictionnarySoftware::class, 'central', [
                                'label'     => __('Software dictionary'),
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(RuleDictionnaryPrinter::class, 'central', [
                                'label'     => __('Printers dictionary'),
                                'scope'     => 'global'
                            ]),
                        ]
                    ],
                    'setup' => [
                        'general' => [
                            $fn_get_rights(Config::class, 'central', ['scope' => 'entity']),
                            $fn_get_rights(null, 'central', [
                                'rights'  => [
                                    READ    => __('Read'),
                                    UPDATE  => __('Update')
                                ],
                                'label'  => __('Personalization'),
                                'field'  => 'personalization',
                                'scope'     => 'entity'
                            ]),
                            $fn_get_rights(\Glpi\Dashboard\Grid::class, 'central', [
                                'label'     => __('All dashboards'),
                                'field'     => 'dashboard',
                                'scope'     => 'entity'
                            ]),
                            $fn_get_rights(DisplayPreference::class, 'central', ['scope' => 'entity']),
                            $fn_get_rights(Item_Devices::class, 'central', [
                                'label'     => _n('Component', 'Components', Session::getPluralNumber()),
                                'field'     => 'device',
                            ]),
                            $fn_get_rights(null, 'central', [
                                'rights'    => $dropdown_rights,
                                'label'     => _n('Global dropdown', 'Global dropdowns', Session::getPluralNumber()),
                                'field'     => 'dropdown',
                                'scope'     => 'global'
                            ]),
                            $fn_get_rights(Location::class, 'central'),
                            $fn_get_rights(ITILCategory::class, 'central'),
                            $fn_get_rights(KnowbaseItemCategory::class, 'central'),
                            $fn_get_rights(TaskCategory::class, 'central'),
                            $fn_get_rights(State::class, 'central'),
                            $fn_get_rights(ITILFollowupTemplate::class, 'central'),
                            $fn_get_rights(SolutionTemplate::class, 'central'),
                            $fn_get_rights(Calendar::class, 'central'),
                            $fn_get_rights(DocumentType::class, 'central'),
                            $fn_get_rights(Link::class, 'central'),
                            $fn_get_rights(Notification::class, 'central'),
                            $fn_get_rights(SLM::class, 'central', ['label' => __('SLM')]),
                            $fn_get_rights(LineOperator::class, 'central'),
                        ],
                    ]
                ],
                'helpdesk' => [
                    'tracking' => [
                        'general' => [
                            $fn_get_rights(Ticket::class, 'helpdesk'),
                            $fn_get_rights(ITILFollowup::class, 'helpdesk'),
                            $fn_get_rights(TicketTask::class, 'helpdesk'),
                            $fn_get_rights(TicketValidation::class, 'helpdesk'),
                        ],
                    ],
                    'tools' => [
                        'general' => [
                            $fn_get_rights(KnowbaseItem::class, 'helpdesk'),
                            $fn_get_rights(ReservationItem::class, 'helpdesk'),
                            $fn_get_rights(Reminder::class, 'helpdesk'),
                            $fn_get_rights(RSSFeed::class, 'helpdesk'),
                        ],
                    ],
                    'setup' => [
                        'general' => [
                            $fn_get_rights(null, 'helpdesk', [
                                'rights'  => [
                                    READ    => __('Read'),
                                    UPDATE  => __('Update')
                                ],
                                'label'  => __('Personalization'),
                                'field'  => 'personalization',
                            ]),
                        ],
                    ]
                ]
            ];
        }

        $result = $all_rights;
        if ($interface !== 'all') {
            $result = $all_rights[$interface] ?? [];
        }
        if ($form !== 'all') {
            $result = $all_rights[$interface][$form] ?? [];
        }
        if ($group !== 'all') {
            $result = $all_rights[$interface][$form][$group] ?? [];
        }
        return $result;
    }

    /**
     * Print the helpdesk right form for the current profile
     *
     * @since 0.85
     **/
    public function showFormTrackingHelpdesk()
    {
        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $matrix_options = ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2'
        ];

        $matrix_options['title'] = __('Assistance');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('helpdesk', 'tracking', 'general'), $matrix_options);

        echo "<div class='mt-4 mx-n2'>";
        echo "<table class='table table-hover card-table'>";
        echo "<thead>";
        echo "<tr class='border-top'><th colspan='2'><h4>" . __('Association') . "</h4></th></tr>";
        echo "</thead>";

        echo "<tr'>";
        echo "<td>" . __('See hardware of my groups') . "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'    => '_show_group_hardware',
            'checked' => $this->fields['show_group_hardware']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Link with items for the creation of tickets') . "</td>";
        echo "<td>";
        self::getLinearRightChoice(
            self::getHelpdeskHardwareTypes(true),
            ['field' => 'helpdesk_hardware',
                'value' => $this->fields['helpdesk_hardware']
            ]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Associable items to tickets, changes and problems') . "</td>";
        echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
        self::dropdownHelpdeskItemtypes(['values' => $this->fields["helpdesk_item_type"]]);

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Default ticket template') . "</td>";
        echo "<td>";
       // Only root entity ones and recursive
        $options = ['value'     => $this->fields["tickettemplates_id"],
            'entity'    => 0
        ];
        if (Session::isMultiEntitiesMode()) {
            $options['condition'] = ['is_recursive' => 1];
        }
       // Only add profile if on root entity
        if (!isset($_SESSION['glpiactiveentities'][0])) {
            $options['addicon'] = false;
        }
        TicketTemplate::dropdown($options);
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Default change template') . "</td>";
        echo "<td>";
       // Only root entity ones and recursive
        $options = ['value'     => $this->fields["changetemplates_id"],
            'entity'    => 0
        ];
        if (Session::isMultiEntitiesMode()) {
            $options['condition'] = ['is_recursive' => 1];
        }
       // Only add profile if on root entity
        if (!isset($_SESSION['glpiactiveentities'][0])) {
            $options['addicon'] = false;
        }
        ChangeTemplate::dropdown($options);
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('Default problem template') . "</td>";
        echo "<td>";
       // Only root entity ones and recursive
        $options = ['value'     => $this->fields["problemtemplates_id"],
            'entity'    => 0
        ];
        if (Session::isMultiEntitiesMode()) {
            $options['condition'] = ['is_recursive' => 1];
        }
       // Only add profile if on root entity
        if (!isset($_SESSION['glpiactiveentities'][0])) {
            $options['addicon'] = false;
        }
        ProblemTemplate::dropdown($options);
        echo "</td>";
        echo "</tr>";

        if ($canedit) {
            echo "<tr'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
        } else {
            echo "</table>";
        }
        echo "</div>";
        echo "</div>";
    }


    /**
     * Print the helpdesk right form for the current profile
     *
     * @since 0.85
     **/
    public function showFormToolsHelpdesk()
    {
        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $matrix_options = ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2'
        ];

        $matrix_options['title'] = __('Tools');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('helpdesk', 'tools', 'general'), $matrix_options);

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }



    /**
     * Print the Asset rights form for the current profile
     *
     * @since 0.85
     *
     * @param $openform  boolean open the form (true by default)
     * @param $closeform boolean close the form (true by default)
     *
     **/
    public function showFormAsset($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'assets', 'general'), [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => _n('Asset', 'Assets', Session::getPluralNumber())
        ]);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";
    }


    /**
     * Print the Management rights form for the current profile
     *
     * @since 0.85 (before showFormInventory)
     *
     * @param $openform  boolean open the form (true by default)
     * @param $closeform boolean close the form (true by default)
     **/
    public function showFormManagement($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";

        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $matrix_options = ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2'
        ];

        $matrix_options['title'] = __('Management');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'management', 'general'), $matrix_options);

        echo "<div class='tab_cadre_fixehov mx-n2'>";
        echo "<input type='hidden' name='_managed_domainrecordtypes' value='1'>";
        $rand = rand();
        echo "<label for='dropdown_managed_domainrecordtypes$rand'>" . __('Manageable domain records') . "</label>";
        $values = ['-1' => __('All')];
        $values += $this->getDomainRecordTypes();
        Dropdown::showFromArray(
            'managed_domainrecordtypes',
            $values,
            [
                'display'   => true,
                'multiple'  => true,
                'size'      => 3,
                'rand'      => $rand,
                'values'    => $this->fields['managed_domainrecordtypes']
            ]
        );
        echo "</div>";

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Print the Tools rights form for the current profile
     *
     * @since 0.85
     *
     * @param $openform  boolean open the form (true by default)
     * @param $closeform boolean close the form (true by default)
     **/
    public function showFormTools($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";

        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $matrix_options = ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2'
        ];

        $matrix_options['title'] = __('Tools');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tools', 'general'), $matrix_options);

        $matrix_options['title'] = _n('Project', 'Projects', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tools', 'projects'), $matrix_options);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Print the Tracking right form for the current profile
     *
     * @param $openform     boolean  open the form (true by default)
     * @param $closeform    boolean  close the form (true by default)
     **/
    public function showFormTracking($openform = true, $closeform = true)
    {
        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        echo "<div class='mt-n2 mx-n2 mb-4'>";
        echo "<table class='table table-hover card-table'>";
       // Assistance / Tracking-helpdesk
        echo "<thead>";
        echo "<tr><th colspan='2'><h4>" . __('ITIL Templates') . "<h4></th></tr>";
        echo "</thead>";

        echo "<tbody>";
        foreach (['Ticket', 'Change', 'Problem'] as $itiltype) {
            $object = new $itiltype();
            echo "<tr>";
            echo "<td>" . sprintf(__('Default %1$s template'), $object->getTypeName()) . "</td><td>";
           // Only root entity ones and recursive
            $options = [
                'value'     => $this->fields[strtolower($itiltype) . "templates_id"],
                'entity'    => 0
            ];
            if (Session::isMultiEntitiesMode()) {
                $options['condition'] = ['is_recursive' => 1];
            }
           // Only add profile if on root entity
            if (!isset($_SESSION['glpiactiveentities'][0])) {
                $options['addicon'] = false;
            }

            $tpl_class = $itiltype . 'Template';
            $tpl_class::dropdown($options);
            echo "</td></tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";

        $matrix_options = ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2'
        ];

        $matrix_options['title'] = _n('ITIL object', 'ITIL objects', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'itilobjects'), $matrix_options);

        $matrix_options['title'] = _n('Ticket', 'Tickets', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'tickets'), $matrix_options);

        $matrix_options['title'] = _n('Followup', 'Followups', Session::getPluralNumber()) . " / " . _n('Task', 'Tasks', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'followups_tasks'), $matrix_options);

        $matrix_options['title'] = _n('Validation', 'Validations', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'validations'), $matrix_options);

        echo "<div class='mx-n2 my-4'>";
        echo "<table class='table table-hover card-table'>";

        echo "<thead>";
        echo "<tr class='border-top'><th colspan='2'><h4>" . __('Association') . "<h4></th></tr>";
        echo "</thead>";

        echo "<tr>";
        echo "<td>" . __('See hardware of my groups') . "</td>";
        echo "<td>";
        Html::showCheckbox(['name'    => '_show_group_hardware',
            'checked' => $this->fields['show_group_hardware']
        ]);
        echo "</td></tr>";

        echo "<tr>";
        echo "<td>" . __('Link with items for the creation of tickets') . "</td>";
        echo "<td>";
        self::getLinearRightChoice(
            self::getHelpdeskHardwareTypes(true),
            ['field' => 'helpdesk_hardware',
                'value' => $this->fields['helpdesk_hardware']
            ]
        );
        echo "</td></tr>";

        echo "<tr>";
        echo "<td>" . __('Associable items to tickets, changes and problems') . "</td>";
        echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
        self::dropdownHelpdeskItemtypes(['values' => $this->fields["helpdesk_item_type"]]);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";

        $matrix_options['title'] = __('Visibility');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'visibility'), $matrix_options);

        $matrix_options['title'] = __('Planning');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'planning'), $matrix_options);

        $matrix_options['title'] = Problem::getTypeName(Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'problems'), $matrix_options);

        $matrix_options['title'] = _n('Change', 'Changes', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'tracking', 'changes'), $matrix_options);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Display the matrix of the elements lifecycle of the elements
     *
     * @since 0.85
     *
     * @param $title          the kind of lifecycle
     * @param $html_field     field that is sent to _POST
     * @param $db_field       field inside the DB (to get current state)
     * @param $statuses       all available statuses for the given cycle (obj::getAllStatusArray())
     * @param $canedit        can we edit the elements ?
     *
     * @return void
     **/
    public function displayLifeCycleMatrix($title, $html_field, $db_field, $statuses, $canedit)
    {

        $columns  = [];
        $rows     = [];

        foreach ($statuses as $index_1 => $status_1) {
            $columns[$index_1] = $status_1;
            $row               = ['label'      => $status_1,
                'columns'    => []
            ];

            foreach ($statuses as $index_2 => $status_2) {
                $content = ['checked' => true];
                if (isset($this->fields[$db_field][$index_1][$index_2])) {
                    $content['checked'] = $this->fields[$db_field][$index_1][$index_2];
                }
                if (($index_1 == $index_2) || (!$canedit)) {
                    $content['readonly'] = true;
                }
                $row['columns'][$index_2] = $content;
            }
            $rows[$html_field . "[$index_1]"] = $row;
        }
        Html::showCheckboxMatrix(
            $columns,
            $rows,
            ['title'         => $title,
                'row_check_all' => true,
                'col_check_all' => true,
                'first_cell'    => '<b>' . __("From \ To") . '</b>'
            ]
        );
    }


    /**
     * Print the Life Cycles form for the current profile
     *
     * @param $openform   boolean  open the form (true by default)
     * @param $closeform  boolean  close the form (true by default)
     **/
    public function showFormLifeCycle($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";

        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $this->displayLifeCycleMatrix(
            __('Life cycle of tickets'),
            '_cycle_ticket',
            'ticket_status',
            Ticket::getAllStatusArray(),
            $canedit
        );

        $this->displayLifeCycleMatrix(
            __('Life cycle of problems'),
            '_cycle_problem',
            'problem_status',
            Problem::getAllStatusArray(),
            $canedit
        );

        $this->displayLifeCycleMatrix(
            __('Life cycle of changes'),
            '_cycle_change',
            'change_status',
            Change::getAllStatusArray(),
            $canedit
        );

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Display the matrix of the elements lifecycle of the elements
     *
     * @since 0.85
     *
     * @param $title          the kind of lifecycle
     * @param $html_field     field that is sent to _POST
     * @param $db_field       field inside the DB (to get current state)
     * @param $canedit        can we edit the elements ?
     *
     * @return void
     **/
    public function displayLifeCycleMatrixTicketHelpdesk($title, $html_field, $db_field, $canedit)
    {

        $columns     = [];
        $rows        = [];
        $statuses    = [];
        $allstatuses = Ticket::getAllStatusArray();
        foreach ([Ticket::INCOMING, Ticket::SOLVED, Ticket::CLOSED] as $val) {
            $statuses[$val] = $allstatuses[$val];
        }
        $alwaysok     = [Ticket::INCOMING => [],
            Ticket::SOLVED   => [Ticket::INCOMING],
            Ticket::CLOSED   => []
        ];

        $allowactions = [Ticket::INCOMING => [],
            Ticket::SOLVED   => [Ticket::CLOSED],
            Ticket::CLOSED   => [Ticket::INCOMING]
        ];

        foreach ($statuses as $index_1 => $status_1) {
            $columns[$index_1] = $status_1;
            $row               = ['label'      => $status_1,
                'columns'    => []
            ];

            foreach ($statuses as $index_2 => $status_2) {
                $content = ['checked' => true];
                if (isset($this->fields[$db_field][$index_1][$index_2])) {
                    $content['checked'] = $this->fields[$db_field][$index_1][$index_2];
                }

                if (in_array($index_2, $alwaysok[$index_1])) {
                    $content['checked'] = true;
                }

                if (
                    ($index_1 == $index_2)
                    || (!$canedit)
                    || !in_array($index_2, $allowactions[$index_1])
                ) {
                    $content['readonly'] = true;
                }
                $row['columns'][$index_2] = $content;
            }
            $rows[$html_field . "[$index_1]"] = $row;
        }
        Html::showCheckboxMatrix(
            $columns,
            $rows,
            ['title'         => $title,
                'first_cell'    => '<b>' . __("From \ To") . '</b>'
            ]
        );
    }


    /**
     * Print the Life Cycles form for the current profile
     *
     *  @since 0.85
     *
     * @param $openform   boolean  open the form (true by default)
     * @param $closeform  boolean  close the form (true by default)
     **/
    public function showFormLifeCycleHelpdesk($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";

        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $this->displayLifeCycleMatrixTicketHelpdesk(
            __('Life cycle of tickets'),
            '_cycle_ticket',
            'ticket_status',
            $canedit
        );

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Print the central form for a profile
     *
     * @param $openform     boolean  open the form (true by default)
     * @param $closeform    boolean  close the form (true by default)
     **/
    public function showFormAdmin($openform = true, $closeform = true)
    {
        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";

        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $matrix_options = [
            'canedit'       => $canedit,
        ];

        $matrix_options['title'] = __('Administration');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'admin', 'general'), $matrix_options);

        $matrix_options['title'] = __('Inventory');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'admin', 'inventory'), $matrix_options);

        $matrix_options['title'] = _n('Rule', 'Rules', Session::getPluralNumber());
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'admin', 'rules'), $matrix_options);

        $matrix_options['title'] = __('Dropdowns dictionary');
        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'admin', 'dictionaries'), $matrix_options);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
    }

    /**
     * Print the central form for a profile
     *
     * @param $openform     boolean  open the form (true by default)
     * @param $closeform    boolean  close the form (true by default)
     **/
    public function showFormSetup($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $this->displayRightsChoiceMatrix(self::getRightsForForm('central', 'setup', 'general'), [
            'canedit'       => $canedit,
            'title'         => __('Setup')
        ]);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
    }


    /**
     * Print the Setup rights form for a helpdesk profile
     *
     * @since 9.4.0
     *
     * @param boolean $openform  open the form (true by default)
     * @param boolean $closeform close the form (true by default)
     *
     * @return void
     *
     **/
    public function showFormSetupHelpdesk($openform = true, $closeform = true)
    {

        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        if (
            ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
            && $openform
        ) {
            echo "<form method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        }

        $this->displayRightsChoiceMatrix(self::getRightsForForm('helpdesk', 'setup', 'general'), [
            'canedit'       => $canedit,
            'title'         => __('Setup')
        ]);

        if (
            $canedit
            && $closeform
        ) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $this->fields['id'] . "'>";
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";

        $this->showLegend();
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
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
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

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'interface',
            'name'               => __("Profile's interface"),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals']
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_default',
            'name'               => __('Default profile'),
            'datatype'           => 'bool',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '118',
            'table'              => $this->getTable(),
            'field'              => 'create_ticket_on_login',
            'name'               => __('Ticket creation form on login'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab[] = [
            'id'                 => 'inventory',
            'name'               => _n('Asset', 'Assets', Session::getPluralNumber())
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Computer', 'Computers', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Computer',
            'rightname'          => 'computer',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'computer']
            ]
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Monitor', 'Monitors', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Monitor',
            'rightname'          => 'monitor',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'monitor']
            ]
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Software', 'Software', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Software',
            'rightname'          => 'software',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'software']
            ]
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Network', 'Networks', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Network',
            'rightname'          => 'networking',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'networking']
            ]
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Printer', 'Printers', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Printer',
            'rightname'          => 'printer',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'printer']
            ]
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Peripheral::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Peripheral',
            'rightname'          => 'peripheral',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'peripheral']
            ]
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Cartridge', 'Cartridges', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Cartridge',
            'rightname'          => 'cartridge',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'cartridge']
            ]
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Consumable', 'Consumables', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Consumable',
            'rightname'          => 'consumable',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'consumable']
            ]
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Phone::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Phone',
            'rightname'          => 'phone',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'phone']
            ]
        ];

        $tab[] = [
            'id'                 => '129',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Internet'),
            'datatype'           => 'right',
            'rightclass'         => 'NetworkName',
            'rightname'          => 'internet',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'internet']
            ]
        ];

        $tab[] = [
            'id'                 => '130',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Simcard PIN/PUK'),
            'datatype'           => 'right',
            'rightclass'         => 'Item_DeviceSimcard',
            'rightname'          => 'devicesimcard_pinpuk',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'devicesimcard_pinpuk']
            ]
        ];

        $tab[] = [
            'id'                 => 'management',
            'name'               => __('Management')
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Contact::getTypeName(1) . " / " . Supplier::getTypeName(1),
            'datatype'           => 'right',
            'rightclass'         => 'Contact',
            'rightname'          => 'contact_entreprise',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'contact_enterprise']
            ]
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Document::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Document',
            'rightname'          => 'document',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'document']
            ]
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Contract', 'Contracts', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Contract',
            'rightname'          => 'contract',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'contract']
            ]
        ];

        $tab[] = [
            'id'                 => '33',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Financial and administratives information'),
            'datatype'           => 'right',
            'rightclass'         => 'Infocom',
            'rightname'          => 'infocom',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'infocom']
            ]
        ];

        $tab[] = [
            'id'                 => '101',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Budget::getTypeName(1),
            'datatype'           => 'right',
            'rightclass'         => 'Budget',
            'rightname'          => 'budget',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'budget']
            ]
        ];

        $tab[] = [
            'id'                 => '142',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => SoftwareLicense::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => SoftwareLicense::class,
            'rightname'          => SoftwareLicense::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => SoftwareLicense::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '143',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Contact', 'Contacts', Session::getPluralNumber()) . " / " .
                _n('Supplier', 'Suppliers', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Contact::class,
            'rightname'          => 'contact_enterprise',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'contact_enterprise']
            ]
        ];

        $tab[] = [
            'id'                 => '144',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Line::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Line::class,
            'rightname'          => Line::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Line::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '145',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Certificate::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Certificate::class,
            'rightname'          => Certificate::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Certificate::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '146',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Datacenter::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Datacenter::class,
            'rightname'          => Datacenter::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Datacenter::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '147',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Cluster::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Cluster::class,
            'rightname'          => Cluster::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Cluster::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '148',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Domain::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Domain::class,
            'rightname'          => Domain::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Domain::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '149',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Appliance::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Appliance::class,
            'rightname'          => Appliance::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Appliance::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '150',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => DatabaseInstance::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => DatabaseInstance::class,
            'rightname'          => DatabaseInstance::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => DatabaseInstance::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '151',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Cable::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Cable::class,
            'rightname'          => Cable::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Cable::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => 'tools',
            'name'               => __('Tools')
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Knowledge base'),
            'datatype'           => 'right',
            'rightclass'         => 'KnowbaseItem',
            'rightname'          => 'knowbase',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'knowbase']
            ]
        ];

        $tab[] = [
            'id'                 => '36',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Reservation', 'Reservations', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'ReservationItem',
            'rightname'          => 'reservation',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'reservation']
            ]
        ];

        $tab[] = [
            'id'                 => '38',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Report', 'Reports', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Report',
            'rightname'          => 'reports',
            'nowrite'            => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'reports']
            ]
        ];

        $tab[] = [
            'id'                 => '140',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Project::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Project::class,
            'rightname'          => Project::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Project::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '141',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => ProjectTask::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => ProjectTask::class,
            'rightname'          => ProjectTask::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => ProjectTask::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => 'config',
            'name'               => __('Setup')
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'DropdownTranslation',
            'rightname'          => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'dropdown']
            ]
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Component', 'Components', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Item_Devices',
            'rightname'          => 'device',
            'noread'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'device']
            ]
        ];

        $tab[] = [
            'id'                 => '106',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Notification', 'Notifications', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Notification',
            'rightname'          => 'notification',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'notification']
            ]
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => DocumentType::getTypeName(1),
            'datatype'           => 'right',
            'rightclass'         => 'DocumentType',
            'rightname'          => 'typedoc',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'typedoc']
            ]
        ];

        $tab[] = [
            'id'                 => '46',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('External link', 'External links', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Link',
            'rightname'          => 'link',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'link']
            ]
        ];

        $tab[] = [
            'id'                 => '47',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('General setup'),
            'datatype'           => 'right',
            'rightclass'         => 'Config',
            'rightname'          => 'config',
            'noread'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'config']
            ]
        ];

        $tab[] = [
            'id'                 => '109',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Personalization'),
            'datatype'           => 'right',
            'rightclass'         => 'Config',
            'rightname'          => 'personalization',
            'noread'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'personalization']
            ]
        ];

        $tab[] = [
            'id'                 => '52',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Search result user display'),
            'datatype'           => 'right',
            'rightclass'         => 'DisplayPreference',
            'rightname'          => 'search_config',
            'noread'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'search_config']
            ]
        ];

        $tab[] = [
            'id'                 => '107',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Calendar', 'Calendars', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Calendar',
            'rightname'          => 'calendar',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'calendar']
            ]
        ];

        $tab[] = [
            'id'                 => '162',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('All dashboards'),
            'datatype'           => 'right',
            'rightclass'         => Glpi\Dashboard\Grid::class,
            'rightname'          => 'dashboard',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'dashboard']
            ]
        ];

        $tab[] = [
            'id'                 => '163',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Location::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Location::class,
            'rightname'          => Location::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Location::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '164',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => ITILCategory::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => ITILCategory::class,
            'rightname'          => ITILCategory::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => ITILCategory::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '165',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => KnowbaseItemCategory::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => KnowbaseItemCategory::class,
            'rightname'          => KnowbaseItemCategory::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => KnowbaseItemCategory::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '166',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => TaskCategory::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => TaskCategory::class,
            'rightname'          => TaskCategory::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => TaskCategory::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '167',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => State::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => State::class,
            'rightname'          => State::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => State::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '168',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => ITILFollowupTemplate::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => ITILFollowupTemplate::class,
            'rightname'          => ITILFollowupTemplate::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => ITILFollowupTemplate::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '169',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => SolutionTemplate::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => SolutionTemplate::class,
            'rightname'          => SolutionTemplate::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => SolutionTemplate::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '170',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('SLM'),
            'datatype'           => 'right',
            'rightclass'         => SLM::class,
            'rightname'          => 'slm',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'slm']
            ]
        ];

        $tab[] = [
            'id'                 => '171',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => LineOperator::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => LineOperator::class,
            'rightname'          => LineOperator::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => LineOperator::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => 'admin',
            'name'               => __('Administration')
        ];

        $tab[] = [
            'id'                 => '48',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Business rules for tickets'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleTicket',
            'rightname'          => 'rule_ticket',
            'nowrite'            => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_ticket']
            ]
        ];

        $tab[] = [
            'id'                 => '105',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Rules for assigning a ticket created through a mails receiver'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleMailCollector',
            'rightname'          => 'rule_mailcollector',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_mailcollector']
            ]
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Rules for assigning a computer to an entity'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleImportAsset',
            'rightname'          => 'rule_import',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_import']
            ]
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Authorizations assignment rules'),
            'datatype'           => 'right',
            'rightclass'         => 'Rule',
            'rightname'          => 'rule_ldap',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_ldap']
            ]
        ];

        $tab[] = [
            'id'                 => '51',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Rules for assigning a category to a software'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleSoftwareCategory',
            'rightname'          => 'rule_softwarecategories',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_softwarecategories']
            ]
        ];

        $tab[] = [
            'id'                 => '159',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => RuleLocation::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => RuleLocation::class,
            'rightname'          => RuleLocation::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => RuleLocation::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '160',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => RuleAsset::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => RuleAsset::class,
            'rightname'          => RuleAsset::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => RuleAsset::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '90',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Software dictionary'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleDictionnarySoftware',
            'rightname'          => 'rule_dictionnary_software',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_dictionnary_software']
            ]
        ];

        $tab[] = [
            'id'                 => '91',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Dropdowns dictionary'),
            'datatype'           => 'right',
            'rightclass'         => 'RuleDictionnaryDropdown',
            'rightname'          => 'rule_dictionnary_dropdown',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rule_dictionnary_dropdown']
            ]
        ];

        $tab[] = [
            'id'                 => '161',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => RuleDictionnaryPrinter::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => RuleDictionnaryPrinter::class,
            'rightname'          => RuleDictionnaryPrinter::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => RuleDictionnaryPrinter::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '55',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => self::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Profile',
            'rightname'          => 'profile',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'profile']
            ]
        ];

        $tab[] = [
            'id'                 => '56',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => User::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'User',
            'rightname'          => 'user',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'user']
            ]
        ];

        $tab[] = [
            'id'                 => '58',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Group::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Group',
            'rightname'          => 'group',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'group']
            ]
        ];

        $tab[] = [
            'id'                 => '59',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Entity::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Entity',
            'rightname'          => 'entity',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'entity']
            ]
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Transfer'),
            'datatype'           => 'right',
            'rightclass'         => 'Transfer',
            'rightname'          => 'transfer',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'transfer']
            ]
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Log', 'Logs', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Log',
            'rightname'          => Log::$rightname,
            'nowrite'            => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Log::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('System logs'),
            'datatype'           => 'right',
            'rightclass'         => 'Log',
            'rightname'          => Event::$rightname,
            'nowrite'            => true,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Event::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '152',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => QueuedNotification::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => QueuedNotification::class,
            'rightname'          => QueuedNotification::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => QueuedNotification::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '153',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Inventory'),
            'datatype'           => 'right',
            'rightclass'         => \Glpi\Inventory\Conf::class,
            'rightname'          => \Glpi\Inventory\Conf::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => \Glpi\Inventory\Conf::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '154',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Lockedfield::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Lockedfield::class,
            'rightname'          => Lockedfield::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Lockedfield::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '155',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => SNMPCredential::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => SNMPCredential::class,
            'rightname'          => SNMPCredential::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => SNMPCredential::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '156',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => RefusedEquipment::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => RefusedEquipment::class,
            'rightname'          => RefusedEquipment::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => RefusedEquipment::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '157',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Unmanaged::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Unmanaged::class,
            'rightname'          => Unmanaged::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Unmanaged::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '158',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Agent::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => Agent::class,
            'rightname'          => Agent::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => Agent::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => 'ticket',
            'name'               => __('Assistance')
        ];

        $tab[] = [
            'id'                 => '102',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Create a ticket'),
            'datatype'           => 'right',
            'rightclass'         => 'Ticket',
            'rightname'          => 'ticket',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'ticket']
            ]
        ];

        $newtab = [
            'id'                 => '108',
            'table'              => 'glpi_tickettemplates',
            'field'              => 'name',
            'name'               => __('Default ticket template'),
            'datatype'           => 'dropdown',
        ];
        if (Session::isMultiEntitiesMode()) {
            $newtab['condition']     = ['entities_id' => 0, 'is_recursive' => 1];
        } else {
            $newtab['condition']     = ['entities_id' => 0];
        }
        $tab[] = $newtab;

        $tab[] = [
            'id'                 => '103',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Ticket template', 'Ticket templates', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'TicketTemplate',
            'rightname'          => 'tickettemplate',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'tickettemplate']
            ]
        ];

        $tab[] = [
            'id'                 => '79',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Planning'),
            'datatype'           => 'right',
            'rightclass'         => 'Planning',
            'rightname'          => 'planning',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'planning']
            ]
        ];

        $tab[] = [
            'id'                 => '85',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Statistics'),
            'datatype'           => 'right',
            'rightclass'         => 'Stat',
            'rightname'          => 'statistic',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'statistic']
            ]
        ];

        $tab[] = [
            'id'                 => '119',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Ticket cost', 'Ticket costs', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'TicketCost',
            'rightname'          => 'ticketcost',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'ticketcost']
            ]
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'helpdesk_hardware',
            'name'               => __('Link with items for the creation of tickets'),
            'massiveaction'      => false,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '87',
            'table'              => $this->getTable(),
            'field'              => 'helpdesk_item_type',
            'name'               => __('Associable items to tickets, changes and problems'),
            'massiveaction'      => false,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '88',
            'table'              => $this->getTable(),
            'field'              => 'managed_domainrecordtypes',
            'name'               => __('Managed domain records types'),
            'massiveaction'      => false,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '89',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('See hardware of my groups'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'show_group_hardware']
            ]
        ];

        $tab[] = [
            'id'                 => '100',
            'table'              => $this->getTable(),
            'field'              => 'ticket_status',
            'name'               => __('Life cycle of tickets'),
            'nosearch'           => true,
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '110',
            'table'              => $this->getTable(),
            'field'              => 'problem_status',
            'name'               => __('Life cycle of problems'),
            'nosearch'           => true,
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '112',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => Problem::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Problem',
            'rightname'          => 'problem',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'problem']
            ]
        ];

        $tab[] = [
            'id'                 => '111',
            'table'              => $this->getTable(),
            'field'              => 'change_status',
            'name'               => __('Life cycle of changes'),
            'nosearch'           => true,
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '115',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Change', 'Changes', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Change',
            'rightname'          => 'change',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'change']
            ]
        ];

        $tab[] = [
            'id'                 => '131',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => ITILFollowup::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => ITILFollowup::class,
            'rightname'          => ITILFollowup::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => ITILFollowup::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '132',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => TicketTask::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => TicketTask::class,
            'rightname'          => TicketTask::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => TicketTask::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '133',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => TicketValidation::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => TicketValidation::class,
            'rightname'          => TicketValidation::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => TicketValidation::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '134',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Template', 'Templates', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => TicketTemplate::class,
            'rightname'          => 'itiltemplate',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'itiltemplate']
            ]
        ];

        $tab[] = [
            'id'                 => '135',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => PendingReason::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => PendingReason::class,
            'rightname'          => PendingReason::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => PendingReason::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '136',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => TicketRecurrent::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => TicketRecurrent::class,
            'rightname'          => TicketRecurrent::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => TicketRecurrent::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '137',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => PlanningExternalEvent::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => PlanningExternalEvent::class,
            'rightname'          => PlanningExternalEvent::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => PlanningExternalEvent::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '138',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => ChangeValidation::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => ChangeValidation::class,
            'rightname'          => ChangeValidation::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => ChangeValidation::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => '139',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => RecurrentChange::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => RecurrentChange::class,
            'rightname'          => RecurrentChange::$rightname,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => RecurrentChange::$rightname]
            ]
        ];

        $tab[] = [
            'id'                 => 'other',
            'name'               => __('Other')
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => __('Update own password'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'password_update']
            ]
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Public reminder', 'Public reminders', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'Reminder',
            'rightname'          => 'reminder_public',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'reminder_public']
            ]
        ];

        $tab[] = [
            'id'                 => '64',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Public saved search', 'Public saved searches', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'SavedSearch',
            'rightname'          => 'bookmark_public',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'bookmark_public']
            ]
        ];

        $tab[] = [
            'id'                 => '120',
            'table'              => 'glpi_profilerights',
            'field'              => 'rights',
            'name'               => _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber()),
            'datatype'           => 'right',
            'rightclass'         => 'RSSFeed',
            'rightname'          => 'rssfeed_public',
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.name' => 'rssfeed_public']
            ]
        ];

        return $tab;
    }


    /**
     * @since 0.84
     *
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
            case 'interface':
                return self::getInterfaceName($values[$field]);

            case 'helpdesk_hardware':
                return self::getHelpdeskHardwareTypeName($values[$field]);

            case "helpdesk_item_type":
                $types = explode(',', $values[$field]);
                $message = [];
                foreach ($types as $type) {
                    if ($item = getItemForItemtype($type)) {
                        $message[] = $item->getTypeName();
                    }
                }
                return implode(', ', $message);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
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
            case 'interface':
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, self::getInterfaces(), $options);

            case 'helpdesk_hardware':
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, self::getHelpdeskHardwareTypes(), $options);

            case "helpdesk_item_type":
                $options['values'] = explode(',', $values[$field]);
                $options['name']   = $name;
                return self::dropdownHelpdeskItemtypes($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Make a select box for rights
     *
     * @since 0.85
     *
     * @param $values    array    of values to display
     * @param $name      integer  name of the dropdown
     * @param $current   integer  value in database (sum of rights)
     * @param $options   array
     **/
    public static function dropdownRights(array $values, $name, $current, $options = [])
    {

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $value['long'];
            }
        }

        $param['multiple'] = true;
        $param['display'] = true;
        $param['size']    = count($values);
        $tabselect = [];
        foreach ($values as $k => $v) {
            if ((int) $current & $k) {
                $tabselect[] = $k;
            }
        }
        $param['values'] =  $tabselect;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

       // To allow dropdown with no value to be in prepareInputForUpdate
       // without this, you can't have an empty dropdown
       // done to avoid define NORIGHT value
        if ($param['multiple']) {
            echo "<input type='hidden' name='" . $name . "[]' value='0'>";
        }
        return Dropdown::showFromArray($name, $values, $param);
    }



    /**
     * Make a select box for a None Read Write choice
     *
     * @since 0.84
     *
     * @param $name          select name
     * @param $options array of possible options:
     *       - value   : preselected value.
     *       - nonone  : hide none choice ? (default false)
     *       - noread  : hide read choice ? (default false)
     *       - nowrite : hide write choice ? (default false)
     *       - display : display or get string (default true)
     *       - rand    : specific rand (default is generated one)
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function dropdownRight($name, $options = [])
    {

        $param['value']   = '';
        $param['display'] = true;
        $param['nonone']  = false;
        $param['noread']  = false;
        $param['nowrite'] = false;
        $param['rand']    = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $values = [];
        if (!$param['nonone']) {
            $values[0] = __('No access');
        }
        if (!$param['noread']) {
            $values[READ] = __('Read');
        }
        if (!$param['nowrite']) {
            $values[CREATE] = __('Write');
        }
        return Dropdown::showFromArray(
            $name,
            $values,
            ['value'   => $param['value'],
                'rand'    => $param['rand'],
                'display' => $param['display']
            ]
        );
    }


    /**
     * Dropdown profiles which have rights under the active one
     *
     * @param $options array of possible options:
     *    - name : string / name of the select (default is profiles_id)
     *    - value : integer / preselected value (default 0)
     *
     **/
    public static function dropdownUnder($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $p['name']  = 'profiles_id';
        $p['value'] = '';
        $p['rand']  = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => self::getUnderActiveProfileRestrictCriteria(),
            'ORDER'  => 'name'
        ]);

        // New rule -> get the next free ranking
        $profiles = [];
        foreach ($iterator as $data) {
            $profiles[$data['id']] = $data['name'];
        }
        Dropdown::showFromArray(
            $p['name'],
            $profiles,
            ['value'               => $p['value'],
                'rand'                => $p['rand'],
                'display_emptychoice' => true
            ]
        );
    }


    /**
     * Get the default Profile for new user
     *
     * @return integer profiles_id
     **/
    public static function getDefault()
    {
        /** @var \DBmysql $DB */
        global $DB;

        foreach ($DB->request(self::getTable(), ['is_default' => 1]) as $data) {
            return $data['id'];
        }
        return 0;
    }


    /**
     * @since 0.84
     **/
    public static function getInterfaces()
    {

        return ['central'  => __('Standard interface'),
            'helpdesk' => __('Simplified interface')
        ];
    }


    /**
     * @param $value
     **/
    public static function getInterfaceName($value)
    {

        $tab = self::getInterfaces();
        if (isset($tab[$value])) {
            return $tab[$value];
        }
        return NOT_AVAILABLE;
    }


    /**
     * @since 0.84
     *
     * @param $rights   boolean   (false by default)
     **/
    public static function getHelpdeskHardwareTypes($rights = false)
    {

        if ($rights) {
            return [pow(2, Ticket::HELPDESK_MY_HARDWARE)     => __('My devices'),
                pow(2, Ticket::HELPDESK_ALL_HARDWARE)    => __('All items')
            ];
        }

        return [0                                        => Dropdown::EMPTY_VALUE,
            pow(2, Ticket::HELPDESK_MY_HARDWARE)     => __('My devices'),
            pow(2, Ticket::HELPDESK_ALL_HARDWARE)    => __('All items'),
            pow(2, Ticket::HELPDESK_MY_HARDWARE)
                    + pow(2, Ticket::HELPDESK_ALL_HARDWARE) => __('My devices and all items')
        ];
    }


    /**
     * @since 0.84
     *
     * @param $value
     **/
    public static function getHelpdeskHardwareTypeName($value)
    {

        $tab = self::getHelpdeskHardwareTypes();
        if (isset($tab[$value])) {
            return $tab[$value];
        }
        return NOT_AVAILABLE;
    }


    /**
     * @since 0.85
     **/
    public static function getHelpdeskItemtypes()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $values = [];
        foreach ($CFG_GLPI["ticket_types"] as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                $values[$itemtype] = $item->getTypeName();
            } else {
                unset($CFG_GLPI["ticket_types"][$key]);
            }
        }
        return $values;
    }


    /**
     * Get domains records types
     *
     * @return array
     */
    public function getDomainRecordTypes()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => DomainRecordType::getTable(),
        ]);

        $types = [];
        foreach ($iterator as $row) {
            $types[$row['id']] = $row['name'];
        }
        return $types;
    }

    /**
     * Dropdown profiles which have rights under the active one
     *
     * @since 0.84
     *
     * @param $options array of possible options:
     *    - name : string / name of the select (default is profiles_id)
     *    - values : array of values
     **/
    public static function dropdownHelpdeskItemtypes($options)
    {
        $p['name']    = 'helpdesk_item_type';
        $p['values']  = [];
        $p['display'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $values = self::getHelpdeskItemtypes();

        $p['multiple'] = true;
        $p['size']     = 3;
        return Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * Check if user has given right.
     *
     * @since 0.84
     *
     * @param $user_id    integer  id of the user
     * @param $rightname  string   name of right to check
     * @param $rightvalue integer  value of right to check
     * @param $entity_id  integer  id of the entity
     *
     * @return boolean
     */
    public static function haveUserRight($user_id, $rightname, $rightvalue, $entity_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request(
            [
                'COUNT'      => 'cpt',
                'FROM'       => 'glpi_profilerights',
                'INNER JOIN' => [
                    'glpi_profiles' => [
                        'FKEY' => [
                            'glpi_profilerights' => 'profiles_id',
                            'glpi_profiles'      => 'id',
                        ]
                    ],
                    'glpi_profiles_users' => [
                        'FKEY' => [
                            'glpi_profiles_users' => 'profiles_id',
                            'glpi_profiles'       => 'id',
                            [
                                'AND' => ['glpi_profiles_users.users_id' => $user_id],
                            ],
                        ]
                    ],
                ],
                'WHERE'      => [
                    'glpi_profilerights.name'   => $rightname,
                    'glpi_profilerights.rights' => ['&',  $rightvalue],
                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_id, true),
            ]
        );

        if (!$data = $result->current()) {
            return false;
        }

        return $data['cpt'] > 0;
    }


    /**
     * Get rights for an itemtype
     *
     * @since 0.85
     *
     * @param $itemtype   string   itemtype
     * @param $interface  string   (default 'central')
     *
     * @return array
     **/
    public static function getRightsFor($itemtype, $interface = 'central')
    {

        if (class_exists($itemtype)) {
            $item = new $itemtype();
            return $item->getRights($interface);
        }

        return [];
    }


    /**
     * Display rights choice matrix
     *
     * @since 0.85
     *
     * @param $rights array    possible:
     *             'itemtype'   => the type of the item to check (as passed to self::getRightsFor())
     *             'rights'     => when use of self::getRightsFor() is impossible
     *             'label'      => the label for the right
     *             'field'      => the name of the field inside the DB and HTML form (prefixed by '_')
     *             'html_field' => when $html_field != '_'.$field
     * @param $options array   possible:
     *             'title'         the title of the matrix
     *             'canedit'
     *             'default_class' the default CSS class used for the row
     *
     * @return integer random value used to generate the ids
     **/
    public function displayRightsChoiceMatrix(array $rights, array $options = [])
    {

        $param                  = [];
        $param['title']         = '';
        $param['canedit']       = true;
        $param['default_class'] = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

       // To be completed before display to avoid non available rights in DB
        $availablerights = ProfileRight::getAllPossibleRights();

        $column_labels = [];
        $columns       = [];
        $rows          = [];

        foreach ($rights as $info) {
            if (is_string($info)) {
                $rows[] = $info;
                continue;
            }
            if (
                is_array($info)
                && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
                && (!empty($info['label']))
                && (!empty($info['field']))
            ) {
               // Add right if it does not exists : security for update
                if (!isset($availablerights[$info['field']])) {
                    ProfileRight::addProfileRights([$info['field']]);
                }

                $row = ['label'   => $info['label'],
                    'columns' => []
                ];
                if (!empty($info['row_class'])) {
                    $row['class'] = $info['row_class'];
                } else if (isset($info['scope'])) {
                    $default_scope_class = !empty($param['default_class']) ? $param['default_class'] : 'tab_bg_2';
                    $row['class'] = $info['scope'] === 'global' ? 'tab_bg_4' : $default_scope_class;
                } else {
                    $row['class'] = $param['default_class'];
                }
                if (isset($this->fields[$info['field']])) {
                    $profile_right = $this->fields[$info['field']];
                } else {
                    $profile_right = 0;
                }

                if (isset($info['rights'])) {
                    $itemRights = $info['rights'];
                } else {
                    $itemRights = self::getRightsFor($info['itemtype']);
                }
                foreach ($itemRights as $right => $label) {
                    if (!isset($column_labels[$right])) {
                        $column_labels[$right] = [];
                    }
                    if (is_array($label)) {
                        $long_label = $label['long'];
                    } else {
                        $long_label = $label;
                    }
                    if (!isset($column_labels[$right][$long_label])) {
                        $column_labels[$right][$long_label] = count($column_labels[$right]);
                    }
                    $right_value                  = $right . '_' . $column_labels[$right][$long_label];

                    $columns[$right_value]        = $label;

                    $checked                      = ((($profile_right & $right) == $right) ? 1 : 0);
                    $row['columns'][$right_value] = ['checked' => $checked];
                    if (!$param['canedit']) {
                        $row['columns'][$right_value]['readonly'] = true;
                    }
                }
                if (!empty($info['html_field'])) {
                    $rows[$info['html_field']] = $row;
                } else {
                    $rows['_' . $info['field']] = $row;
                }
            }
        }

        uksort($columns, function ($a, $b) {
            $a = explode('_', $a);
            $b = explode('_', $b);

          // For standard rights sort by right
            if (($a[0] < 1024) || ($b[0] < 1024)) {
                if ($a[0] > $b[0]) {
                    return 1;
                }
                if ($a[0] < $b[0]) {
                    return -1;
                }
            }

          // For extra right sort by type
            if ($a[1] > $b[1]) {
                 return 1;
            }
            if ($a[1] < $b[1]) {
                return -1;
            }
            return 0;
        });

        return Html::showCheckboxMatrix(
            $columns,
            $rows,
            ['title'                => $param['title'],
                'row_check_all'        => count($columns) > 1,
                'col_check_all'        => count($rows) > 1
            ]
        );
    }


    /**
     * Get right linear right choice.
     *
     * @since 0.85
     *
     * @param $elements  array   all pair identifier => label
     * @param $options   array   possible:
     *             'canedit'
     *             'field'         name of the HTML field
     *             'value'         the value inside the database
     *             'max_per_line'  maximum number of elements per line
     *             'check_all'     add a checkbox to check or uncheck every checkbox
     *             'rand'          random value used to generate the ids
     *             'zero_on_empty' do we send 0 when checkbox is not checked ?
     *             'display'
     *             'check_method'  method used to check the right
     *
     * @return string|void Return generated content if `display` parameter is true.
     **/
    public static function getLinearRightChoice(array $elements, array $options = [])
    {

        $param                  = [];
        $param['canedit']       = true;
        $param['field']         = '';
        $param['value']         = '';
        $param['max_per_line']  = 10;
        $param['check_all']     = false;
        $param['rand']          = mt_rand();
        $param['zero_on_empty'] = true;
        $param['display']       = true;
        $param['check_method']  = function ($element, $field) {
            return (($field & $element) == $element);
        };

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        if (empty($param['field'])) {
            return;
        }

        $nb_cbs      = count($elements);
        $cb_options  = ['readonly' => !$param['canedit']];
        $massive_tag = 'checkall_' . $param['field'] . '_' . $param['rand'];
        if ($param['check_all']) {
            $nb_cbs++;
            $cb_options['massive_tags'] = $massive_tag;
        }

        $nb_lines         = ceil($nb_cbs / $param['max_per_line']);
        $nb_item_per_line = ceil($nb_cbs / $nb_lines);

        $out              = '';

        $count            = 0;
        $nb_checked       = 0;
        foreach ($elements as $element => $label) {
            if ($count != 0) {
                if (($count % $nb_item_per_line) == 0) {
                    $out .= "<br>\n";
                } else {
                    $out .= "&nbsp;-\n\t\t&nbsp;";
                }
            } else {
                $out .= "\n\t\t";
            }
            $out                        .= $label . '&nbsp;';
            $cb_options['name']          = $param['field'] . '[' . $element . ']';
            $cb_options['id']            = Html::cleanId('checkbox_linear_' . $cb_options['name'] .
                                                      '_' . $param['rand']);
            $cb_options['zero_on_empty'] = $param['zero_on_empty'];

            $cb_options['checked']       = $param['check_method'](
                $element,
                $param['value']
            );

            $out                        .= Html::getCheckbox($cb_options);
            $count++;
            if ($cb_options['checked']) {
                $nb_checked++;
            }
        }

        if ($param['check_all']) {
            $cb_options = ['criterion' => ['tag_for_massive' => $massive_tag],
                'id'        => Html::cleanId('checkbox_linear_' . $param['rand'])
            ];
            if ($nb_checked > (count($elements) / 2)) {
                $cb_options['checked'] = true;
            }
            $out .= "&nbsp;-&nbsp;<i><b>" . __('Select/unselect all') . "</b></i>&nbsp;" .
                  Html::getCheckbox($cb_options);
        }

        if (!$param['display']) {
            return $out;
        }

        echo $out;
    }


    public static function getIcon()
    {
        return "ti ti-user-check";
    }

    /**
     * Get all super admin profiles ids
     *
     * @return array of ids
     */
    public static function getSuperAdminProfilesId(): array
    {
        $super_admin_profiles = (new self())->find([
            'id' => new QuerySubQuery([
                'SELECT' => 'profiles_id',
                'FROM'   => ProfileRight::getTable(),
                'WHERE'  => [
                    'name'   => static::$rightname,
                    'rights' => ["&", UPDATE],
                ]
            ]),
            'interface' => 'central',
        ]);

        return array_column($super_admin_profiles, 'id');
    }

    /**
     * Check if this profile is the last super-admin profile (a "super-admin
     * profile" is a profile that can edit other profiles)
     *
     * @return bool
     */
    public function isLastSuperAdminProfile(): bool
    {
        $profiles_ids = self::getSuperAdminProfilesId();
        return
            count($profiles_ids) == 1 // Only one super admin
            && $profiles_ids[0] == $this->fields['id'] // Id match this account
        ;
    }

    public function canPurgeItem()
    {
        // We can't delete the last super admin profile
        if ($this->isLastSuperAdminProfile()) {
            return false;
        }

        return true;
    }
}
