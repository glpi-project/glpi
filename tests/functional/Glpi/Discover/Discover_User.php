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

namespace tests\units\Glpi\Discover;

use DbTestCase;

class Discover_User extends DbTestCase
{
    /**
     * Returns a mock instance of the Discover_User class with a localized configuration for testing purposes.
     *
     * @return \mock\Discover\Discover_User
     */
    public function getDiscoverUser(): \mock\Discover\Discover_User
    {
        $user_id = $this->login()->user->getID();
        $discover_user = \Glpi\Discover\Discover_User::getForUser($user_id);

        $this->mockGenerator->generate('Glpi\Discover\Discover_User', '\mock\Discover');

        $discover_userMock = new \mock\Discover\Discover_User();
        $discover_userMock->fields = $discover_user->fields;

        $this->calling($discover_userMock)->getLocalizedConfig = function () {
            return [
                "version" => "0.0.1",
                "startingLesson" => "test",
                "endingLesson" => "test3",
                "categories" => [
                    "test" => [
                        "title" => __("Test"),
                        "description" => __("This category contains the test lessons"),
                    ]
                ],
                "lessons" => [
                    [
                        "id" => "test",
                        "title" => __("Test"),
                        "points" => 10,
                        "steps" => [
                            [
                                "title" => __("First step of test"),
                                "content" => "test content",
                                "tooltipClass" => "large-tooltip",
                            ],
                            [
                                "title" => __("Second step of test"),
                                "content" => "test content",
                            ]
                        ]
                    ],
                    [
                        "id" => "test2",
                        "title" => __("Test2"),
                        "points" => 10,
                        "category" => "test",
                        "navigateTo" => "front/central.php",
                        "steps" => [
                            [
                                "title" => __("First step of test2"),
                                "content" => "test content",
                                "tooltipClass" => "large-tooltip",
                            ],
                            [
                                "title" => __("Second step of test2"),
                                "content" => "test content",
                            ]
                        ]
                    ],
                    [
                        "id" => "test3",
                        "title" => __("Test3"),
                        "points" => 10,
                        "steps" => [
                            [
                                "title" => __("First step of test3"),
                                "content" => "test content",
                                "tooltipClass" => "large-tooltip",
                            ],
                            [
                                "title" => __("Second step of test3"),
                                "content" => "test content",
                            ]
                        ]
                    ]
                ]
            ];
        };

        return $discover_userMock;
    }

    /**
     * Test case for the getForUser() method of the Discover_User class.
     *
     * It checks that the user has an ID greater than 0 and that has no progression.
     *
     * @return void
     */
    public function testGetForUser()
    {
        $discover_user = $this->getDiscoverUser();

        $this->integer($discover_user->getID())->isGreaterThan(0);
        $this->array(json_decode($discover_user->fields['progression'], true))->isEmpty();
    }

    /**
     * Test case for the getCompletedLessons method of the Discover_User class.
     *
     * It checks that the user has no completed lessons, completes a lesson, completes another lesson,
     * uncompletes a lesson, and uncompletes another lesson.
     *
     * @return void
     */
    public function testGetCompletedLessons()
    {
        $discover_user = $this->getDiscoverUser();

        // Check that the user has no completed lessons
        $this->array($discover_user->getCompletedLessons())->isEmpty();

        // Complete a lesson
        $discover_user->setLessonCompleted('test');
        $this->array($discover_user->getCompletedLessons())->isEqualTo(['test']);

        // Complete another lesson
        $discover_user->setLessonCompleted('test2');
        $this->array($discover_user->getCompletedLessons())->isEqualTo(['test', 'test2']);

        // Uncomplete a lesson
        $this->boolean($discover_user->setLessonUncompleted('test'))->isTrue();
        $this->array($discover_user->getCompletedLessons())->isEqualTo(['test2']);

        // Uncomplete another lesson
        $this->boolean($discover_user->setLessonUncompleted('test2'))->isTrue();
        $this->array($discover_user->getCompletedLessons())->isEmpty();
    }

    /**
     * Test case for the hasCompletedLesson method of the Discover_User class.
     *
     * @return void
     */
    public function testHasCompletedLesson()
    {
        $discover_user = $this->getDiscoverUser();

        // Check that the user has no completed lessons
        $this->boolean($discover_user->hasCompletedLesson('test'))->isFalse();

        // Complete a lesson
        $discover_user->setLessonCompleted('test');
        $this->boolean($discover_user->hasCompletedLesson('test'))->isTrue();

        // Complete another lesson
        $discover_user->setLessonCompleted('test2');
        $this->boolean($discover_user->hasCompletedLesson('test2'))->isTrue();

        // Uncomplete a lesson
        $discover_user->setLessonUncompleted('test');
        $this->boolean($discover_user->hasCompletedLesson('test'))->isFalse();

        // Uncomplete another lesson
        $discover_user->setLessonUncompleted('test2');
        $this->boolean($discover_user->hasCompletedLesson('test2'))->isFalse();
    }

    public function testGetStartingLesson()
    {
        $discover_user = $this->getDiscoverUser();

        // Check if it's the right starting lesson
        $this->array($discover_user->getStartingLesson())->hasSize(4);
        $this->array($discover_user->getStartingLesson())->hasKeys(['id', 'title', 'points', 'steps']);
        $this->array($discover_user->getStartingLesson())->hasKey('id', 'test');

        // Complete the starting lesson
        $this->boolean($discover_user->setLessonCompleted('test'))->isTrue();
        $this->variable($discover_user->getStartingLesson())->isNull();
    }

    public function testGetEndingLesson()
    {
        $discover_user = $this->getDiscoverUser();

        // Check if it's the right ending lesson
        $this->array($discover_user->getEndingLesson())->hasSize(4);
        $this->array($discover_user->getEndingLesson())->hasKeys(['id', 'title', 'points', 'steps']);
        $this->array($discover_user->getEndingLesson())->hasKey('id', 'test3');

        // Complete the ending lesson
        $this->boolean($discover_user->setLessonCompleted('test3'))->isFalse();
        $this->boolean($discover_user->hasCompletedLesson('test3'))->isFalse();
    }

    /**
     * Test case for the getLesson method of the Discover_User class.
     *
     * @return void
     */
    public function testGetLesson()
    {
        $discover_user = $this->getDiscoverUser();

        // Check if it's the right lesson
        $this->array($discover_user->getLesson('test2'))->hasSize(6);
        $this->array($discover_user->getLesson('test2'))->hasKeys(['id', 'title', 'points', 'steps', 'category', 'navigateTo']);
        $this->array($discover_user->getLesson('test2'))->hasKey('id', 'test2');
    }

    /**
     * Test case for the getLessons method of the Discover_User class.
     *
     * @return void
     */
    public function testGetLessons()
    {
        $discover_user = $this->getDiscoverUser();

        // Check if it's the right lessons
        $this->array($discover_user->getLessons())->hasSize(3);
        $this->array($discover_user->getLessons()[0])->hasKey('id', 'test');
        $this->array($discover_user->getLessons()[1])->hasKey('id', 'test2');
        $this->array($discover_user->getLessons()[2])->hasKey('id', 'test3');
    }

    /**
     * Test case for the getLessonProgression method of the Discover_User class.
     *
     * @return void
     */
    public function testGetLessonProgression()
    {
        $discover_user = $this->getDiscoverUser();

        // Initially, the progression for 'test' lesson should be 0
        $this->integer($discover_user->getLessonProgression('test'))->isEqualTo(0);

        // Complete 'test' lesson
        $discover_user->setLessonCompleted('test');
        $this->integer($discover_user->getLessonProgression('test'))->isEqualTo(2); // All steps completed

        // Complete 'test2' lesson
        $discover_user->setLessonCompleted('test2');
        $this->integer($discover_user->getLessonProgression('test2'))->isEqualTo(2); // All steps completed

        // Uncomplete 'test' lesson
        $discover_user->setLessonUncompleted('test');
        $this->integer($discover_user->getLessonProgression('test'))->isEqualTo(0); // No steps completed

        // Uncomplete 'test2' lesson
        $discover_user->setLessonUncompleted('test2');
        $this->integer($discover_user->getLessonProgression('test2'))->isEqualTo(0); // No steps completed
    }
}
