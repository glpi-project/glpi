<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class RuleDictionnarySoftwareCollection extends RuleCollection
{
    // From RuleCollection

    public $stop_on_first_match = true;
    public $can_replay_rules    = true;
    public $menu_type           = 'dictionnary';
    public $menu_option         = 'software';

    public static $rightname           = 'rule_dictionnary_software';

    public function getTitle()
    {
        //TRANS: software in plural
        return __('Dictionary of software');
    }

    public function cleanTestOutputCriterias(array $output)
    {
        //If output array contains keys begining with _ : drop it
        foreach (array_keys($output) as $criteria) {
            if (($criteria[0] == '_') && ($criteria != '_ignore_import')) {
                unset($output[$criteria]);
            }
        }
        return $output;
    }

    public function warningBeforeReplayRulesOnExistingDB()
    {
        $rule_class = $this->getRuleClassName();

        $twig_params = [
            'warning_title' => __('Warning before running rename based on the dictionary rules'),
            'warning_message' => __('Warning! This operation can put merged software in the trashbin. Ensure to notify your users.'),
            'manufacturer_label' => __('Replay dictionary rules for manufacturers'),
            'btn_label' => _sx('button', 'Post'),
            'target' => $rule_class::getSearchURL(),
            'emptylabel' => __('All'),
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/alerts_macros.html.twig' as alerts %}
            <form name="testrule_form" id="softdictionnary_confirmation" method="post" action="{{ target }}">
                <div class="card">
                    <div class="card-body">
                        {{ alerts.alert_warning(warning_title, warning_message) }}
                        <div>
                            {{ fields.dropdownField('Manufacturer', 'manufacturer', 0, manufacturer_label, {
                                emptylabel: emptylabel,
                            }) }}
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-row-reverse">
                        <input type="hidden" name="replay_confirm" value="replay_confirm">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <button type="submit" name="replay_rule" class="btn btn-primary">{{ btn_label }}</button>
                    </div>
                </div>
            </form>
TWIG, $twig_params);
        return true;
    }

    public function countTotalItemsForRulesReplay(array $params = []): int
    {
        global $DB;

        return $DB->request($this->getIteratorCriteriaForRulesReplay($params))->count();
    }

    public function replayRulesOnExistingDB($offset = 0, $maxtime = 0, $items = [], $params = [])
    {
        global $DB;

        $i  = $offset;

        if (count($items) === 0) {
            $criteria = $this->getIteratorCriteriaForRulesReplay($params);
            if ($offset) {
                $criteria['START'] = (int) $offset;
                $criteria['LIMIT'] = 2 ** 32; // MySQL requires a limit, set it to an unreachable value
            }

            $iterator = $DB->request($criteria);
            $nb   = count($iterator) + $offset;

            foreach ($iterator as $input) {
                //If manufacturer is set, then first run the manufacturer's dictionary
                if (isset($input["manufacturer"])) {
                    $input["manufacturer"] = Manufacturer::processName($input["manufacturer"]);
                }

                //Replay software dictionary rules
                $res_rule = $this->processAllRules($input, [], []);

                if (
                    (isset($res_rule["name"]) && (strtolower($res_rule["name"]) != strtolower($input["name"])))
                    || (isset($res_rule["version"]) && ($res_rule["version"] != ''))
                    || (isset($res_rule['new_entities_id'])
                    && ($res_rule['new_entities_id'] != $input['entities_id']))
                    || (isset($res_rule['is_helpdesk_visible'])
                    && ($res_rule['is_helpdesk_visible'] != $input['helpdesk']))
                    || (isset($res_rule['manufacturer'])
                    && ($res_rule['manufacturer'] != $input['manufacturer']))
                    || (isset($res_rule['softwarecategories_id'])
                    && ($res_rule['softwarecategories_id'] != $input['softwarecategories_id']))
                ) {
                    $IDs = [];
                    //Find all the software in the database with the same name and manufacturer
                    $same_iterator = $DB->request([
                        'SELECT' => 'id',
                        'FROM'   => 'glpi_softwares',
                        'WHERE'  => [
                            'name'               => $input['name'],
                            'manufacturers_id'   => $input['manufacturers_id'],
                        ],
                    ]);

                    if (count($same_iterator)) {
                        //Store all the software's IDs in an array
                        foreach ($same_iterator as $result) {
                            $IDs[] = $result["id"];
                        }
                        //Replay dictionary on all the software
                        $this->replayDictionnaryOnSoftwaresByID($IDs, $res_rule);
                    }
                }
                $i++;

                if ($maxtime && microtime(true) > $maxtime) {
                    break;
                }
            }
        } else {
            $this->replayDictionnaryOnSoftwaresByID($items);
            return count($items);
        }

        return (($i == $nb) ? -1 : $i);
    }

    private function getIteratorCriteriaForRulesReplay(array $params): array
    {
        // Select all the differents software
        $criteria = [
            'SELECT'          => [
                'glpi_softwares.name',
                'glpi_manufacturers.name AS manufacturer',
                'glpi_softwares.manufacturers_id AS manufacturers_id',
                'glpi_softwares.entities_id AS entities_id',
                'glpi_softwares.is_helpdesk_visible AS helpdesk',
                'glpi_softwares.softwarecategories_id AS softwarecategories_id',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_softwares',
            'LEFT JOIN'       => [
                'glpi_manufacturers' => [
                    'ON' => [
                        'glpi_manufacturers' => 'id',
                        'glpi_softwares'     => 'manufacturers_id',
                    ],
                ],
            ],
            'WHERE'           => [
                // Do not replay on trashbin and templates
                'glpi_softwares.is_deleted'   => 0,
                'glpi_softwares.is_template'  => 0,
            ],
        ];

        if (isset($params['manufacturer']) && $params['manufacturer']) {
            $criteria['WHERE']['glpi_softwares.manufacturers_id'] = $params['manufacturer'];
        }

        return $criteria;
    }

    /**
     * Replay dictionary on several software
     *
     * @param array $IDs       array of software IDs to replay
     * @param array $res_rule  array of rule results
     *
     * @return void
     **/
    public function replayDictionnaryOnSoftwaresByID(array $IDs, $res_rule = [])
    {
        global $DB;

        $new_softs  = [];
        $delete_ids = [];

        foreach ($IDs as $ID) {
            $iterator = $DB->request([
                'SELECT'    => [
                    'gs.id',
                    'gs.name AS name',
                    'gs.entities_id AS entities_id',
                    'gm.name AS manufacturer',
                ],
                'FROM'      => 'glpi_softwares AS gs',
                'LEFT JOIN' => [
                    'glpi_manufacturers AS gm' => [
                        'ON' => [
                            'gs'  => 'manufacturers_id',
                            'gm'  => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'gs.is_template'  => 0,
                    'gs.id'           => $ID,
                ],
            ]);

            if (count($iterator)) {
                $soft = $iterator->current();
                //For each software
                $this->replayDictionnaryOnOneSoftware(
                    $new_softs,
                    $res_rule,
                    $ID,
                    $res_rule['new_entities_id'] ?? $soft["entities_id"],
                    $soft['name'] ?? '',
                    $soft['manufacturer'] ?? '',
                    $delete_ids
                );
            }
        }
        //Delete software if needed
        $this->putOldSoftsInTrash($delete_ids);
    }

    /**
     * Replay dictionary on one software
     *
     * @param array &$new_softs      array containing new software already computed
     * @param array $res_rule        array of rule results
     * @param integer $ID                    ID of the software
     * @param integer $entity                working entity ID
     * @param string $name                  software name
     * @param string $manufacturer          manufacturer name
     * @param array &$soft_ids       array containing replay software need to be put in trashbin
     **/
    public function replayDictionnaryOnOneSoftware(
        array &$new_softs,
        array $res_rule,
        $ID,
        $entity,
        $name,
        $manufacturer,
        array &$soft_ids
    ) {
        global $DB;

        $input["name"]         = $name;
        $input["manufacturer"] = $manufacturer;
        $input["entities_id"]  = $entity;

        if ($res_rule === []) {
            $res_rule = $this->processAllRules($input, [], []);
        }
        $soft = new Software();
        if (isset($res_rule['_ignore_import']) && ($res_rule['_ignore_import'] == 1)) {
            $soft->putInTrash($ID, __('Software deleted by GLPI dictionary rules'));
            return;
        }

        //Software's name has changed or entity
        if (
            (isset($res_rule["name"]) && (strtolower($res_rule["name"]) !== strtolower($name)))
            //Entity has changed, and new entity is a parent of the current one
            || (!isset($res_rule["name"])
              && isset($res_rule['new_entities_id'])
              && in_array(
                  $res_rule['new_entities_id'],
                  getAncestorsOf('glpi_entities', $entity)
              ))
        ) {
            if (isset($res_rule["name"])) {
                $new_name = $res_rule["name"];
            } else {
                $new_name = $name;
            }

            if (isset($res_rule["manufacturer"]) && $res_rule["manufacturer"]) {
                $manufacturer = $res_rule["manufacturer"];
            }

            //New software not already present in this entity
            if (!isset($new_softs[$entity][$new_name])) {
                // create new software or restore it from trashbin
                $new_software_id               = $soft->addOrRestoreFromTrash(
                    $new_name,
                    $manufacturer,
                    $entity,
                    '',
                    true
                );
                $new_softs[$entity][$new_name] = $new_software_id;
            } else {
                $new_software_id = $new_softs[$entity][$new_name];
            }
            // Move licenses to new software
            $this->moveLicenses($ID, $new_software_id);
        } else {
            $new_software_id = $ID;
            $res_rule["id"]  = $ID;
            if (isset($res_rule["manufacturer"]) && $res_rule["manufacturer"]) {
                $res_rule["manufacturers_id"] = Dropdown::importExternal(
                    'Manufacturer',
                    $res_rule["manufacturer"]
                );
                unset($res_rule["manufacturer"]);
            }
            $soft->update($res_rule);
        }

        // Add to software to deleted list
        if ($new_software_id !== $ID) {
            $soft_ids[] = $ID;
        }

        //Get all the different versions for a software
        $iterator = $DB->request([
            'FROM'   => 'glpi_softwareversions',
            'WHERE'  => ['softwares_id' => $ID],
        ]);

        foreach ($iterator as $version) {
            $input["version"] = $version["name"];
            $old_version_name = $input["version"];

            if (isset($res_rule['version_append']) && $res_rule['version_append'] != '') {
                $new_version_name = $old_version_name . $res_rule['version_append'];
            } elseif (isset($res_rule["version"]) && $res_rule["version"] != '') {
                $new_version_name = $res_rule["version"];
            } else {
                $new_version_name = $version["name"];
            }
            if (
                ($ID != $new_software_id)
                || ($new_version_name != $old_version_name)
            ) {
                $this->moveVersions(
                    $ID,
                    $new_software_id,
                    $version["id"],
                    $old_version_name,
                    $new_version_name,
                    $entity
                );
            }
        }
    }

    /**
     * Delete a list of software
     *
     * @param array $soft_ids array containing replay software need to be put in trashbin
     **/
    public function putOldSoftsInTrash(array $soft_ids)
    {
        global $DB;

        if (count($soft_ids) > 0) {
            //Try to delete all the software that are not used anymore
            // (which means that don't have version associated anymore)
            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_softwares.id',
                    'COUNT' => 'glpi_softwareversions.softwares_id AS cpt',
                ],
                'FROM'      => 'glpi_softwares',
                'LEFT JOIN' => [
                    'glpi_softwareversions' => [
                        'ON' => [
                            'glpi_softwareversions' => 'softwares_id',
                            'glpi_softwares'        => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'glpi_softwares.id'  => $soft_ids,
                    'is_deleted'         => 0,
                ],
                'GROUPBY'   => 'glpi_softwares.id',
                'HAVING'    => ['cpt' => 0],
            ]);

            $software = new Software();
            foreach ($iterator as $soft) {
                $software->putInTrash($soft["id"], __('Software deleted by GLPI dictionary rules'));
            }
        }
    }

    /**
     * Change software's name, and move versions if needed
     *
     * @param int $ID                    old software ID
     * @param int $new_software_id       new software ID
     * @param int $version_id            version ID to move
     * @param string $old_version        old version name (Not used)
     * @param string $new_version        new version name
     * @param int $entity                entity ID
     * @return void
     */
    public function moveVersions($ID, $new_software_id, $version_id, $old_version, $new_version, $entity)
    {
        global $DB;

        $new_versionID = $this->versionExists($new_software_id, $new_version);

        // Do something if it is not the same version
        if ($new_versionID != $version_id) {
            //A version does not exist : update existing one
            if ($new_versionID == -1) {
                //Transfer versions from old software to new software for a specific version
                $DB->update(
                    'glpi_softwareversions',
                    [
                        'name'         => $new_version,
                        'softwares_id' => $new_software_id,
                    ],
                    [
                        'id' => $version_id,
                    ]
                );
            } else {
                // Delete software can be in double after update
                $item_softwareversion_table = Item_SoftwareVersion::getTable();
                $iterator = $DB->request([
                    'SELECT'    => ['gcs_2.*'],
                    'FROM'      => $item_softwareversion_table,
                    'LEFT JOIN' => [
                        "{$item_softwareversion_table} AS gcs_2" => [
                            'FKEY'   => [
                                'gcs_2'                       => 'items_id',
                                $item_softwareversion_table   => 'items_id', [
                                    'AND' => [
                                        'gcs_2.itemtype' => $item_softwareversion_table . '.itemtype',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        "{$item_softwareversion_table}.softwareversions_id"   => $new_versionID,
                        'gcs_2.softwareversions_id'                           => $version_id,
                    ],
                ]);
                foreach ($iterator as $data) {
                    $DB->delete(
                        'glpi_items_softwareversions',
                        [
                            'id' => $data['id'],
                        ]
                    );
                }

                //Change ID of the version in glpi_items_softwareversions
                $DB->update(
                    $item_softwareversion_table,
                    [
                        'softwareversions_id' => $new_versionID,
                    ],
                    [
                        'softwareversions_id' => $version_id,
                    ]
                );

                // Update licenses version link
                $DB->update(
                    'glpi_softwarelicenses',
                    [
                        'softwareversions_id_buy' => $new_versionID,
                    ],
                    [
                        'softwareversions_id_buy' => $version_id,
                    ]
                );

                $DB->update(
                    'glpi_softwarelicenses',
                    [
                        'softwareversions_id_use' => $new_versionID,
                    ],
                    [
                        'softwareversions_id_use' => $version_id,
                    ]
                );

                //Delete old version
                $old_version = new SoftwareVersion();
                $old_version->delete(["id" => $version_id]);
            }
        }
    }

    /**
     * Move licenses from a software to another
     *
     * @param integer $old_software_id old software ID
     * @param integer $new_software_id new software ID
     *
     * @return boolean
     **/
    public function moveLicenses($old_software_id, $new_software_id)
    {
        global $DB;

        // Return false if one of the 2 software doesn't exist
        if (
            !countElementsInTable('glpi_softwares', ['id' => $old_software_id])
            || !countElementsInTable('glpi_softwares', ['id' => $new_software_id])
        ) {
            return false;
        }

        // Transfer licenses to new software if needed
        if ($old_software_id != $new_software_id) {
            $DB->update(
                'glpi_softwarelicenses',
                [
                    'softwares_id' => $new_software_id,
                ],
                [
                    'softwares_id' => $old_software_id,
                ]
            );
        }
        return true;
    }

    /**
     * Check if a version exists
     *
     * @param integer $software_id  software ID
     * @param string $version      version name
     **/
    public function versionExists($software_id, $version)
    {
        global $DB;

        // Check if the version exists
        $iterator = $DB->request([
            'FROM'   => 'glpi_softwareversions',
            'WHERE'  => [
                'softwares_id' => $software_id,
                'name'         => $version,
            ],
        ]);
        return count($iterator) ? $iterator->current()['id'] : -1;
    }
}
