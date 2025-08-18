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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\RichText\UserMention;

use function Safe\json_encode;

if (($_POST['action'] ?? null) === 'change_task_state') {
    header("Content-Type: application/json; charset=UTF-8");

    if (!isset($_POST['tasks_id'], $_POST['parenttype'])) {
        return;
    }

    $parent = getItemForItemtype($_POST['parenttype']);
    if (!($parent instanceof CommonITILObject)) {
        return;
    }

    $task = $parent::getTaskClassInstance();
    if (!$task->getFromDB((int) $_POST['tasks_id']) || !$task->canUpdateItem()) {
        throw new AccessDeniedHttpException();
    }
    if (!in_array($task->fields['state'], [0, Planning::INFO])) {
        $new_state = ($task->fields['state'] == Planning::DONE)
                        ? Planning::TODO
                        : Planning::DONE;
        $foreignKey = $parent::getForeignKeyField();
        $task->update([
            'id'        => (int) $_POST['tasks_id'],
            $foreignKey => (int) $_POST[$foreignKey],
            'state'     => $new_state,
            'users_id_editor' => Session::getLoginUserID(),
        ]);
        $new_label = Planning::getState($new_state);
        echo json_encode([
            'state'  => $task->fields['state'],
            'label'  => $new_label,
        ]);
    }
} elseif (($_REQUEST['action'] ?? null) === 'viewsubitem') {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
    if (!isset($_REQUEST['type'], $_REQUEST['parenttype'])) {
        throw new BadRequestHttpException();
    }

    $item = getItemForItemtype($_REQUEST['type']);

    if (!$item->canView()) {
        throw new AccessDeniedHttpException();
    }
    $parent = getItemForItemtype($_REQUEST['parenttype']);

    if (!$parent instanceof CommonITILObject) {
        throw new BadRequestHttpException();
    }

    if (
        isset($_REQUEST[$parent::getForeignKeyField()])
        && !$parent->can($_REQUEST[$parent::getForeignKeyField()], READ)
    ) {
        throw new AccessDeniedHttpException();
    }

    $id = isset($_REQUEST['id']) && (int) $_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
    if (!$item->can($id, READ)) {
        throw new AccessDeniedHttpException();
    }

    $twig = TemplateRenderer::getInstance();
    $template = null;
    if (isset($_REQUEST[$parent::getForeignKeyField()])) {
        $parent->getFromDB($_REQUEST[$parent::getForeignKeyField()]);
    }
    $id = isset($_REQUEST['id']) && (int) $_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
    if ($id) {
        $item->getFromDB($id);
    }

    $mention_options = UserMention::getMentionOptions($parent);

    $params = [
        'item'            => $parent,
        'subitem'         => $item,
        'mention_options' => $mention_options,
        'has_pending_reason' => PendingReason_Item::getForItem($parent) !== false,
    ];

    if ($_REQUEST['type'] === ITILFollowup::class) {
        $template = 'form_followup';
    } elseif ($_REQUEST['type'] === ITILSolution::class) {
        $template = 'form_solution';
    } elseif (is_subclass_of($_REQUEST['type'], CommonITILTask::class)) {
        $template = 'form_task';
    } elseif (is_subclass_of($_REQUEST['type'], CommonITILValidation::class)) {
        $template = 'form_validation';
        $params['form_mode'] = $_REQUEST['item_action'] === 'validation-answer' ? 'answer' : 'request';
    } elseif ($id !== null && $parent->getID() >= 0) {
        $ol = ObjectLock::isLocked($_REQUEST['parenttype'], $parent->getID());
        if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
            ObjectLock::setReadOnlyProfile();
        }
        $foreignKey = $parent->getForeignKeyField();
        $params[$foreignKey] = $_REQUEST[$foreignKey];
        $parent::showSubForm($item, $_REQUEST["id"], ['parent' => $parent, $foreignKey => $_REQUEST[$foreignKey]]);
        return;
    }
    if ($template === null) {
        throw new AccessDeniedHttpException();
    }
    $twig->display("components/itilobject/timeline/{$template}.html.twig", $params);
}
