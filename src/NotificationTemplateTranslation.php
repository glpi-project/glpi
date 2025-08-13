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

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    protected function computeFriendlyName()
    {
        global $CFG_GLPI;

        if ($this->getField('language') !== '') {
            return $CFG_GLPI['languages'][$this->getField('language')][0];
        }
        return __('Default translation');
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        if (!Config::canUpdate()) {
            return false;
        }
        $notificationtemplates_id = $options['notificationtemplates_id'] ?? -1;

        if ($this->getFromDB($ID)) {
            $notificationtemplates_id = $this->getField('notificationtemplates_id');
        }
        $template = new NotificationTemplate();
        $template->getFromDB($notificationtemplates_id);

        $used_languages = self::getAllUsedLanguages($notificationtemplates_id);
        // Remove current language
        if (!$this->isNewItem()) {
            $used_languages = array_diff($used_languages, [$this->getField('language')]);
        }

        TemplateRenderer::getInstance()->display('pages/setup/notification/translation.html.twig', [
            'item' => $this,
            'template' => $template,
            'used_languages' => $used_languages,
        ]);
        return true;
    }

    /**
     * @param NotificationTemplate $template object
     * @param array $options
     **/
    public function showSummary(NotificationTemplate $template, $options = [])
    {
        global $CFG_GLPI, $DB;

        $nID     = $template->getField('id');
        $canedit = Config::canUpdate();

        if ($canedit) {
            $twig_params = [
                'id' => $nID,
                'add_msg' => __('Add a new translation'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center mb-3">
                    <a class="btn btn-primary" href="{{ 'NotificationTemplateTranslation'|itemtype_form_path }}?notificationtemplates_id={{ id }}">{{ add_msg }}</a>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplatetranslations',
                'WHERE' => ['notificationtemplates_id' => $nID],
            ]) as $data
        ) {
            if ($this->getFromDB($data['id'])) {
                $href = self::getFormURL() . "?id=" . $data['id'] . "&notificationtemplates_id=" . $nID;
                $lang = $data['language'] !== '' ? $CFG_GLPI['languages'][$data['language']][0] : __('Default translation');

                $entries[] = [
                    'itemtype' => self::class,
                    'id' => $data['id'],
                    'language' => '<a href="' . htmlescape($href) . '">' . htmlescape($lang) . '</a>',
                ];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'language' => __('Language'),
            ],
            'formatters' => [
                'language' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    /**
     * @param array $input
     * @return array
     */
    public static function cleanContentHtml(array $input)
    {
        // Get as text plain text
        $txt = RichText::getTextFromHtml($input['content_html'], true, false, false, true);

        if (!$txt) {
            // No HTML (nothing to display)
            $input['content_html'] = '';
        } elseif (!$input['content_text']) {
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
            '_add_link' => false,
        ]);

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, [
            'force_update' => true,
            'name' => 'content_html',
            'content_field' => 'content_html',
            '_add_link' => false,
        ]);

        parent::post_updateItem($history);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'language',
            'name'               => __('Language'),
            'datatype'           => 'language',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'subject',
            'name'               => __('Subject'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'content_html',
            'name'               => __('Email HTML body'),
            'datatype'           => 'text',
            'htmltext'           => 'true',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'content_text',
            'name'               => __('Email text body'),
            'datatype'           => 'text',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    /**
     * @param $language_id
     * @return array
     */
    public static function getAllUsedLanguages($language_id)
    {
        global $DB;

        $used_languages = $DB->request([
            'SELECT' => ['language'],
            'FROM' => 'glpi_notificationtemplatetranslations',
            'WHERE' => [
                'notificationtemplates_id' => $language_id,
            ],
        ]);

        $used           = [];
        foreach ($used_languages as $used_language) {
            $used[$used_language['language']] = $used_language['language'];
        }

        return $used;
    }

    /**
     * @param $itemtype
     * @return void
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
            if ($values['events'] === NotificationTarget::TAG_FOR_ALL_EVENTS) {
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

            if ($values['type'] === NotificationTarget::TAG_LANGUAGE) {
                $label = sprintf(__('%1$s: %2$s'), __('Label'), $values['label']);
            } else {
                $label = $values['label'];
            }
            $rows[] = [
                'values' => [
                    ['content' => htmlescape($tag)],
                    ['content' => htmlescape($label)],
                    ['content' => htmlescape($event)],
                    ['content' => htmlescape($action)],
                    ['content' => htmlescape($allowed_values)],
                ],
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
                ],
            ],
            'rows' => $rows,
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case self::class:
                    return self::createTabEntry(__('Preview'), 0, $item::class, 'ti ti-template');
                case NotificationTemplate::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['notificationtemplates_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case self::class:
                $item->showPreview();
                break;
            case NotificationTemplate::class:
                $temp = new self();
                $temp->showSummary($item);
                break;
        }
        return true;
    }

    /**
     * Display preview information for current object.
     */
    private function showPreview(): void
    {
        $template = new NotificationTemplate();
        if (!$template->getFromDB($this->fields['notificationtemplates_id'])) {
            return;
        }

        $itemtype = $template->fields['itemtype'];
        if (!($item = getItemForItemtype($itemtype))) {
            return;
        }

        $oktypes = ['CartridgeItem', 'Change', 'ConsumableItem', 'Contract', 'CronTask',
            'Problem', 'Project', 'Ticket', 'User',
        ];

        $can_preview = in_array($itemtype, $oktypes, true);

        // Criteria Form
        $key   = getForeignKeyFieldForItemType($item::class);
        $id    = Session::getSavedOption(self::class, $key, 0);
        $event = Session::getSavedOption(self::class, $key . '_event', '');

        $data = null;

        // Preview
        if ($can_preview && $event && $item->getFromDB($id)) {
            $options = ['_debug' => true];

            // TODO Awfull Hack waiting for https://forge.indepnet.net/issues/3439
            //TODO Is this supposed to refer to notifications that are grouped together? For example, one notification about all certificates expiring? This may not be up to date.
            $multi   = ['alert', 'alertnotclosed', 'end', 'notice',
                'periodicity', 'periodicitynotice',
            ];
            if (in_array($event, $multi, true)) {
                // Won't work for Cardridge and Consumable
                $options['entities_id'] = $item->getEntityID();
                $options['items']       = [$item->getID() => $item->fields];
            }
            $target = NotificationTarget::getInstance($item, $event, $options);
            $infos  = ['language' => $_SESSION['glpilanguage'],
                'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER],
            ];

            $template->resetComputedTemplates();
            $template->setSignature(Notification::getMailingSignature($_SESSION['glpiactive_entity']));
            if ($tid = $template->getTemplateByLanguage($target, $infos, $event, $options)) {
                $data = $template->templates_by_languages[$tid];
            }
        }
        TemplateRenderer::getInstance()->display('pages/setup/notification/translation_debug.html.twig', [
            'can_preview'   => $can_preview,
            'template'      => $template,
            'data'          => $data,
        ]);
    }
}
