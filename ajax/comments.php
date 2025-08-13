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

global $CFG_GLPI;

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

use Glpi\Application\View\TemplateRenderer;

if (
    isset($_POST["itemtype"])
    && isset($_POST["value"])
) {
    // Security
    if (!is_subclass_of($_POST["itemtype"], "CommonDBTM")) {
        return;
    }

    switch ($_POST["itemtype"]) {
        case User::getType():
            $link = null;
            $comments = [];
            if ($_POST['value'] == 0) {
                $link = $CFG_GLPI['root_doc'] . "/front/user.php";
            } else {
                $user = new User();
                if (is_array($_POST["value"])) {
                    foreach ($_POST["value"] as $users_id) {
                        if ($user->getFromDB($users_id) && $user->canView()) {
                            $comments[] = $user->getInfoCard();
                        }
                    }
                    unset($_POST['withlink']);
                } else {
                    if ($user->getFromDB($_POST['value']) && $user->canView()) {
                        $link = $user->getLinkURL();
                        $comments[] = $user->getInfoCard();
                    }
                }
            }

            echo(implode("<br>", $comments));

            if (isset($_POST['withlink']) && $link !== null) {
                echo Html::scriptBlock(
                    sprintf(
                        '$("#%s").attr("href", "%s");',
                        jsescape($_POST['withlink']),
                        jsescape($link)
                    )
                );
            }
            break;

        case Group::getType():
            if ($_POST['value'] != 0) {
                $group = new Group();
                if (!is_array($_POST["value"]) && $group->getFromDB($_POST['value']) && $group->canView()) {
                    $group_params = [
                        'id' => $group->getID(),
                        'group_name' => $group->fields['completename'],
                        'comment' => $group->fields['comment'],
                    ];
                    TemplateRenderer::getInstance()->display('components/group/info_card.html.twig', [
                        'group' => $group_params,
                    ]);
                }
            }
            break;

        case Supplier::getType():
            $tmpname = [
                'comment' => "",
            ];
            if ($_POST['value'] != 0) {
                $supplier = new Supplier();
                if (!is_array($_POST["value"]) && $supplier->getFromDB($_POST['value']) && $supplier->canView()) {
                    $supplier_params = [
                        'id' => $supplier->getID(),
                        'supplier_name' => $supplier->fields['name'],
                        'comment' => $supplier->fields['comment'],
                        'address' => $supplier->fields['address'],
                        'postcode' => $supplier->fields['postcode'],
                        'town' => $supplier->fields['town'],
                        'email' => $supplier->fields['email'],
                        'fax' => $supplier->fields['fax'],
                        'registration_number' => $supplier->fields['registration_number'],
                        'phonenumber' => $supplier->fields['phonenumber'],
                    ];
                    $comment = TemplateRenderer::getInstance()->render('components/supplier/info_card.html.twig', [
                        'supplier' => $supplier_params,
                    ]);
                    $tmpname = [
                        'comment' => $comment,
                    ];
                }
            }
            echo($tmpname["comment"]);
            break;

        default:
            if ($_POST["value"] > 0) {
                if (
                    !Session::validateIDOR([
                        'itemtype'    => $_POST['itemtype'],
                        '_idor_token' => $_POST['_idor_token'] ?? "",
                    ])
                ) {
                    return;
                }

                $itemtype = $_POST['itemtype'];
                if (is_subclass_of($itemtype, 'Rule')) {
                    $table = Rule::getTable();
                } else {
                    $table = getTableForItemType($_POST['itemtype']);
                }

                echo Dropdown::getDropdownComments($table, (int) $_POST["value"]);

                if (isset($_POST['withlink'])) {
                    echo Html::scriptBlock(
                        sprintf(
                            '$("#%s").attr("href", "%s");',
                            jsescape($_POST['withlink']),
                            jsescape($_POST['itemtype']::getFormURLWithID($_POST["value"]))
                        )
                    );
                }

                if (isset($_POST['with_dc_position'])) {
                    $item = getItemForItemtype($_POST['itemtype']);

                    //if item have a DC position (reload url to it's rack)
                    if (
                        method_exists($item, 'getParentRack')
                        && ($rack = $item->getParentRack())
                    ) {
                        echo Html::scriptBlock(
                            sprintf(
                                '$("#%s").html("href", "&nbsp;<a class=\'ti ti-crosshairs\' href=\'%s\'></a>");',
                                jsescape($_POST['with_dc_position']),
                                jsescape(htmlescape($rack->getLinkURL()))
                            )
                        );
                    } else {
                        //remove old dc position
                        echo Html::scriptBlock(
                            sprintf(
                                '$("#%s").empty();',
                                jsescape($_POST['with_dc_position'])
                            )
                        );
                    }
                }
            }
    }
}
