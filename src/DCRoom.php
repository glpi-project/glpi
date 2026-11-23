<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Features\DCBreadcrumb;
use Glpi\Features\DCBreadcrumbInterface;

use function Safe\json_decode;
use function Safe\preg_match;

/**
 * DCRoom Class
 **/
class DCRoom extends CommonDBTM implements DCBreadcrumbInterface
{
    use DCBreadcrumb;

    public const AXIS_COLUMN = 'column';
    public const AXIS_ROW = 'row';
    public const AXIS_LABEL_MAX_LENGTH = 100;

    // From CommonDBTM
    public bool $dohistory                   = true;
    protected bool $usenotepad               = true;
    public static string $rightname                   = 'datacenter';

    public static function getTypeName($nb = 0)
    {
        return _n('Server room', 'Server rooms', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', Datacenter::class, self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this
         ->addStandardTab(Rack::class, $ong, $options)
         ->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Document_Item::class, $ong, $options)
         ->addStandardTab(ManualLink::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
        }
        TemplateRenderer::getInstance()->display('pages/management/dcroom.html.twig', [
            'item' => $this,
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if ((int) ($input['vis_rows'] ?? 0) < 1) {
            Session::addMessageAfterRedirect(
                __s('Number of rows must be >= 1'),
                true,
                ERROR
            );
            return false;
        }

        if ((int) ($input['vis_cols'] ?? 0) < 1) {
            Session::addMessageAfterRedirect(
                __s('Number of columns must be >= 1'),
                true,
                ERROR
            );
            return false;
        }

        return $this->manageBlueprint($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['vis_rows']) && (int) ($input['vis_rows']) < 1) {
            Session::addMessageAfterRedirect(
                __s('Number of rows must be >= 1'),
                true,
                ERROR
            );
            return false;
        }

        if (isset($input['vis_cols']) && (int) ($input['vis_cols']) < 1) {
            Session::addMessageAfterRedirect(
                __s('Number of columns must be >= 1'),
                true,
                ERROR
            );
            return false;
        }

        return $this->manageBlueprint($input);
    }

    public function cleanDBonPurge()
    {
        Toolbox::deletePicture($this->fields['blueprint']);
    }

    /**
     * Add/remove blueprint picture
     * @param  array $input the form input
     * @return array        the altered input
     */
    public function manageBlueprint($input)
    {
        if (
            isset($input["_blank_blueprint"])
            && $input["_blank_blueprint"]
        ) {
            $input['blueprint'] = '';

            if (array_key_exists('blueprint', $this->fields)) {
                Toolbox::deletePicture($this->fields['blueprint']);
            }
        }

        if (isset($input["_blueprint"])) {
            $blueprint = array_shift($input["_blueprint"]);

            if ($dest = Toolbox::savePicture(GLPI_TMP_DIR . '/' . $blueprint)) {
                $input['blueprint'] = $dest;
            } else {
                Session::addMessageAfterRedirect(__s('Unable to save picture file.'), true, ERROR);
            }

            if (array_key_exists('blueprint', $this->fields)) {
                Toolbox::deletePicture($this->fields['blueprint']);
            }
        }

        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false, // implicit key==1
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => Datacenter::getTable(),
            'field'              => 'name',
            'name'               => Datacenter::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'vis_cols',
            'name'               => __('Number of columns'),
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'vis_rows',
            'name'               => __('Number of rows'),
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }

    /**
     * @return array
     */
    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        // separator
        $tab[] = [
            'id'   => 'dcroom',
            'name' => self::getTypeName(1),
        ];

        $tab[] = [
            'id'                 => '1450',
            'table'              => 'glpi_dcrooms',
            'field'              => 'name',
            'datatype'           => 'itemlink',
            'name'               => self::getTypeName(1),
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_racks',
                    'linkfield'          => 'racks_id',
                    'joinparams'         => [
                        'beforejoin'         => [
                            'table'              => 'glpi_items_racks',
                            'joinparams'         => [
                                'jointype'           => 'itemtype_item',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $tab;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item::class) {
            case Datacenter::class:
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        self::getTable(),
                        [
                            'datacenters_id'  => $item->getID(),
                            'is_deleted'      => 0,
                        ]
                    );
                }
                return self::createTabEntry(
                    self::getTypeName(Session::getPluralNumber()),
                    $nb,
                    $item::class
                );
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof Datacenter) {
            return false;
        }

        self::showForDatacenter($item);
        return true;
    }

    /**
     * Print datacenter's roms
     *
     * @param Datacenter $datacenter Datacenter object
     *
     * @return void|bool (display) Returns false if there is a rights error.
     **/
    public static function showForDatacenter(Datacenter $datacenter)
    {
        global $DB;

        $ID = $datacenter->getID();
        $rand = mt_rand();

        if (
            !$datacenter->getFromDB($ID)
            || !$datacenter->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $datacenter->canEdit($ID);

        $rooms = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'datacenters_id' => $datacenter->getID(),
            ],
        ]);

        if ($canedit) {
            echo "<div class='mt-1 mb-3 text-center'>";
            Html::showSimpleForm(
                self::getFormURL(),
                '_add_fromitem',
                __('New room for this datacenter...'),
                ['datacenters_id' => $datacenter->getID()]
            );
            echo "</div>";
        }

        $dcroom = new self();
        $entries = [];
        foreach ($rooms as $room) {
            $dcroom->getFromResultSet($room);
            $entries[] = [
                'itemtype' => self::class,
                'id' => $room['id'],
                'name' => $dcroom->getLink(),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Get already filled places
     *
     * @param string $current Current position to exclude; defaults to null
     *
     * @return array [x => [pos_x], y => [pos_y]]]
     */
    public function getFilled($current = null)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['position'],
            'FROM'   => Rack::getTable(),
            'WHERE'  => [
                'dcrooms_id'   => $this->getID(),
                'is_deleted'   => 0,
            ],
        ]);

        $filled = [];
        foreach ($iterator as $rack) {
            if (preg_match('/(\d+),\s?(\d+)/', $rack['position'])) {
                $position = $rack['position'];
                if (empty($current) || $current !== $position) {
                    $filled[$position] = $position;
                }
            }
        }

        return $filled;
    }

    /**
     * Get all possible positions for current room
     *
     * @return array
     */
    public function getAllPositions()
    {
        $positions = [];
        $column_labels = $this->getAxisLabels(self::AXIS_COLUMN);
        $row_labels = $this->getAxisLabels(self::AXIS_ROW);
        for ($x = 1; $x < (int) $this->fields['vis_cols'] + 1; ++$x) {
            for ($y = 1; $y < (int) $this->fields['vis_rows'] + 1; ++$y) {
                $positions["$x,$y"] = sprintf(
                    __('col: %1$s, row: %2$s'),
                    $column_labels[$x] ?? $this->getDefaultAxisLabel(self::AXIS_COLUMN, $x),
                    $row_labels[$y] ?? $this->getDefaultAxisLabel(self::AXIS_ROW, $y)
                );
            }
        }
        return $positions;
    }

    /**
     * Get custom labels for an axis, indexed by their 1-based position.
     *
     * @return array<int, string>
     */
    public function getAxisLabels(string $axis): array
    {
        $field = $this->getAxisLabelsField($axis);
        $value = $this->fields[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        $labels = [];
        foreach ($decoded as $position => $label) {
            if (
                (!is_int($position) && !ctype_digit($position))
                || (int) $position < 1
                || !is_string($label)
                || trim($label) === ''
            ) {
                continue;
            }
            $labels[(int) $position] = $label;
        }

        return $labels;
    }

    /**
     * Get the effective label for an axis position.
     */
    public function getAxisLabel(string $axis, int $position): string
    {
        $labels = $this->getAxisLabels($axis);
        return $labels[$position] ?? $this->getDefaultAxisLabel($axis, $position);
    }

    /**
     * Save or remove a custom label for an axis position.
     */
    public function updateAxisLabel(string $axis, int $position, string $label): bool
    {
        $dimension_field = match ($axis) {
            self::AXIS_COLUMN => 'vis_cols',
            self::AXIS_ROW => 'vis_rows',
            default => throw new InvalidArgumentException('Invalid room axis.'),
        };
        if ($position < 1 || $position > (int) $this->fields[$dimension_field]) {
            throw new InvalidArgumentException('Axis position is outside the room bounds.');
        }

        $label = trim($label);
        if (mb_strlen($label, 'UTF-8') > self::AXIS_LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException('Axis label is too long.');
        }

        $labels = $this->getAxisLabels($axis);
        if ($label === '') {
            unset($labels[$position]);
        } else {
            $labels[$position] = $label;
        }
        ksort($labels, SORT_NUMERIC);

        return $this->update([
            'id' => $this->getID(),
            $this->getAxisLabelsField($axis) => $labels === []
                ? null
                : json_encode(
                    $labels,
                    JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
        ]);
    }

    private function getAxisLabelsField(string $axis): string
    {
        return match ($axis) {
            self::AXIS_COLUMN => 'column_labels',
            self::AXIS_ROW => 'row_labels',
            default => throw new InvalidArgumentException('Invalid room axis.'),
        };
    }

    private function getDefaultAxisLabel(string $axis, int $position): string
    {
        return match ($axis) {
            self::AXIS_COLUMN => Toolbox::getBijectiveIndex($position),
            self::AXIS_ROW => (string) $position,
            default => throw new InvalidArgumentException('Invalid room axis.'),
        };
    }

    public static function getIcon()
    {
        return "ti ti-building";
    }
}
