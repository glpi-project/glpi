<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace test\units;

use \DbTestCase;

/* Test for inc/knowbase.class.php */

class Knowbase extends DbTestCase {

   public function testGetJstreeCategoryList() {

      // Create empty categories
      $kbcat = new \KnowbaseItemCategory();

      $cat_1_id = $kbcat->add(
         [
            'name' => 'cat 1',
            'knowbaseitemcategories_id' => 0,
            'level' => 1,
         ]
      );
      $this->integer($cat_1_id)->isGreaterThan(0);

      $cat_1_1_id = $kbcat->add(
         [
            'name' => 'cat 1.1',
            'knowbaseitemcategories_id' => $cat_1_id,
            'level' => 2,
         ]
      );
      $this->integer($cat_1_1_id)->isGreaterThan(0);

      $cat_1_1_1_id = $kbcat->add(
         [
            'name' => 'cat 1.1.1',
            'knowbaseitemcategories_id' => $cat_1_1_id,
            'level' => 3,
         ]
      );
      $this->integer($cat_1_1_1_id)->isGreaterThan(0);

      $cat_1_1_2_id = $kbcat->add(
         [
            'name' => 'cat 1.1.2',
            'knowbaseitemcategories_id' => $cat_1_1_id,
            'level' => 3,
         ]
      );
      $this->integer($cat_1_1_2_id)->isGreaterThan(0);

      $cat_1_2_id = $kbcat->add(
         [
            'name' => 'cat 1.2',
            'knowbaseitemcategories_id' => $cat_1_id,
            'level' => 2,
         ]
      );
      $this->integer($cat_1_2_id)->isGreaterThan(0);

      $cat_1_2_1_id = $kbcat->add(
         [
            'name' => 'cat 1.2.1',
            'knowbaseitemcategories_id' => $cat_1_2_id,
            'level' => 3,
         ]
      );
      $this->integer($cat_1_2_1_id)->isGreaterThan(0);

      $cat_1_3_id = $kbcat->add(
         [
            'name' => 'cat 1.3',
            'knowbaseitemcategories_id' => $cat_1_id,
            'level' => 2,
         ]
      );
      $this->integer($cat_1_3_id)->isGreaterThan(0);

      // Returned tree should only containes root, as categories does not contains any elements
      $this->login('normal', 'normal');

      // Expected root category item for normal user
      $expected_root_cat = [
         'id' => '0',
         'parent' => '#',
         'text' => 'Root category <strong title="This category contains articles">(2)</strong>',
         'a_attr' => ['data-id' => '0']
      ];

      $tree = \Knowbase::getJstreeCategoryList();
      $this->array($tree)->isEqualTo([$expected_root_cat]);

      // Add a private item (not FAQ)
      $kbitem = new \KnowbaseItem();
      $kbitem_id = $kbitem->add(
         [
            'knowbaseitemcategories_id' => $cat_1_1_2_id,
            'users_id' => \Session::getLoginUserID(),
         ]
      );
      $this->integer($kbitem_id)->isGreaterThan(0);

      $kbitem_target = new \Entity_KnowbaseItem();
      $kbitem_target_id = $kbitem_target->add(
         [
            'knowbaseitems_id' => $kbitem_id,
            'entities_id' => 0,
            'is_recursive' => 1,
         ]
      );
      $this->integer($kbitem_target_id)->isGreaterThan(0);

      // Check that tree contains root + category branch containing kb item of user
      $tree = \Knowbase::getJstreeCategoryList();
      $this->array($tree)->isEqualTo(
         [
            [
               'id' => "$cat_1_1_2_id",
               'parent' => "$cat_1_1_id",
               'text' => 'cat 1.1.2 <strong title="This category contains articles">(1)</strong>',
               'a_attr' => ['data-id' => "$cat_1_1_2_id"]
            ],
            [
               'id' => "$cat_1_1_id",
               'parent' => "$cat_1_id",
               'text' => 'cat 1.1',
               'a_attr' => ['data-id' => "$cat_1_1_id"]
            ],
            [
               'id' => "$cat_1_id",
               'parent' => '0',
               'text' => 'cat 1',
               'a_attr' => ['data-id' => "$cat_1_id"]
            ],
            $expected_root_cat
         ]
      );

      // Add a FAQ item
      $kbitem = new \KnowbaseItem();
      $kbitem_id = $kbitem->add(
         [
            'knowbaseitemcategories_id' => $cat_1_2_1_id,
            'is_faq' => 1,
         ]
      );
      $this->integer($kbitem_id)->isGreaterThan(0);

      $kbitem_target = new \Entity_KnowbaseItem();
      $kbitem_target_id = $kbitem_target->add(
         [
            'knowbaseitems_id' => $kbitem_id,
            'entities_id' => 0,
            'is_recursive' => 1,
         ]
      );
      $this->integer($kbitem_target_id)->isGreaterThan(0);

      // Expected root category item for anonymous user
      $expected_root_cat = [
         'id' => '0',
         'parent' => '#',
         'text' => 'Root category',
         'a_attr' => ['data-id' => '0']
      ];

      // Check that tree contains root only (FAQ is not public) for anonymous user
      // Force session reset
      $session_bck = $_SESSION;
      $this->setUp();
      $tree_with_no_public_faq = \Knowbase::getJstreeCategoryList();

      // Check that tree contains root + category branch containing FAQ item (FAQ is public) for anonymous user
      global $CFG_GLPI;
      $use_public_faq_bck = $CFG_GLPI['use_public_faq'];
      $CFG_GLPI['use_public_faq'] = 1;
      $tree_with_public_faq = \Knowbase::getJstreeCategoryList();

      // Put back globals
      $_SESSION = $session_bck;
      $CFG_GLPI['use_public_faq'] = $use_public_faq_bck;

      $this->array($tree_with_no_public_faq)->isEqualTo([$expected_root_cat]);
      $this->array($tree_with_public_faq)->isEqualTo(
         [
            [
               'id' => "$cat_1_2_1_id",
               'parent' => "$cat_1_2_id",
               'text' => 'cat 1.2.1 <strong title="This category contains articles">(1)</strong>',
               'a_attr' => ['data-id' => "$cat_1_2_1_id"]
            ],
            [
               'id' => "$cat_1_2_id",
               'parent' => "$cat_1_id",
               'text' => 'cat 1.2',
               'a_attr' => ['data-id' => "$cat_1_2_id"]
            ],
            [
               'id' => "$cat_1_id",
               'parent' => '0',
               'text' => 'cat 1',
               'a_attr' => ['data-id' => "$cat_1_id"]
            ],
            $expected_root_cat
         ]
      );
   }
}
