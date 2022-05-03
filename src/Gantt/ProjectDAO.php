<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Gantt;

/**
 * DAO class for handling project records
 */
class ProjectDAO
{
    public function addProject($project)
    {

        if (!\Project::canCreate()) {
            throw new \Exception(__('Not enough rights'));
        }

        $input = [
            'name' => $project->text,
            'comment' => $project->comment,
            'projects_id' => $project->parent,
            'date' => $_SESSION['glpi_currenttime'],
            'plan_start_date' => $project->start_date,
            'plan_end_date' => $project->end_date,
            'priority' => 3,  //medium
            'projectstates_id' => 1,
            'users_id' => \Session::getLoginUserID(),
            'show_on_global_gantt' => 1
        ];
        $proj = new \Project();
        $proj->add($input);
        return $proj;
    }

    public function updateProject($project)
    {
        $p = new \Project();
        $p->getFromDB($project->id);

        if (!$p::canUpdate() || !$p->canUpdateItem()) {
            throw new \Exception(__('Not enough rights'));
        }

        $p->update([
            'id' => $project->id,
            'percent_done' => ($project->progress * 100),
            'name' => $project->text
        ]);
        return true;
    }

    public function updateParent($project)
    {
        $p = new \Project();
        $p->getFromDB($project->id);

        if (!$p::canUpdate() || !$p->canUpdateItem()) {
            throw new \Exception(__('Not enough rights'));
        }

        $input = [
            'id' => $project->id,
            'projects_id' => $project->parent
        ];
        $p->update($input);
    }
}
