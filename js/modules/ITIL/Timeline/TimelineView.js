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

export class TimelineView {
    constructor(element, safe_item_fields) {
        this.element = element;
        this.safe_item_fields = safe_item_fields;
        this.info_card_cache = new Map();
        this.initActorFields();
    }

    initActorFields() {
        const entities_id = this.element.attr('data-entities-id');
        const itiltemplate_type = this.element.attr('data-itiltemplate-class');
        const itiltemplate_id = this.element.attr('data-itiltemplate-id');
        const itemtype = this.element.attr('data-itemtype');
        const item_id = this.element.attr('data-items-id');
        const is_new_item = item_id <= 0;

        $('select[data-actor-type]').each((index, element) => {
            const $element = $(element);
            const actor_type = $element.attr('data-actor-type');
            const idor_token = $element.attr('data-idor');
            const returned_itemtypes = ($element.attr('data-returned-itemtypes') || '').split(',');
            const can_update = $element.attr('data-canupdate') !== undefined;
            const allow_auto_submit = $element.attr('data-allow-auto-submit') !== undefined;

            const genericTemplate = (option = {}, is_selection = false) => {
                const element   = $(option.element);
                const itemtype  = element.data('itemtype') ?? option.itemtype;
                const items_id  = element.data('items-id') ?? option.items_id;
                let text        = window._.escape(element.data('text') ?? option.text ?? '');
                const title     = window._.escape(element.data('title') ?? option.title ?? '');
                const use_notif = element.data('use-notification') ?? option.use_notification ?? 1;
                const alt_email = element.data('alternative-email') ?? option.alternative_email ?? '';

                let icon = "";
                let fk   = "";

                switch (itemtype) {
                    case 'User':
                        if (items_id == 0) {
                            text = alt_email;
                            icon = `<i class="ti ti-mail mx-1" title="${__('Direct email')}"></i>`;
                        } else {
                            icon = `<i class="ti ti-user mx-1" title="${_n('User', 'Users', 1)}"></i>`;
                        }
                        if (actor_type === "assign") {
                            fk = "users_id_assign";
                        } else if (actor_type === "requester") {
                            fk = "users_id_requester";
                        } else if (actor_type === "observer") {
                            fk = "users_id_observer";
                        }
                        break;
                    case "Group":
                        icon = `<i class="ti ti-users mx-1" title="${_n('Group', 'Groups', 1)}"></i>`;
                        if (actor_type === "assign") {
                            fk = "groups_id_assign";
                        } else if (actor_type === "requester") {
                            fk = "groups_id_requester";
                        } else if (actor_type === "observer") {
                            fk = "groups_id_observer";
                        }
                        break;
                    case "Supplier":
                        icon = `<i class="ti ti-package mx-1" title="${_n('Supplier', 'Suppliers', 1)}"></i>`;
                        fk   = "suppliers_id_assign";
                        break;
                }

                let actions = '';
                if (can_update && ['User', 'Supplier', 'Email'].includes(itemtype) && is_selection) {
                    actions = `
                    <button class="btn btn-sm btn-ghost-secondary edit-notify-user"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              title="${__('Email followup')}"
                              type="button">
                        <i class="ti ${use_notif ? 'ti-bell-filled' : 'ti-bell'} notify-icon"></i>
                    </button>
                    `;
                }
                // manage specific display for tree data (like groups)
                let indent = "";
                if (!is_selection && "level" in option && option.level > 1) {
                    for (let index = 1; index < option.level; index++) {
                        indent = `&nbsp;&nbsp;&nbsp;${indent}`;
                    }
                    indent = `${indent}&raquo;`;
                }
                // prepare html for option element
                text = (is_selection && itemtype === "Group") ? title : text;
                const option_text    = `<span class="actor_text">${text}</span>`;
                const option_element = $(`
                    <span class="actor_entry" data-itemtype="${window._.escape(itemtype)}" data-items-id="${window._.escape(items_id)}"
                          data-actortype="${window._.escape(actor_type)}">${indent}${icon}${option_text}${actions}</span>`);

                // manage ticket information (number of assigned ticket for an actor)
                if (is_selection && itemtype !== "Email") {
                    let label = '';
                    if (actor_type === "assign") {
                        label = __('Number of tickets already assigned');
                    } else if (actor_type === "requester") {
                        label = __('Number of tickets as requester');
                    }
                    const existing_element = $(`
                    <span class="assign_infos ms-1" title="${window._.escape(label)}"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-id="${window._.escape(items_id)}" data-fk="${window._.escape(fk)}">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </span>
                `);
                    option_element.append(existing_element);

                    $.get(`${CFG_GLPI.root_doc}/ajax/actorinformation.php`, {
                        [fk]: items_id,
                        only_number: true,
                    }).done((number) => {
                        const badge = number.length > 0 ? `<span class="badge bg-secondary-lt">${number}</span>` : '';
                        existing_element.html(badge);
                    });
                }

                return option_element;
            };

            $element.select2({
                tags: true,
                width: ($element.attr('data-can-assign-me') !== undefined) ? 'calc(100% - 30px)' : '100%',
                tokenSeparators: [',', ' '],
                containerCssClass: 'actor-field',
                templateSelection: (option) => genericTemplate(option, true),
                templateResult: (option) => genericTemplate(option, false),
                disabled: !can_update,
                createTag: (params) => {
                    const term = $.trim(params.term);

                    if (term === '') {
                        return null;
                    }

                    // Don't offset to create a tag if it's not an email
                    if (!new RegExp(/^[\w-\.]+@([\w-]+\.)+[\w-]{2,63}$/).test(term)) {
                        // Return null to disable tag creation
                        return null;
                    }

                    return {
                        id: term,
                        text: term,
                        itemtype: "User",
                        items_id: 0,
                        use_notification: 1,
                        alternative_email: term,
                    };
                },
                ajax: {
                    url: `${CFG_GLPI.root_doc}/ajax/actors.php`,
                    datatype: 'json',
                    type: 'POST',
                    delay: 250,
                    data: (params) => {
                        return {
                            action: 'getActors',
                            actortype: actor_type,
                            users_right: actor_type === 'assign' ? 'own_ticket' : 'all',
                            entity_restrict: (window.actors.requester.length === 0 && is_new_item) ? -1 : entities_id,
                            searchText: params.term,
                            _idor_token: idor_token,
                            itiltemplate_class: itiltemplate_type,
                            itiltemplates_id: itiltemplate_id,
                            itemtype: itemtype,
                            items_id: is_new_item ? -1 : item_id,
                            item: this.safe_item_fields,
                            returned_itemtypes: returned_itemtypes,
                            page: params.page || 1
                        };
                    }
                }
            });

            const updateActors = () => {
                const data = $element.select2('data');
                const new_actors = [];
                data.forEach((selection) => {
                    const element = $(selection.element);

                    let itemtype  = selection.itemtype ?? element.data('itemtype');
                    const items_id  = selection.items_id ?? element.data('items-id');
                    let use_notif = selection.use_notification  ?? element.data('use-notification')  ?? false;
                    const def_email = selection.default_email ?? element.data('default-email') ?? '';
                    let alt_email = selection.alternative_email ?? element.data('alternative-email') ?? '';

                    if (itemtype === "Email") {
                        itemtype  = "User";
                        use_notif = true;
                        alt_email = selection.id;
                    }

                    new_actors.push({
                        itemtype: itemtype,
                        items_id: items_id,
                        use_notification: use_notif,
                        default_email: def_email,
                        alternative_email: alt_email,
                    });
                });

                window.actors[actor_type] = new_actors;

                window.saveActorsToDom();
            };

            const auto_submit = () => {
                if (allow_auto_submit && is_new_item && actor_type === 'requester') {
                    const form = $element.closest('form');
                    if (form.length === 1) {
                        form.submit();
                    }
                }
            };

            $element.on('select2:select select2:unselect', () => {
                updateActors();
                auto_submit();
            });

            // intercept event for edit notification button
            document.addEventListener('click', event => {
                const target = $(event.target);
                if (target.closest(`#${$element.prop('id')} + .select2 .edit-notify-user`).length) {
                    return window.openNotifyModal(event);
                }
                // if a click on assign info is detected prevent opening of select2
                if (target.closest(`#${$element.prop('id')} + .select2 .assign_infos`).length) {
                    event.stopPropagation();
                }
            }, {capture: true});
            document.addEventListener('keydown', event => {
                const target = $(event.target);
                if (target.closest(`#${$element.prop('id')} + .select2 .edit-notify-user`).length
                    && event.key == "Enter") {
                    return window.openNotifyModal(event);
                }
            }, {capture: true});
        });

        this.element.on('mouseenter', '.actor_entry', (e) => {
            // Delay fetching user info card until actually needed
            const target = $(e.target).closest('.actor_entry');
            this.addActorInfoPopover(target, target.attr('data-itemtype'), target.attr('data-items-id'), true);
        });
    }

    getActorInfoCard(itemtype, items_id) {
        if (this.info_card_cache.has(`${itemtype}_${items_id}`)) {
            return Promise.resolve(this.info_card_cache.get(`${itemtype}_${items_id}`));
        } else {
            return $.ajax({
                url: `${CFG_GLPI.root_doc}/ajax/comments.php`,
                type: 'POST',
                data: {
                    'itemtype': itemtype,
                    'value': items_id,
                }
            }).then((data) => {
                this.info_card_cache.set(`${itemtype}_${items_id}`, data);
                return data;
            });
        }
    }

    /**
     *
     * @param {jQuery} element
     * @param {string} itemtype
     * @param {number} items_id
     * @param {boolean} show_immediately
     */
    addActorInfoPopover(element, itemtype, items_id, show_immediately = false) {
        if (window.bootstrap.Popover.getInstance(element)) {
            // already initialized
            return;
        }
        this.getActorInfoCard(itemtype, items_id).then((data) => {
            element.popover({
                container: element.parent(),
                html: true,
                sanitize: false,
                trigger: 'hover',
                delay: { hide: 300 },
                content: data
            }).on('show.bs.popover', () => {
                // hide other popovers
                $('.popover').popover('hide');
            });
            if (show_immediately) {
                element.popover('show');
            }
        });
    }
}
