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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

use function Safe\preg_replace;
use function Safe\strtotime;

abstract class CommonITILSatisfaction extends CommonDBTM
{
    public $dohistory         = true;
    public $history_blacklist = ['date_answered'];

    /**
     * Survey is done internally
     */
    public const TYPE_INTERNAL = 1;

    /**
     * Survey is done externally
     */
    public const TYPE_EXTERNAL = 2;

    abstract public static function getConfigSufix(): string;
    abstract public static function getSearchOptionIDOffset(): int;

    public static function getTypeName($nb = 0)
    {
        return __('Satisfaction');
    }

    public static function getIcon()
    {
        return 'ti ti-star';
    }

    public static function getItemInstance(): CommonITILObject
    {
        $class = preg_replace('/Satisfaction$/', '', static::class);

        if (!is_a($class, CommonITILObject::class, true)) {
            throw new LogicException();
        }

        return new $class();
    }

    /**
     * for use showFormHeader
     **/
    public static function getIndexName()
    {
        return static::getItemInstance()::getForeignKeyField();
    }

    public function getLogTypeID()
    {
        $item = static::getItemInstance();
        return [$item::class, $this->fields[$item::getForeignKeyField()]];
    }

    public static function canUpdate(): bool
    {
        $item = static::getItemInstance();
        return (Session::haveRight($item::$rightname, READ));
    }

    /**
     * Is the current user have right to update the current satisfaction
     *
     * @return boolean
     **/
    public function canUpdateItem(): bool
    {
        $item = static::getItemInstance();
        if (!$item->getFromDB($this->fields[$item::getForeignKeyField()])) {
            return false;
        }

        // you can't change if your answer > 12h
        if (
            !is_null($this->fields['date_answered'])
            && ((time() - strtotime($this->fields['date_answered'])) > (12 * HOUR_TIMESTAMP))
        ) {
            return false;
        }

        if (
            $item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
            || ($item->fields["users_id_recipient"] === Session::getLoginUserID() && Session::haveRight($item::$rightname, $item::SURVEY))
            || (isset($_SESSION["glpigroups"])
                && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"]))
        ) {
            return true;
        }
        return false;
    }

    /**
     * form for satisfaction
     *
     * @param CommonITILObject $item The item this satisfaction is for
     **/
    public function showSatisactionForm($item, bool $add_form_header = true)
    {
        $options             = [];
        $options['colspan']  = 1;
        $options['candel'] = false;

        // for external inquest => link
        if ((int) $this->fields["type"] === self::TYPE_EXTERNAL) {
            $url = Entity::generateLinkSatisfaction($item);
            TemplateRenderer::getInstance()->display('/components/itilobject/itilsatisfaction.html.twig', [
                'url' => $url,
            ]);
        } else { // for internal inquest => form
            $config_suffix = $item->getType() === 'Ticket' ? '' : ('_' . strtolower($item->getType()));

            if ($add_form_header) {
                $this->showFormHeader($options);
            }
            // Set default satisfaction to 3 if not set
            if (is_null($this->fields["satisfaction"])) {
                $default_rate = Entity::getUsedConfig('inquest_config' . $config_suffix, $item->fields['entities_id'], 'inquest_default_rate' . $config_suffix);
                $this->fields["satisfaction"] = $default_rate;
            }
            $max_rate = Entity::getUsedConfig('inquest_config' . $config_suffix, $item->fields['entities_id'], 'inquest_max_rate' . $config_suffix);
            $duration = (int) Entity::getUsedConfig('inquest_duration' . $config_suffix, $item->fields['entities_id']);
            $expired = $duration !== 0 && (time() - strtotime($this->fields['date_begin'])) > $duration * DAY_TIMESTAMP;
            TemplateRenderer::getInstance()->display('/components/itilobject/itilsatisfaction.html.twig', [
                'item'   => $this,
                'parent_item' => $item,
                'max_rate' => $max_rate,
                'params' => $options,
                'expired' => $expired,
            ]);
        }
    }

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('satisfaction', $input) && $input['satisfaction'] >= 0) {
            $input["date_answered"] = $_SESSION["glpi_currenttime"];
        }

        if (array_key_exists('satisfaction', $input) || array_key_exists('comment', $input)) {
            $satisfaction = array_key_exists('satisfaction', $input) ? $input['satisfaction'] : $this->fields['satisfaction'];
            $comment      = array_key_exists('comment', $input) ? $input['comment'] : $this->fields['comment'];
            $itemtype     = static::getItemInstance()::class;
            $entities_id  = $this->getItemEntity($itemtype, $this->fields[$itemtype::getForeignKeyField()]);

            $config_suffix = $itemtype === 'Ticket' ? '' : ('_' . strtolower($itemtype));
            $inquest_mandatory_comment = Entity::getUsedConfig('inquest_config' . $config_suffix, $entities_id, 'inquest_mandatory_comment' . $config_suffix);
            if ($inquest_mandatory_comment && ($satisfaction <= $inquest_mandatory_comment) && empty($comment)) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(__('Comment is required if score is less than or equal to %d'), $inquest_mandatory_comment)),
                    false,
                    ERROR
                );
                return false;
            }
        }

        if (array_key_exists('satisfaction', $input) && $input['satisfaction'] >= 0) {
            $item = static::getItemInstance();
            $fkey = static::getIndexName();
            if ($item->getFromDB($input[$fkey] ?? $this->fields[$fkey])) {
                $max_rate = Entity::getUsedConfig(
                    'inquest_config',
                    $item->fields['entities_id'],
                    'inquest_max_rate' . static::getConfigSufix()
                );
                $input['satisfaction_scaled_to_5'] = $input['satisfaction'] / ($max_rate / 5);
            }
        }

        return $input;
    }

    public function post_addItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            $item = static::getItemInstance();
            if ($item->getFromDB($this->fields[$item::getForeignKeyField()])) {
                NotificationEvent::raiseEvent("satisfaction", $item, [], $this);
            }
        }
    }

    public function post_UpdateItem($history = true)
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            // Send notification only if fields related to reply are updated.
            $answer_updates = array_filter(
                $this->updates,
                fn($field) => in_array($field, ['satisfaction', 'comment'])
            );

            $item = static::getItemInstance();
            if (count($answer_updates) > 1 && $item->getFromDB($this->fields[$item::getForeignKeyField()])) {
                NotificationEvent::raiseEvent("replysatisfaction", $item, [], $this);
            }
        }
    }

    /**
     * display satisfaction value
     *
     * @param int|float $value Between 0 and 10
     **/
    public static function displaySatisfaction($value, $entities_id)
    {
        if (!is_numeric($value)) {
            return "";
        }

        $max_rate = (int) Entity::getUsedConfig(
            'inquest_config',
            $entities_id,
            'inquest_max_rate' . static::getConfigSufix()
        );

        if ($value < 0) {
            $value = 0;
        }
        if ($value > $max_rate) {
            $value = $max_rate;
        }

        $rand = mt_rand();
        $out = "<div id='rateit_$rand' class='rateit'></div>";
        $out .= Html::scriptBlock("
            $(function () {
                $('#rateit_$rand').rateit({
                    max: $max_rate,
                    resetable: false,
                    value: $value,
                    readonly: true,
                });
            });
        ");

        return $out;
    }


    /**
     * Get name of inquest type
     *
     * @param int $value Survey type ID
     **/
    public static function getTypeInquestName($value)
    {

        switch ($value) {
            case self::TYPE_INTERNAL:
                return __('Internal survey');

            case self::TYPE_EXTERNAL:
                return __('External survey');

            default:
                // Get value if not defined
                return $value;
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'type':
                return htmlescape(self::getTypeInquestName($values[$field]));
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'type':
                $options['value'] = $values[$field];
                $typeinquest = [
                    self::TYPE_INTERNAL => __('Internal survey'),
                    self::TYPE_EXTERNAL => __('External survey'),
                ];
                return Dropdown::showFromArray($name, $typeinquest, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getFormURLWithID($id = 0, $full = true)
    {

        $satisfaction = new static();
        if (!$satisfaction->getFromDB($id)) {
            return '';
        }

        $item = static::getItemInstance();
        return $item::getFormURLWithID($satisfaction->fields[$item::getForeignKeyField()]) . '&forcetab=' . $item::class . '$3';
    }

    public static function rawSearchOptionsToAdd()
    {
        global $DB;

        $base_id = static::getSearchOptionIDOffset();
        $table = static::getTable();

        $tab[] = [
            'id'                 => 'satisfaction',
            'name'               => __('Satisfaction survey'),
        ];

        $tab[] = [
            'id'                 => 31 + $base_id,
            'table'              => $table,
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'searchequalsonfield' => true,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => 60 + $base_id,
            'table'              => $table,
            'field'              => 'date_begin',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => 61 + $base_id,
            'table'              => $table,
            'field'              => 'date_answered',
            'name'               => __('Response date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => 62 + $base_id,
            'table'              => $table,
            'field'              => 'satisfaction',
            'name'               => __('Satisfaction'),
            'datatype'           => 'number',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'additionalfields' => ['TABLE.entities_id'],
        ];

        $tab[] = [
            'id'                 => 63 + $base_id,
            'table'              => $table,
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $sql = "WITH RECURSIVE entity_tree AS (
                SELECT
                    id,
                    entities_id,
                    inquest_duration
                FROM
                    glpi_entities
                WHERE
                    inquest_config != -2
                UNION ALL
                SELECT
                    e.id,
                    e.entities_id,
                    et.inquest_duration
                FROM
                    glpi_entities e
                INNER JOIN
                    entity_tree et
                    ON e.entities_id = et.id
                WHERE
                    e.inquest_config = -2
            )
            SELECT
                id AS entity_id,
                inquest_duration
            FROM
                entity_tree
        ";

        $subquery = new QueryExpression("($sql) AS durations");

        $tab[] = [
            'id'                 => 75 + $base_id,
            'table'              => $table,
            'field'              => 'inquest_duration',
            'name'               => __('End date'),
            'datatype'           => 'datetime',
            'maybefuture'        => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'usehaving'          => true,
            'nometa'             => true,
            'computation'        => QueryFunction::if(
                condition: new QueryExpression("EXISTS (SELECT 1 FROM $subquery WHERE durations.entity_id = glpi_entities.id AND durations.inquest_duration > 0)"),
                true_expression: QueryFunction::dateAdd(
                    date: "$table.date_begin",
                    interval: new QueryExpression("(SELECT durations.inquest_duration FROM $subquery WHERE durations.entity_id = glpi_entities.id)"),
                    interval_unit: 'DAY',
                ),
                false_expression: new QueryExpression($DB::quoteValue(''))
            ),
        ];

        return $tab;
    }
}
