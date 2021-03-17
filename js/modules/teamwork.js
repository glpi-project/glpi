/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/**
 * Adds common features for teamwork features such as the Kanban.
 * These features include the retrieval and generation of team badges (User, Supplier, Group, Contract, etc).
 */
export default class Teamwork {

   constructor(params) {
      /**
       * The AJAX directory.
       * @since 9.5.0
       * @type {string}
       */
      this.ajax_root = CFG_GLPI.root_doc + "/ajax/";

      this.dark_theme = params['dark_theme'] || false;

      /**
       * The size in pixels for the team badges.
       * @type {number}
       * @since x.x.x
       */
      this.team_image_size = params['team_image_size'] || 24;

      this.team_badge_cache = {
         badges: {},
         colors: {
            User: {},
            Group: {},
            Supplier: {},
            Contact: {},
            _dark_theme: this.dark_theme
         }
      };
   }

   /**
    *
    * @param {string} itemtype
    * @param {number} items_id
    * @param {string} name
    * @param {{}} params
    */
   createTeamMember(itemtype, items_id, name, params = {}) {
      return new TeamMember(this, itemtype, items_id, name, params);
   }
}

export class TeamMember {

   /**
    *
    * @param {Teamwork} teamwork
    * @param {string} itemtype
    * @param {number} items_id
    * @param {string} name
    * @param {{}} params
    */
   constructor(teamwork, itemtype, items_id, name, params = {}) {
      this.teamwork = teamwork;
      this.itemtype = itemtype;
      this.items_id = items_id;
      this.name = name;
      this.params = params;
      this.teamMemberBadgeFactory = new TeamMemberBadgeFactory();
   }

   /**
    *
    * @return {*}
    */
   getBadge() {
      const itemtype = this.itemtype;
      const items_id = this.items_id;

      if (this.teamwork.team_badge_cache['badges'][this] === undefined ) {
         this.teamwork.team_badge_cache['badges'][this] = TeamMemberBadgeFactory.generateBadge(this);
      }
      return this.teamwork.team_badge_cache['badges'][this];
   }
}

export class TeamMemberBadgeFactory {

   /**
    * @param {TeamMember} team_member
    * @returns {TeamMemberBadge}
    */
   static generateBadge(team_member) {
      const itemtype = team_member.itemtype;
      const items_id = team_member.items_id;

      if (itemtype === 'User') {
         let user_img = null;
         $.ajax({
            url: (CFG_GLPI.root_doc + "/ajax/getUserPicture.php"),
            async: false,
            data: {
               users_id: [items_id],
               size: team_member.teamwork.team_image_size,
            },
            contentType: 'application/json',
            dataType: 'json'
         }).done(function(data) {
            if (data[items_id] !== undefined) {
               user_img = data[items_id];
            } else {
               user_img = null;
            }
         });

         if (user_img) {
            team_member.teamwork.team_badge_cache['badges'][team_member] = "<span>" + user_img + "</span>";
         } else {
            team_member.teamwork.team_badge_cache['badges'][team_member] = this.generateUserBadge(team_member);
         }
      } else {
         switch (itemtype) {
            case 'Group':
               team_member.teamwork.team_badge_cache['badges'][team_member] = this.generateOtherBadge(team_member, 'fa-users');
               break;
            case 'Supplier':
               team_member.teamwork.team_badge_cache['badges'][team_member] = this.generateOtherBadge(team_member, 'fa-briefcase');
               break;
            case 'Contact':
               team_member.teamwork.team_badge_cache['badges'][team_member] = this.generateOtherBadge(team_member, 'fa-user');
               break;
            default:
               team_member.teamwork.team_badge_cache['badges'][team_member] = this.generateOtherBadge(team_member, 'fa-user');
         }
      }
   }

   /**
    * Compute a new badge color or retrieve the cached color from session storage.
    * @since 9.5.0
    * @param {TeamMember} team_member The team member this badge is for.
    * @returns {string} Hex code color value
    */
   static getBadgeColor(team_member) {
      let cached_colors = team_member.teamwork.team_badge_cache.colors;
      const itemtype = team_member.itemtype;
      const baseColor = Math.random();
      const lightness = (Math.random() * 10) + (team_member.teamwork.dark_theme ? 25 : 70);
      //var bg_color = "hsl(" + baseColor + ", 100%," + lightness + "%,1)";
      let bg_color = ColorUtil.hslToHexColor(baseColor, 1, lightness / 100);

      if (cached_colors !== undefined && cached_colors[itemtype] !== undefined && cached_colors[itemtype][team_member.items_id]) {
         bg_color = cached_colors[itemtype][team_member.items_id];
      } else {
         if (cached_colors === null || cached_colors === undefined) {
            cached_colors = {
               User: {},
               Group: {},
               Supplier: {},
               Contact: {},
               _dark_theme: team_member.teamwork.dark_theme
            };
         }
         cached_colors[itemtype][team_member.items_id] = bg_color;
         window.sessionStorage.setItem('badge_colors', JSON.stringify(cached_colors));
      }

      return bg_color;
   }

   /**
    * Generate a user image based on the user's initials.
    * @since 9.5.0
    * @param {TeamMember} team_member The team member that represents the user.
    * @return {string} HTML image of the generated user badge.
    */
   static generateUserBadge(team_member) {
      let initials = "";
      if (team_member["firstname"]) {
         initials += team_member["firstname"][0];
      }
      if (team_member["realname"]) {
         initials += team_member["realname"][0];
      }
      // Force uppercase initials
      initials = initials.toUpperCase();

      if (initials.length === 0) {
         return this.generateOtherBadge(team_member, 'fa-user');
      }

      const canvas = document.createElement('canvas');
      canvas.width = team_member.teamwork.team_image_size;
      canvas.height = team_member.teamwork.team_image_size;
      const context = canvas.getContext('2d');
      context.strokeStyle = "#f1f1f1";

      context.fillStyle = this.getBadgeColor(team_member);
      context.beginPath();
      context.arc(team_member.teamwork.team_image_size / 2, team_member.teamwork.team_image_size / 2, team_member.teamwork.team_image_size / 2, 0, 2 * Math.PI);
      context.fill();
      context.fillStyle = team_member.teamwork.dark_theme ? 'white' : 'black';
      context.textAlign = 'center';
      context.font = 'bold ' + (team_member.teamwork.team_image_size / 2) + 'px sans-serif';
      context.textBaseline = 'middle';
      context.fillText(initials, team_member.teamwork.team_image_size / 2, team_member.teamwork.team_image_size / 2);
      const src = canvas.toDataURL("image/png");
      return "<span><img src='" + src + "' title='" + team_member['name'] + "'/></span>";
   }

   /**
    * Generate team member icon based on its name and a FontAwesome icon.
    * @since 9.5.0
    * @param {TeamMember} team_member The team member data.
    * @param {string} icon FontAwesome icon to use for this badge.
    * @returns {string} HTML icon of the generated badge.
    */
   static generateOtherBadge(team_member, icon) {
      const bg_color = this.getBadgeColor(team_member);

      return `
            <span class='fa-stack fa-lg' style='font-size: ${(team_member.teamwork.team_image_size / 2)}px'>
                <i class='fas fa-circle fa-stack-2x' style="color: ${bg_color}" title="${team_member['name']}"></i>
                <i class='fas ${icon} fa-stack-1x' title="${team_member['name']}"></i>
            </span>
         `;
   }

   /**
    * Generate a badge to indicate that 'overflow_count' number of team members are not shown on the Kanban item.
    * @since 9.5.0
    * @param {number} overflow_count Number of members without badges on the Kanban item.
    * @param {Teamwork} teamwork
    * @returns {string} HTML image of the generated overflow badge.
    */
   static generateOverflowBadge(overflow_count, teamwork) {
      const canvas = document.createElement('canvas');
      canvas.width = teamwork.team_image_size;
      canvas.height = teamwork.team_image_size;
      const context = canvas.getContext('2d');
      context.strokeStyle = "#f1f1f1";

      // Create fill color based on theme type
      const lightness = (teamwork.dark_theme ? 40 : 80);
      context.fillStyle = "hsl(255, 0%," + lightness + "%,1)";
      context.beginPath();
      context.arc(teamwork.team_image_size / 2, teamwork.team_image_size / 2, teamwork.team_image_size / 2, 0, 2 * Math.PI);
      context.fill();
      context.fillStyle = teamwork.dark_theme ? 'white' : 'black';
      context.textAlign = 'center';
      context.font = 'bold ' + (teamwork.team_image_size / 2) + 'px sans-serif';
      context.textBaseline = 'middle';
      context.fillText("+" + overflow_count, teamwork.team_image_size / 2, teamwork.team_image_size / 2);
      const src = canvas.toDataURL("image/png");
      return "<span><img src='" + src + "' title='" + __('%d other team members').replace('%d', overflow_count) + "'/></span>";
   }
}

export class TeamMemberBadge {

   constructor(element) {
      this.element = element;
   }
}

export const ColorUtil = {

   /**
    * Convert the given H, S, L values into a color hex code (with prepended hash symbol).
    * @param {number} h Hue
    * @param {number} s Saturation
    * @param {number} l Lightness
    * @returns {string} Hex code color value
    */
   hslToHexColor: (h, s, l) => {
      let r, g, b;

      if (s === 0) {
         r = g = b = l;
      } else {
         const hue2rgb = function hue2rgb(p, q, t){
            if (t < 0)
               t += 1;
            if (t > 1)
               t -= 1;
            if (t < 1/6)
               return p + (q - p) * 6 * t;
            if (t < 1/2)
               return q;
            if (t < 2/3)
               return p + (q - p) * (2/3 - t) * 6;
            return p;
         };

         const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
         const p = 2 * l - q;
         r = hue2rgb(p, q, h + 1/3);
         g = hue2rgb(p, q, h);
         b = hue2rgb(p, q, h - 1/3);
      }

      r = ('0' + (r * 255).toString(16)).substr(-2);
      g = ('0' + (g * 255).toString(16)).substr(-2);
      b = ('0' + (b * 255).toString(16)).substr(-2);
      return '#' + r + g + b;
   },
   /**
    * Check if the provided color is more light or dark.
    * This function converts the given hex value into HSL and checks the L value.
    * @since 9.5.0
    * @param hex Hex code of the color. It may or may not contain the beginning '#'.
    * @returns {boolean} True if the color is more light.
    */
   isLightColor: (hex) => {
      let c = hex;
      if (c.startsWith('#')) {
         c = c.substring(1);
      }
      const rgb = parseInt(c, 16);
      const r = (rgb >> 16) & 0xff;
      const g = (rgb >>  8) & 0xff;
      const b = (rgb >>  0) & 0xff;
      const lightness = 0.2126 * r + 0.7152 * g + 0.0722 * b;
      return lightness > 110;
   }
};
