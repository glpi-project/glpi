<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 * Following variables have to be defined before inclusion of this file:
 * @var string|CommonITILObject $itemtype
 * @var string|CommonITILSatisfaction $inquest_itemtype
 */

$ent = new Entity();
// Get suffix for entity config fields. For backwards compatibility, ticket values have no suffix.
$config_suffix = $itemtype::getType() === 'Ticket' ? '' : ('_' . strtolower($itemtype::getType()));

if (isset($_POST['inquest_config' . $config_suffix]) && isset($_POST['entities_id'])) {
    if ($ent->getFromDB($_POST['entities_id'])) {
        $inquest_delay             = $ent->getfield('inquest_delay' . $config_suffix);
        $inquest_rate              = $ent->getfield('inquest_rate' . $config_suffix);
        $inquest_duration          = $ent->getfield('inquest_duration' . $config_suffix);
        $inquest_max_rate          = $ent->getfield('inquest_max_rate' . $config_suffix);
        $inquest_default_rate      = $ent->getfield('inquest_default_rate' . $config_suffix);
        $inquest_mandatory_comment = $ent->getfield('inquest_mandatory_comment' . $config_suffix);
        $max_closedate             = $ent->getfield('max_closedate' . $config_suffix);
    } else {
        $inquest_delay             = -1;
        $inquest_rate              = -1;
        $inquest_default_rate      = 3;
        $inquest_max_rate          = 5;
        $inquest_mandatory_comment = 0;
        $max_closedate             = '';
    }

    if ($_POST['inquest_config' . $config_suffix] > 0) {
        echo "<table class='tab_cadre_fixe' width='50%'>";
        echo "<tr class='tab_bg_1'><td width='50%'>" . __('Create survey after') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_delay' . $config_suffix,
            ['value' => $inquest_delay,
                'min'   => 1,
                'max'   => 90,
                'step'  => 1,
                'toadd' => ['0' => __('As soon as possible')],
                'unit'  => 'day'
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>" .
            "<td>" . __('Rate to trigger survey') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_rate' . $config_suffix,
            [
                'value'   => $inquest_rate,
                'min'     => 10,
                'max'     => 100,
                'step'    => 10,
                'toadd'   => [0 => __('Disabled')],
                'unit'    => '%'
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1' data-field='duration{$config_suffix}'><td width='50%'>" . __('Duration of survey') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_duration' . $config_suffix,
            [
                'value' => $inquest_duration,
                'min'   => 1,
                'max'   => 180,
                'step'  => 1,
                'toadd' => ['0' => __('Unspecified')],
                'unit'  => 'day'
            ]
        );
        echo "</td></tr>";
        echo "<tr class='tab_bg_1' data-field='max_rate{$config_suffix}'><td width='50%'>" . __('Max rate') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_max_rate' . $config_suffix,
            [
                'value' => $inquest_max_rate,
                'min'   => 1,
                'max'   => 10,
                'step'  => 1,
                'unit'  => ''
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1' data-field='default_rate{$config_suffix}'><td width='50%'>" . __('Default rate') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_default_rate' . $config_suffix,
            [
                'value' => $inquest_default_rate,
                'min'   => 1,
                'max'   => 10,
                'step'  => 1,
                'unit'  => ''
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1' data-field='mandatory_comment{$config_suffix}'><td width='50%'>" . __('Comment required if score is <= to') . "</td>";
        echo "<td>";
        Dropdown::showNumber(
            'inquest_mandatory_comment' . $config_suffix,
            [
                'value' => $inquest_mandatory_comment,
                'min'   => 1,
                'max'   => 10,
                'step'  => 1,
                'toadd' => ['0' => __('Disabled')],
                'unit'  => ''
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1' data-field='max_closedate{$config_suffix}'><td>" . sprintf(__('For %s closed after'), $itemtype::getTypeName(Session::getPluralNumber())) . "</td><td>";
        Html::showDateTimeField(
            "max_closedate" . $config_suffix,
            [
                'value'      => $max_closedate,
                'timestep'   => 1,
                'maybeempty' => false,
            ]
        );
        echo "</td></tr>";

        $tag_prefix = strtoupper($itemtype::getType());
        $ticket_only_tags = "[REQUESTTYPE_ID] [REQUESTTYPE_NAME] [TICKETTYPE_NAME] [TICKETTYPE_ID] ";
        $ticket_only_tags .= "[SLA_TTO_ID] [SLA_TTO_NAME] [SLA_TTR_ID] [SLA_TTR_NAME] [SLALEVEL_ID] [SLALEVEL_NAME]";

        if ($_POST['inquest_config' . $config_suffix] == 2) {
            echo "<tr class='tab_bg_1' data-field='url{$config_suffix}'>";
            echo "<td>" . __('Valid tags') . "</td><td>" .
                "[{$tag_prefix}_ID] [{$tag_prefix}_NAME] [{$tag_prefix}_CREATEDATE] [{$tag_prefix}_SOLVEDATE] " .
                "[{$tag_prefix}_PRIORITY] [{$tag_prefix}_PRIORITYNAME]  " .
                "[ITILCATEGORY_ID] [ITILCATEGORY_NAME] " .
                "[SOLUTIONTYPE_ID] [SOLUTIONTYPE_NAME] " .
                ($itemtype === 'Ticket' ? (' ' . $ticket_only_tags) : '') .
                "</td></tr>";

            echo "<tr class='tab_bg_1'><td>" . __('URL') . "</td>";
            echo "<td>";
            echo Html::input('inquest_URL' . $config_suffix, ['value' => $ent->fields['inquest_URL' . $config_suffix]]);
            echo "</td></tr>";
        }

        echo "</table>";
        $js = <<<JS
            $(document).ready(() => {
                const rate_dropdown = $('select[name="inquest_rate{$config_suffix}"]');

                const refresh_param_rows = () => {
                    const param_rows = [
                        $('tr[data-field="duration{$config_suffix}"]'),
                        $('tr[data-field="max_rate{$config_suffix}"]'),
                        $('tr[data-field="default_rate{$config_suffix}"]'),
                        $('tr[data-field="mandatory_comment{$config_suffix}"]'),
                        $('tr[data-field="max_closedate{$config_suffix}"]'),
                        $('tr[data-field="url{$config_suffix}"]')
                    ];
                    if (rate_dropdown.val() == 0) {
                        // Hide all param rows if they exist
                        param_rows.forEach(row => {
                            if (row.length > 0) {
                                row.hide();
                            }
                        });
                    } else {
                        // Show all param rows if they exist
                        param_rows.forEach(row => {
                            if (row.length > 0) {
                                row.show();
                            }
                        });
                    }
                };

                $(rate_dropdown).on('change', () => {
                    refresh_param_rows();
                });
                refresh_param_rows();
            });
JS;
        echo Html::scriptBlock($js);
    }
}
