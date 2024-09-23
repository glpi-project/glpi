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

namespace Glpi\Discover;

use CommonDBTM;
use Session;

class Discover_User extends CommonDBTM
{
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'users_id',
            'name'               => __('User ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        return $tab;
    }

    public static function canView(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canDelete(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
     * Get the discover object for the given user
     *
     * @param int $user_id
     * @return Discover_User
     */
    public static function getForUser(int $user_id): Discover_User
    {
        $discover = new self();
        if (
            !$discover->getFromDBByCrit([
                'users_id' => $user_id
            ])
        ) {
            $discover->getFromDB($discover->add([
                'users_id'    => $user_id,
                'progression' => '{}'
            ]));
        }

        return $discover;
    }

    /**
     * Get the completed lessons for the user
     *
     * @return array
     */
    public function getCompletedLessons(): array
    {
        return json_decode($this->fields['progression'] ?? '{}', true)['done'] ?? [];
    }


    /**
     * Set the lesson as completed for the user
     *
     * @param string $lesson_id
     * @return bool
     */
    public function setLessonCompleted(string $lesson_id): bool
    {
        // Ending lesson can't be completed
        if ($lesson_id === $this->getEndingLesson()['id']) {
            return false;
        }

        $progression = json_decode($this->fields['progression'] ?? '{}', true);
        if (!in_array($lesson_id, $progression['done'] ?? [])) {
            $progression['done'][] = $lesson_id;
        }

        return $this->update([
            'id' => $this->getID(),
            'progression' => json_encode($progression)
        ]);
    }

    /**
     * Set the lesson as uncompleted for the user
     *
     * @param string $lesson_id
     * @return bool
     */
    public function setLessonUncompleted(string $lesson_id): bool
    {
        $progression = json_decode($this->fields['progression'] ?? '{}', true);
        $progression['done'] = array_filter($progression['done'] ?? [], function ($done_lesson_id) use ($lesson_id) {
            return $done_lesson_id !== $lesson_id;
        });

        // Reindex the array
        $progression['done'] = array_values($progression['done']);

        return $this->update([
            'id' => $this->getID(),
            'progression' => json_encode($progression)
        ]);
    }

    /**
     * Check if the user has completed the given lesson
     *
     * @param string $lesson_id
     * @return bool
     */
    public function hasCompletedLesson(string $lesson_id): bool
    {
        return in_array($lesson_id, $this->getCompletedLessons());
    }

    /**
     * Load the config file and replace the content of the steps with the translated version
     *
     * @return array
     */
    public function getLocalizedConfig(): array
    {
        $discoverConfig = [];

        $discoverConfig = require GLPI_ROOT . '/resources/Lessons/config.php';

        array_walk_recursive($discoverConfig, function (&$value, $key) {
            if ($key === 'content' && str_starts_with($value, 'file://./sources/')) {
                $filepath = GLPI_ROOT . '/resources/Lessons/translated/' . Session::getLanguage() . '/' . substr($value, 17);

                // If the file doesn't exist in the user language, we fallback to english
                if (!file_exists($filepath)) {
                    $filepath = GLPI_ROOT . '/resources/Lessons/translated/en_GB/' . substr($value, 17);
                }

                // If the file doesn't exist in english, we fallback to the original file
                if (!file_exists($filepath)) {
                    $filepath = GLPI_ROOT . '/resources/Lessons/sources/' . substr($value, 17);
                }

                $value = file_get_contents($filepath);
            }
        });

        return $discoverConfig;
    }

    /**
     * Get the starting lesson for the user
     *
     * @return array|null
     */
    public function getStartingLesson(): array|null
    {
        $config = $this->getLocalizedConfig();
        $startingLesson = $config['startingLesson'] ?? null;
        if (!$startingLesson || $this->hasCompletedLesson($startingLesson)) {
            return null;
        }

        return $this->getLesson($startingLesson);
    }

    /**
     * Get the ending lesson for the user
     *
     * @return array|null
     */
    public function getEndingLesson(): array|null
    {
        $config = $this->getLocalizedConfig();
        $endingLesson = $config['endingLesson'] ?? null;
        if (!$endingLesson || $this->hasCompletedLesson($endingLesson)) {
            return null;
        }

        return $this->getLesson($endingLesson);
    }

    /**
     * Get the lesson with the given id
     *
     * @param string|null $lesson_id
     * @return array|null
     */
    public function getLesson(string $lesson_id = null): array|null
    {
        // If the user isn't a super-admin, we don't show the discover
        if (!Session::haveRight('config', UPDATE)) {
            return null;
        }

        $lesson = current(array_filter($this->getLessons(), function ($lesson) use ($lesson_id) {
            return $lesson['id'] === $lesson_id;
        }));

        if (!$lesson) {
            return null;
        }

        return $lesson;
    }

    /**
     * Get the progression of the user for the given lesson
     *
     * @param string $lesson_id
     * @return int
     */
    public function getLessonProgression(string $lesson_id): int
    {
        if ($this->hasCompletedLesson($lesson_id)) {
            return count($this->getLesson($lesson_id)['steps']);
        }

        return 0;
    }

    /**
     * Get the lesson to start for the user
     *
     * @return array|null
     */
    public function getLessonToStart(): array|null
    {
        if (isset($_SESSION['glpidiscover']) && isset($_SESSION['glpidiscover']['start']) && count($_SESSION['glpidiscover']['start']) > 0) {
            $lesson_id = array_shift($_SESSION['glpidiscover']['start']);
            return $this->getLesson($lesson_id);
        }

        return $this->getStartingLesson();
    }

    /**
     * Get all the lessons from the config file
     *
     * @return array
     */
    public function getLessons(): array
    {
        return array_map(function ($lesson) {
            // Rename some keys to match the IntroJs API
            $lesson['steps'] = array_map(function ($step) {
                $step['intro'] = $step['content'];
                unset($step['content']);
                return $step;
            }, $lesson['steps']);

            return $lesson;
        }, $this->getLocalizedConfig()['lessons']);
    }
}
