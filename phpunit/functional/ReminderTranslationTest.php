<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/ReminderTranslation.class.php */

/**
 * @engine isolate
 */
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
