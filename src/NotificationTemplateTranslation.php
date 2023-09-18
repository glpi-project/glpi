<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\RichText\RichText;

/**
 * NotificationTemplateTranslation Class
 **/
class NotificationTemplateTranslation extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype  = 'NotificationTemplate';
    public static $items_id  = 'notificationtemplates_id';

    public $dohistory = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Template translation', 'Template translations', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-language';
    }

    public static function getNameField()
    {
        return 'id';
    }

    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    protected function computeFriendlyName()
    {
        global $CFG_GLPI;

        if ($this->getField('language') != '') {
            return $CFG_GLPI['languages'][$this->getField('language')][0];
        } else {
            return __('Default translation');
        }

        return '';
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function showForm($ID, array $options = [])
    {
        if (!Config::canUpdate()) {
            return false;
        }
        $notificationtemplates_id = -1;
        if (isset($options['notificationtemplates_id'])) {
            $notificationtemplates_id = $options['notificationtemplates_id'];
        }

        if ($this->getFromDB($ID)) {
            $notificationtemplates_id = $this->getField('notificationtemplates_id');
        }
        $template = new NotificationTemplate();
        $template->getFromDB($notificationtemplates_id);

        TemplateRenderer::getInstance()->display('pages/setup/notification/translation.html.twig', [
            'item' => $this,
            'template' => $template
        ]);
        return true;
    }


    /**
     * @param $template        NotificationTemplate object
     * @param $options   array
     **/
    public function showSummary(NotificationTemplate $template, $options = [])
    {
        global $DB, $CFG_GLPI;

        $nID     = $template->getField('id');
        $canedit = Config::canUpdate();

        if ($canedit) {
            echo "<div class='center'>" .
              "<a class='btn btn-primary' href='" . Toolbox::getItemTypeFormURL('NotificationTemplateTranslation') .
                "?notificationtemplates_id=" . $nID . "'>" . __('Add a new translation') . "</a></div><br>";
        }

        echo "<div class='center' id='tabsbody'>";

        Session::initNavigateListItems(
            'NotificationTemplateTranslation',
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                     sprintf(
                                         __('%1$s = %2$s'),
                                         NotificationTemplate::getTypeName(1),
                                         $template->getName()
                                     )
        );

        if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        if ($canedit) {
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
        }
        echo "<th>" . __('Language') . "</th></tr>";

        foreach (
            $DB->request(
                'glpi_notificationtemplatetranslations',
                ['notificationtemplates_id' => $nID]
            ) as $data
        ) {
            if ($this->getFromDB($data['id'])) {
                Session::addToNavigateListItems('NotificationTemplateTranslation', $data['id']);
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td class='center'>";
                echo "<a href='" . Toolbox::getItemTypeFormURL('NotificationTemplateTranslation') .
                  "?id=" . $data['id'] . "&amp;notificationtemplates_id=" . $nID . "'>";

                if ($data['language'] != '') {
                    echo $CFG_GLPI['languages'][$data['language']][0];
                } else {
                    echo __('Default translation');
                }

                echo "</a></td></tr>";
            }
        }
        echo "</table>";

        if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * @param $input  array
     */
    public static function cleanContentHtml(array $input)
    {
       // Get as text plain text
        $txt = RichText::getTextFromHtml($input['content_html'], true, false, false, true);

        if (!$txt) {
           // No HTML (nothing to display)
            $input['content_html'] = '';
        } else if (!$input['content_text']) {
           // Use cleaned HTML
            $input['content_text'] = $txt;
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        return parent::prepareInputForAdd(self::cleanContentHtml($input));
    }


    public function prepareInputForUpdate($input)
    {
        return parent::prepareInputForUpdate(self::cleanContentHtml($input));
    }


    public function post_addItem()
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, [
            'force_update' => true,
            'name' => 'content_html',
            'content_field' => 'content_html',
            '_add_link' => false
        ]);

        parent::post_addItem();
    }


    public function post_updateItem($history = 1)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, [
            'force_update' => true,
            'name' => 'content_html',
            'content_field' => 'content_html',
            '_add_link' => false
        ]);

        parent::post_updateItem($history);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'language',
            'name'               => __('Language'),
            'datatype'           => 'language',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'subject',
            'name'               => __('Subject'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'content_html',
            'name'               => __('Email HTML body'),
            'datatype'           => 'text',
            'htmltext'           => 'true',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'content_text',
            'name'               => __('Email text body'),
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        return $tab;
    }


    /**
     * @param $language_id
     **/
    public static function getAllUsedLanguages($language_id)
    {

        $used_languages = getAllDataFromTable(
            'glpi_notificationtemplatetranslations',
            [
                'notificationtemplates_id' => $language_id
            ]
        );
        $used           = [];

        foreach ($used_languages as $used_language) {
            $used[$used_language['language']] = $used_language['language'];
        }

        return $used;
    }


    /**
     * @param $itemtype
     **/
    public static function showAvailableTags($itemtype)
    {
        $target = NotificationTarget::getInstanceByType($itemtype);
        $target->getTags();
        $tags = [];

        foreach ($target->tag_descriptions as $tag_type => $infos) {
            foreach ($infos as $key => $val) {
                $infos[$key]['type'] = $tag_type;
            }
            $tags = array_merge($tags, $infos);
        }
        ksort($tags);

        $rows = [];
        foreach ($tags as $tag => $values) {
            if ($values['events'] == NotificationTarget::TAG_FOR_ALL_EVENTS) {
                $event = __('All');
            } else {
                $event = implode(', ', $values['events']);
            }

            if ($values['foreach']) {
                $action = __('List of values');
            } else {
                $action = __('Single value');
            }

            if (!empty($values['allowed_values'])) {
                $allowed_values = implode(',', $values['allowed_values']);
            } else {
                $allowed_values = '';
            }

            if ($values['type'] == NotificationTarget::TAG_LANGUAGE) {
                $label = sprintf(__('%1$s: %2$s'), __('Label'), $values['label']);
            } else {
                $label = $values['label'];
            }
            $rows[] = [
                'values' => [
                    ['content' => $tag],
                    ['content' => $label],
                    ['content' => $event],
                    ['content' => $action],
                    ['content' => $allowed_values],
                ]
            ];
        }

        TemplateRenderer::getInstance()->display('components/table.html.twig', [
            'class' => 'table table-borderless',
            'header_rows' => [
                [
                    ['content' => __('Tag')],
                    ['content' => __('Label')],
                    ['content' => _n('Event', 'Events', 1)],
                    ['content' => _n('Type', 'Types', 1)],
                    ['content' => __('Possible values')],
                ]
            ],
            'rows' => $rows
        ]);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'NotificationTemplate':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['notificationtemplates_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'NotificationTemplate') {
            $temp = new self();
            $temp->showSummary($item);
        }
        return true;
    }


    /**
     * Display debug information for current object
     * NotificationTemplateTranslation => translation preview
     *
     * @since 0.84
     **/
    public function showDebug()
    {
        $template = new NotificationTemplate();
        if (!$template->getFromDB($this->fields['notificationtemplates_id'])) {
            return;
        }

        $itemtype = $template->getField('itemtype');
        if (!($item = getItemForItemtype($itemtype))) {
            return;
        }

        $oktypes = ['CartridgeItem', 'Change', 'ConsumableItem', 'Contract', 'CronTask',
            'Problem', 'Project', 'Ticket', 'User'
        ];

        if (!in_array($itemtype, $oktypes)) {
            // this itemtype doesn't work, need to be fixed
            return;
        }

        // Criteria Form
        $key   = getForeignKeyFieldForItemType($item->getType());
        $id    = Session::getSavedOption(__CLASS__, $key, 0);
        $event = Session::getSavedOption(__CLASS__, $key . '_event', '');


        $data = null;

       // Preview
        if ($event && $item->getFromDB($id)) {
            $options = ['_debug' => true];

            // TODO Awfull Hack waiting for https://forge.indepnet.net/issues/3439
            $multi   = ['alert', 'alertnotclosed', 'end', 'notice',
                'periodicity', 'periodicitynotice'
            ];
            if (in_array($event, $multi)) {
             // Won't work for Cardridge and Consumable
                $options['entities_id'] = $item->getEntityID();
                $options['items']       = [$item->getID() => $item->fields];
            }
            $target = NotificationTarget::getInstance($item, $event, $options);
            $infos  = ['language' => $_SESSION['glpilanguage'],
                'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER]
            ];

            $template->resetComputedTemplates();
            $template->setSignature(Notification::getMailingSignature($_SESSION['glpiactive_entity']));
            if ($tid = $template->getTemplateByLanguage($target, $infos, $event, $options)) {
                $data = $template->templates_by_languages[$tid];
            }
        }
        echo "</table></div>";
        TemplateRenderer::getInstance()->display('pages/setup/notification/translation_debug.html.twig', [
            'template' => $template,
            'data' => $data
        ]);
    }
}
