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

namespace tests\units\Glpi\Form\Tag;

use GLPITestCase;
use Glpi\Form\Tag\Tag;

final class FormTagsManager extends GLPITestCase
{
    public function testGetTags()
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $tags = $tag_manager->getTags();
        $this->array($tags)->isNotEmpty();

        foreach ($tags as $tag) {
            $this->object($tag)->isInstanceOf(Tag::class);
        }
    }

    public function testInsertTagsContent()
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $tag_1 = new Tag(label: "Exemple tag 1", value: "exemple-tag-1");
        $tag_2 = new Tag(label: "Exemple tag 2", value: "exemple-tag-2");
        $tag_3 = new Tag(label: "Exemple tag 3", value: "exemple-tag-3");

        $content_with_tag =
            "My content: $tag_1->html, $tag_2->html and $tag_3->html"
        ;
        $computed_content = $tag_manager->insertTagsContent($content_with_tag);
        $this->string($computed_content)->isEqualTo(
            'My content: exemple-tag-1, exemple-tag-2 and exemple-tag-3'
        );
    }
}
