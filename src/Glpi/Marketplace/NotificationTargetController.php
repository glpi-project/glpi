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

namespace Glpi\Marketplace;

use Notification;
use NotificationTarget;
use Override;
use Plugin;
use Session;

/**
 * @extends NotificationTarget<\Glpi\Marketplace\Controller>
 */
class NotificationTargetController extends NotificationTarget
{
    #[Override]
    public function addNotificationTargets($entity): void
    {
        $this->addProfilesToTargets();
        $this->addGroupsToTargets($entity);
        $this->addTarget(Notification::GLOBAL_ADMINISTRATOR, __('Administrator'));
    }

    #[Override]
    public function getEvents(): array
    {
        return ['checkpluginsupdate' => __('Check all plugin updates')];
    }

    #[Override]
    public function addDataForTemplate($event, $options = []): void
    {
        $updated_plugins = $options['plugins'];
        $plugin = new Plugin();
        foreach ($updated_plugins as $plugin_key => $version) {
            $plugin_info = $plugin->getInformationsFromDirectory($plugin_key);

            $this->data['plugins'][] = [
                '##plugin.name##'        => $plugin_info['name'],
                '##plugin.key##'         => $plugin_key,
                '##plugin.version##'     => $version,
                '##plugin.old_version##' => $plugin_info['version'],
            ];
        }

        $this->data['##marketplace.url##'] = $this->formatURL(
            $options['additionnaloption']['usertype'],
            '/front/marketplace.php'
        );

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }

    #[Override]
    public function getTags(): void
    {
        //Tags with just lang
        $tags = [
            'plugins_updates_available' => __('Some updates are available for your installed plugins!'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true,
            ]);
        }

        //Foreach global tags
        $tags = [
            'plugins' => _n('Plugin', 'Plugins', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
            ]);
        }

        // sub tags
        $tags = [
            'plugin.name'        => __('Plugin name'),
            'plugin.key'         => __('Plugin directory'),
            'plugin.version'     => __('Plugin new version number'),
            'plugin.old_version' => __('Plugin old version number'),
            'marketplace.url'    => __('URL of GLPI marketplace'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'    => $tag,
                'label'  => $label,
                'value'  => true,
            ]);
        }
    }
}
