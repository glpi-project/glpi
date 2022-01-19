<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;

/* Test for inc/notificationtemplatetranslation.class.php */

class NotificationTemplateTranslation extends DbTestCase
{
    public function testClone()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => \NotificationTemplateTranslation::getTable(),
            'LIMIT'  => 1
        ]);

        $data = $iterator->current();
        $translation = new \NotificationTemplateTranslation();
        $translation->getFromDB($data['id']);
        $added = $translation->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedTranslation = new \NotificationTemplateTranslation();
        $this->boolean($clonedTranslation->getFromDB($added))->isTrue();

        unset($translation->fields['id']);
        unset($clonedTranslation->fields['id']);

        $this->array($translation->fields)->isIdenticalTo($clonedTranslation->fields);
    }

    public function testCloneFromTemplate()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'id',
                'notificationtemplates_id'
            ],
            'FROM'   => \NotificationTemplateTranslation::getTable(),
            'LIMIT'  => 1
        ]);

        $data = $iterator->current();
        $template = new \NotificationTemplate();
        $template->getFromDB($data['notificationtemplates_id']);
        $added = $template->clone();

        $translations = $DB->request([
            'FROM'   => \NotificationTemplateTranslation::getTable(),
            'WHERE'  => ['notificationtemplates_id' => $data['notificationtemplates_id']]
        ]);

        $clonedTranslations = $DB->request([
            'FROM'   => \NotificationTemplateTranslation::getTable(),
            'WHERE'  => ['notificationtemplates_id' => $added]
        ]);

        $this->integer(count($translations))->isIdenticalTo(count($clonedTranslations));
    }
}
