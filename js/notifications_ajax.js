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

// Ajax notifications
//
// Inspired from BrowserNotification plugin by
// Edgard Lorraine Messias
//
// License: BSD 3 clause

(function (window, $) {
    function GLPINotificationsAjax(options) {

        var _this = this;
        var _queue = $('<div></div>');
        var _queue_audio = $('<div></div>');

        this.options = $.extend({}, GLPINotificationsAjax.default, options);

        this.showNotification = function(id, title, body, url) {
            _queue.queue(function () {
                var queue = this;

                setTimeout(function () {
                    $(queue).dequeue();
                }, 100);

                var notification = new Notification(title, {
                    body: body,
                    icon: _this.options.icon
                });

                if (typeof(url) != 'undefined' && url != null) {
                    notification.url_item = CFG_GLPI.url_base + '/' + (url);

                    notification.onclick = function (event) {
                        event.preventDefault(); // prevent the browser from focusing the Notification's tab
                        window.open(this.url_item, '_blank');
                    };
                }

                $.ajax({
                    url: CFG_GLPI.root_doc + '/ajax/notifications_ajax.php',
                    method: 'GET',
                    data: {
                        delete: id
                    }
                });
            });

        };

        this.playAudio = function (sound) {
            if (!sound || !('Audio' in window)) {
                return false;
            }

            var audioElement = new Audio();

            $(audioElement).append($('<source />', {
                src: CFG_GLPI.root_doc + '/sound/' + sound + '.mp3',
                type: 'audio/mpeg'
            }));
            $(audioElement).append($('<source />', {
                src: CFG_GLPI.root_doc + '/sound/' + sound + '.ogg',
                type: 'audio/ogg'
            }));

            //Queue multiple sounds
            _queue_audio.queue(function () {
                var queue = this;
                audioElement.onended = function () {
                    $(queue).dequeue();
                };

                audioElement.play();
            });
        };

        this.checkNewNotifications = function () {
            if (!_this.isSupported()) {
                return false;
            }

            var ajax = $.getJSON(CFG_GLPI.root_doc + '/ajax/notifications_ajax.php');
            ajax.done(function (data) {
                if (data) {
                    for (var i = 0; i < data.length; i++) {
                        var item = data[i];
                        _this.showNotification(item.id, item.title, item.body, item.url);
                    }

                    if (_this.options.sound) {
                        _this.playAudio(_this.options.sound);
                    }

                }
            });
        };

        this.checkConcurrence = function() {
            //simple concurrency check
            //prevent multiple call to 'notifications_ajax.php' if GLPI is openned in multiple browser tabs

            var lastcheck_key = 'glpi_ajaxnotification_lastcheck_' + this.options.user_id;
            var lastCheck = localStorage.getItem(lastcheck_key);

            if (!lastCheck) {
                lastCheck = 0;
            }

            var timestamp = new Date().getTime();
            //50ms tolerance
            if (lastCheck <= timestamp - this.options.interval + 50) {
                localStorage.setItem(lastcheck_key, timestamp);
                this.checkNewNotifications();
            }

        };

        this.startMonitoring = function() {
            this.checkConcurrence();
            setInterval(this.checkConcurrence.bind(this), this.options.interval);
        };

        this.checkPermission = function () {
            // Let's check whether notification permissions have already been granted
            if (Notification.permission === "granted") {
                this.startMonitoring();
            } else if (Notification.permission !== 'denied') {
            // Otherwise, we need to ask the user for permission
                Notification.requestPermission(function (permission) {
                    // If the user accepts, let's create a notification
                    if (permission === "granted") {
                        this.startMonitoring();
                    }
                });
            }
        };

        this.start = function () {
            if (!this.isSupported()) {
                return false;
            }

            this.checkPermission();
        };

        this.isSupported = function () {
            return "Notification" in window && "localStorage" in window;
        };
    }

    GLPINotificationsAjax.default = {
        interval : 10000,
        sound    : false,
        icon     : false,
        user_id  : 0
    };

    window.GLPINotificationsAjax = GLPINotificationsAjax;
})(window, jQuery);
