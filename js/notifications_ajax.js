// Ajax notifications
//
// Inspired from BrowserNotification plugin by
// Edgard Lorraine Messias
//
// License: BSD 3 clause

(function (window, $) {
   function GLPINotificationsAjax(options) {

      var _this = this;
      var _queue = $('<div />');
      var _queue_audio = $('<div />');
      var _interval = null;

      this.options = $.extend({}, GLPINotificationsAjax.default, options);

      this.showNotification = function(id, title, body, url) {
         /**
          * Queue to prevent firefox bug
          * Show next notification after 100ms
          * @see http://stackoverflow.com/questions/33073958/multiple-notifications-with-notifications-api-continuously-in-firefox
          */
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
               for (i = 0; i < data.length; i++) {
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
         _interval = setInterval(this.checkConcurrence.bind(this), this.options.interval);
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
