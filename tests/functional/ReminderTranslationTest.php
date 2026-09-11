<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units;

use Glpi\Tests\DbTestCase;

class ReminderTranslationTest extends DbTestCase
{
    public function testGetTranslationForReminder()
    {

        $this->login();
        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $data = [
            'name'         => '_test_reminder01',
            'entities_id'  => 0,
        ];

        $reminder = new \Reminder();
        $added = $reminder->add($data);
        $this->assertGreaterThan(0, (int) $added);

        $reminder1 = getItemByTypeName(\Reminder::getType(), '_test_reminder01');

        //first, set data
        $text_orig = 'Translation 1 for Reminder1';
        $text_fr = 'Traduction 1 pour Note1';
        $this->addTranslation($reminder1, $text_orig);
        $this->addTranslation($reminder1, $text_fr, 'fr_FR');

        $nb = countElementsInTable(
            'glpi_remindertranslations'
        );
        $this->assertSame(2, $nb);

        // second, test what we retrieve
        $current_lang = $_SESSION['glpilanguage'];
        $_SESSION['glpilanguage'] = 'fr_FR';
        $text = \ReminderTranslation::getTranslatedValue($reminder1, "text");
        $_SESSION['glpilanguage'] = $current_lang;
        $this->assertSame($text_fr, $text);
    }

    public function testPlanningUsesTranslation(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $reminder = new \Reminder();
        $reminders_id = (int) $reminder->add([
            'name'        => '_test_planned_reminder',
            'text'        => '<p>Original text</p>',
            'entities_id' => 0,
            'plan'        => [
                'begin' => '2025-01-15 10:00:00',
                'end'   => '2025-01-15 11:00:00',
            ],
        ]);
        $this->assertGreaterThan(0, $reminders_id);

        $translation = new \ReminderTranslation();
        $this->assertGreaterThan(0, (int) $translation->add([
            'reminders_id' => $reminders_id,
            'users_id'     => \Session::getLoginUserID(),
            'language'     => 'ja_JP',
            'name'         => 'Translated title',
            'text'         => '<p>Translated text</p>',
        ]));

        $get_event = function () use ($reminders_id): array {
            $events = \Reminder::populatePlanning([
                'who'      => \Session::getLoginUserID(),
                'whogroup' => 0,
                'begin'    => '2025-01-15 00:00:00',
                'end'      => '2025-01-16 00:00:00',
            ]);
            foreach ($events as $event) {
                if ((int) $event['reminders_id'] === $reminders_id) {
                    return $event;
                }
            }
            $this->fail('Reminder not found in planning');
        };

        $current_lang = $_SESSION['glpilanguage'];

        // No translation for the current language, the original values are used
        $_SESSION['glpilanguage'] = 'en_GB';
        $event = $get_event();
        $this->assertSame('_test_planned_reminder', $event['name']);
        $this->assertStringContainsString('Original text', $event['text']);

        // The translation for the current language is used
        $_SESSION['glpilanguage'] = 'ja_JP';
        $event = $get_event();
        $this->assertSame('Translated title', $event['name']);
        $this->assertStringContainsString('Translated text', $event['text']);

        // With several rows for the same reminder and language, the planning must
        // pick the same one as `getTranslatedValue()` (the first, ordered by id).
        $this->assertGreaterThan(0, (int) (new \ReminderTranslation())->add([
            'reminders_id' => $reminders_id,
            'users_id'     => \Session::getLoginUserID(),
            'language'     => 'ja_JP',
            'name'         => 'Second translated title',
            'text'         => '<p>Second translated text</p>',
        ]));

        $reminder1 = new \Reminder();
        $this->assertTrue($reminder1->getFromDB($reminders_id));

        $event = $get_event();
        // Read the single-item value under the same language before restoring it.
        $single_value = \ReminderTranslation::getTranslatedValue($reminder1, 'name');
        $_SESSION['glpilanguage'] = $current_lang;
        $this->assertSame($single_value, $event['name']);
        $this->assertSame('Translated title', $event['name']);
    }

    /**
     * Add translation into database
     *
     * @param \Reminder $reminder
     * @param string    $name Reminder name
     * @param string    $lang Reminder language, defaults to null
     *
     * @return void
     */
    private function addTranslation(\Reminder $reminder, $text, $lang = 'NULL')
    {
        $this->login();
        $trans = new \ReminderTranslation();

        $input = [
            'reminders_id' => $reminder->getID(),
            'users_id'     => getItemByTypeName('User', TU_USER, true),
            'text'         => $text,
            'language'     => $lang,
        ];
        $this->assertGreaterThan(0, (int) $trans->add($input));
    }
}
