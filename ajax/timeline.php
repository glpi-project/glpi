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

include('../inc/includes.php');

Session::checkLoginUser();

if (($_POST['action'] ?? null) === 'change_task_state') {
    header("Content-Type: application/json; charset=UTF-8");

    if (
        !isset($_POST['tasks_id'])
        || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false
    ) {
        exit();
    }

    $taskClass = $parent->getType() . "Task";
    $task = new $taskClass();
    $task->getFromDB(intval($_POST['tasks_id']));
    if (!in_array($task->fields['state'], [0, Planning::INFO])) {
        $new_state = ($task->fields['state'] == Planning::DONE)
                        ? Planning::TODO
                        : Planning::DONE;
        $foreignKey = $parent->getForeignKeyField();
        $task->update([
            'id'        => intval($_POST['tasks_id']),
            $foreignKey => intval($_POST[$foreignKey]),
            'state'     => $new_state,
            'users_id_editor' => Session::getLoginUserID()
        ]);
        $new_label = Planning::getState($new_state);
        echo json_encode([
            'state'  => $task->fields['state'],
            'label'  => $new_label
        ]);
    }
} else if (($_REQUEST['action'] ?? null) === 'viewsubitem') {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
    if (!isset($_REQUEST['type'])) {
        exit();
    }
    if (!isset($_REQUEST['parenttype'])) {
        exit();
    }

    $item = getItemForItemtype($_REQUEST['type']);
    $parent = getItemForItemtype($_REQUEST['parenttype']);

    $twig = TemplateRenderer::getInstance();
    $template = null;
    if (isset($_REQUEST[$parent::getForeignKeyField()])) {
        $parent->getFromDB($_REQUEST[$parent::getForeignKeyField()]);
    }
    $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
    if ($id) {
        $item->getFromDB($id);
    }
    $params = [
        'item'      => $parent,
        'subitem'   => $item
    ];

    if ($_REQUEST['type'] === ITILFollowup::class) {
        $template = 'form_followup';
    } else if ($_REQUEST['type'] === ITILSolution::class) {
        $template = 'form_solution';
        $params['kb_id_toload'] = $_REQUEST['load_kb_sol'] ?? 0;
    } else if (is_subclass_of($_REQUEST['type'], CommonITILTask::class)) {
        $template = 'form_task';
    } else if (is_subclass_of($_REQUEST['type'], CommonITILValidation::class)) {
        $template = 'form_validation';
        $params['form_mode'] = $_REQUEST['item_action'] === 'validation-answer' ? 'answer' : 'request';
    } else if ($id !== null && $parent->getID() >= 0) {
        $ol = ObjectLock::isLocked($_REQUEST['parenttype'], $parent->getID());
        if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
            ObjectLock::setReadOnlyProfile();
        }
        $foreignKey = $parent->getForeignKeyField();
        $params[$foreignKey] = $_REQUEST[$foreignKey];
        $parent::showSubForm($item, $_REQUEST["id"], ['parent' => $parent, $foreignKey => $_REQUEST[$foreignKey]]);
        Html::ajaxFooter();
        exit();
    }
    if ($template === null) {
        echo __('Access denied');
        Html::ajaxFooter();
        exit();
    }
    $twig->display("components/itilobject/timeline/{$template}.html.twig", $params);
}
