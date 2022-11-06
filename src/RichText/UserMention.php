<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\RichText;

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use CommonITILTask;
use CommonITILValidation;
use Glpi\Toolbox\Sanitizer;
use ITILFollowup;
use ITILSolution;
use NotificationEvent;
use SimpleXMLElement;
use User;

final class UserMention
{
    /**
     * Handle user mentions.
     * Add newly mention users to observers and send them a notification.
     *
     * @return void
     */
    public static function handleUserMentions(CommonDBTM $item): void
    {

        $content_fields = $item instanceof CommonITILValidation
         ? ['comment_submission', 'comment_validation']
         : ['content'];

        $previously_mentionned_actors_ids = [];
        $mentionned_actors_ids = [];

        foreach ($content_fields as $content_field) {
            if (!array_key_exists($content_field, $item->fields) && !array_key_exists($content_field, $item->input)) {
                // Field is not define in both `$item->fields` and `$item->input`, it means that
                // it is certainly not a valid field for current item.
                continue;
            }

            if (array_key_exists($content_field, $item->oldvalues)) {
               // Update case: content field was updated
                $previous_value = $item->oldvalues[$content_field];
            } else if (count($item->updates) > 0) {
               // Update case: content field was not updated
                $previous_value = $item->fields[$content_field];
            } else {
               // Creation case
                $previous_value = null;
            }

            $new_value = $item->input[$content_field] ?? null;

            if ($new_value !== null) {
                $mentionned_actors_ids = array_merge(
                    $mentionned_actors_ids,
                    self::getUserIdsFromUserMentions($new_value)
                );
            }

            if ($previous_value !== null) {
                $previously_mentionned_actors_ids = array_merge(
                    $previously_mentionned_actors_ids,
                    self::getUserIdsFromUserMentions($previous_value)
                );
            }
        }

       // Keep only newly mentioned actors
        $mentionned_actors_ids = array_diff($mentionned_actors_ids, $previously_mentionned_actors_ids);

        if (empty($mentionned_actors_ids)) {
            return;
        }

       // Retrieve main item
        $main_item = $item;
        $options = [];
        if ($item instanceof CommonITILTask) {
            $options = [
                'task_id'    => $item->fields['id'],
                'is_private' => $item->isPrivate(),
            ];

            $main_item = $item->getItem();
        } else if ($item instanceof CommonITILValidation) {
            $options = [
                'validation_id'     => $item->fields['id'],
                'validation_status' => $item->fields['status']
            ];

            $main_item = getItemForItemtype($item->getItilObjectItemType());
            $main_item->getFromDB($item->fields[$item::$items_id]);
        } else if ($item instanceof ITILFollowup) {
            $options = [
                'followup_id' => $item->fields['id'],
                'is_private'  => $item->isPrivate(),
            ];

            $main_item = getItemForItemtype($item->fields['itemtype']);
            $main_item->getFromDB($item->fields['items_id']);
        } else if ($item instanceof ITILSolution) {
            $main_item = getItemForItemtype($item->fields['itemtype']);
            $main_item->getFromDB($item->fields['items_id']);
        }

       // Send a "you have been mentioned" notification
        foreach ($mentionned_actors_ids as $user_id) {
            $options['users_id'] = $user_id;
            NotificationEvent::raiseEvent('user_mention', $main_item, $options);
        }

        if ($main_item instanceof CommonITILObject) {
            if (empty($main_item->userlinkclass) || !class_exists($main_item->userlinkclass)) {
                return; // Cannot add observers
            }

           // Retrieve current actors list
            $userlink = new $main_item->userlinkclass();
            $current_actors_ids = [];
            $current_actors = $userlink->getActors($main_item->fields['id']);
            foreach ($current_actors as $actors) {
                foreach ($actors as $actor) {
                    $current_actors_ids[] = $actor['users_id'];
                }
            }

           // Add newly mentioned actors as observers
            foreach ($mentionned_actors_ids as $user_id) {
                if (in_array($user_id, $current_actors_ids)) {
                    continue;
                }

                $input = [
                    'type'                            => CommonITILActor::OBSERVER,
                    'users_id'                        => $user_id,
                    $main_item->getForeignKeyField()  => $main_item->fields['id'],
                    '_do_not_compute_takeintoaccount' => true,
                    '_from_object'                    => true,
                ];
                $userlink->add($input);
            }
        }
    }

    /**
     * Extract ids of mentioned users.
     *
     * @param string $content
     *
     * @return int[]
     */
    public static function getUserIdsFromUserMentions(string $content)
    {
        $ids = [];

        try {
            $content = Sanitizer::getVerbatimValue($content);
            libxml_use_internal_errors(true);
            $content_as_xml = new SimpleXMLElement('<div>' . $content . '</div>');
        } catch (\Throwable $e) {
           // Sanitize process does not handle correctly `<` and `>` chars that are not surrounding html tags.
           // This generates invalid HTML that cannot be loaded by `SimpleXMLElement`.
            return [];
        }

        $mention_elements = $content_as_xml->xpath('//*[@data-user-mention="true"]');
        foreach ($mention_elements as $mention_element) {
            $ids[] = (int)$mention_element->attributes()->{'data-user-id'};
        }

        return $ids;
    }

    /**
     * Refresh user mentions HTML in order to display them.
     * User name is updated, and a link to user page could be added on mention.
     *
     * @param string $content
     *
     * @return string
     */
    public static function refreshUserMentionsHtmlToDisplay(string $content): string
    {

        $mentionned_users_ids = self::getUserIdsFromUserMentions($content);

        foreach ($mentionned_users_ids as $user_id) {
            $user = new User();
            if (!$user->getFromDB($user_id)) {
                // User does not exist anymore, keep the mention but do not add link.
                continue;
            }

            $pattern = '/'
            // <span data-user-mention="true" ...>
            . '<span[^>]*'
            . '('
            . 'data-user-mention="true"[^>]+data-user-id="' . $user_id . '"'
            . '|'
            . 'data-user-id="' . $user_id . '"[^>]+data-user-mention="true"'
            . ')'
            . '[^>]*>'
            // @Name
            . '@[^>]+'
            // span closing
            . '<\/span>'
            . '/';
            $replacement = sprintf(
                '<a class="user-mention" href="%s">@%s</a>',
                $user->getLinkURL(),
                $user->getFriendlyName()
            );
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }
}
