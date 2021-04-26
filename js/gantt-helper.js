var readonly = false;
var url = '../ajax/gantt.php';

function initGantt($ID) {

   // >>>>> Configs

   gantt.config.grid_width = 600;
   gantt.config.date_format = "%Y-%m-%d %H:%i";

   gantt.config.sort = true;
   gantt.config.order_branch = "marker"; // avoid reordering performance slowdown by showing only task name while dragging
   gantt.config.show_progress = true;

   gantt.config.lightbox.sections = [
      { name: "description", height: 70, map_to: "text", type: "textarea", focus: true },
      { name: "time", type: "duration", map_to: "auto" }
   ];

   gantt.config.lightbox.project_sections = [
      { name: "description", height: 70, map_to: "text", type: "textarea", focus: true },
      { name: "time", type: "duration", readonly: true, map_to: "auto" }
   ];

   gantt.config.lightbox.milestone_sections = [
      { name: "description", height: 70, map_to: "text", type: "textarea", focus: true },
      { name: "time", type: "duration", single_date: true, map_to: "auto" }
   ];

   // disable task specific controls on projects
   gantt.templates.task_class = function(start, end, task) {
      var css = [];
      if (task.type == "project") {
         css.push("no_progress_drag");
         css.push("no_link_drag");
      }
      return css.join(" ");
   }

   // set text labels for milestones
   gantt.templates.rightside_text = function(start, end, task) {
      if(task.type == gantt.config.types.milestone){
          return task.text;
      }
      return "";
   };

   gantt.templates.progress_text = function(start, end, task) {
      return "<span style='text-align:left; color: #fff;'>" + Math.round(task.progress * 100) + "% </span>";
   };

   // enable tooltips and fullscreen mode
   gantt.plugins({ tooltip: true, fullscreen: true });

   gantt.templates.tooltip_text = function(start, end, task) {
      return "<b><span class=\"capitalize\">" + task.type + ":</span></b> " + task.text + "<br/><b>Start date:</b> " +
         gantt.templates.tooltip_date_format(start) +
         "<br/><b>End date:</b> " + gantt.templates.tooltip_date_format(end) +
         "<br/><b>Progress:</b> " + parseInt(task.progress * 100) + "%";
   };

   // columns definition
   gantt.config.columns = [
      //{ name: "wbs", label: " ", width: 45, template: gantt.getWBSCode, align: "left" },
      { name: "text", label: "Project / Task", width: 200, tree: true, align: "left" },
      { name: "start_date", label: "Start date", align: "left", width: 90 },
      { name: "end_date", label: "End date", align: "left", width: 90 },
      { name: "duration", label: "Duration", align: "center" },
      {
         name: "parent",
         label: "Parent",
         align: "left",
         width: 160,
         template: function(item) {
            let parent;
            if (item.parent)
               parent = gantt.getTask(item.parent).text;
            return parent;
         }
      }
   ];

   // specify fullscreen root element
   gantt.ext.fullscreen.getFullscreenElement = function() {
      return document.getElementById("gantt-container");
   }

   var zoomConfig = {
      levels: [{
            name: "day",
            scale_height: 27,
            min_column_width: 80,
            scales: [
               { unit: "day", step: 1, format: "%d %M" }
            ]
         },
         {
            name: "week",
            scale_height: 50,
            min_column_width: 50,
            scales: [{
                  unit: "week",
                  step: 1,
                  format: function(date) {
                     var dateToStr = gantt.date.date_to_str("%d %M");
                     var endDate = gantt.date.add(date, 6, "day");
                     var weekNum = gantt.date.date_to_str("%W")(date);
                     return "#" + weekNum + ", " + dateToStr(date) + " - " + dateToStr(endDate);
                  }
               },
               { unit: "day", step: 1, format: "%j %D" }
            ]
         },
         {
            name: "month",
            scale_height: 50,
            min_column_width: 120,
            scales: [
               { unit: "month", format: "%F, %Y" },
               { unit: "week", format: "Week #%W" }
            ]
         },
         {
            name: "quarter",
            height: 50,
            min_column_width: 90,
            scales: [
               { unit: "month", step: 1, format: "%M" },
               {
                  unit: "quarter",
                  step: 1,
                  format: function(date) {
                     var dateToStr = gantt.date.date_to_str("%M");
                     var endDate = gantt.date.add(gantt.date.add(date, 3, "month"), -1, "day");
                     return dateToStr(date) + " - " + dateToStr(endDate);
                  }
               }
            ]
         },
         {
            name: "year",
            scale_height: 50,
            min_column_width: 30,
            scales: [
               { unit: "year", step: 1, format: "%Y" }
            ]
         }
      ]
   };

   gantt.ext.zoom.init(zoomConfig);
   gantt.ext.zoom.setLevel("month");

   // <<<<< Configs


   // >>>>> Event handlers

   gantt.ext.zoom.attachEvent("onAfterZoom", function(level, config) {
      document.querySelector(".gantt_radio[value='" + config.name + "']").checked = true;
   });

   var radios = document.getElementsByName("scale");
   for (var i = 0; i < radios.length; i++) {
      radios[i].onclick = function(event) {
         gantt.ext.zoom.setLevel(event.target.value);
      };
   }

   // enable task reordering (on same level) between projects
   gantt.attachEvent("onBeforeTaskMove", function(id, parent, tindex) {
      var task = gantt.getTask(id);
      if (task.parent != parent)
         return false;
      return true;
   });

   if (!readonly) {
      // catch task drag event to update db
      gantt.attachEvent("onAfterTaskDrag", function(id, mode, e) {
         var task = gantt.getTask(id);
         var progress = (Math.round(task.progress * 100 / 5) * 5) / 100; // prevent server side exception for wrong stepping
         $.ajax({
            url,
            type: 'POST',
            data: {
               updateTask: 1,
               task: {
                  id: task.linktask_id,
                  start_date: formatFunc(task.start_date),
                  end_date: formatFunc(task.end_date),
                  progress
               }
            },
            success: function(resp) {
               var json = JSON.parse(resp);
               if (json.ok) {
                  task.progress = progress;
                  gantt.updateTask(task.id);
               } else
                  console.log('Could not update Task[' + id + ']: ' + json.error);
            }
         });
      });

      gantt.attachEvent("onAfterTaskUpdate", function(id, task) {
         parentProgress(id);
      });

      // handle lightbox Save action
      gantt.attachEvent("onLightboxSave", function(id, item, is_new) {
         // TODO add new item

         // update item
         if (item.type == 'project') {
            $.ajax({
               url,
               type: 'POST',
               data: {
                  updateProject: 1,
                  project: {
                     id: item.id,
                     name: item.text
                  }
               },
               success: function(resp) {
                  var json = JSON.parse(resp);
                  if (json.ok) {
                     $project = gantt.getTask(id);
                     $project.text = item.text;
                     gantt.updateTask(id);
                     gantt.hideLightbox();
                  } else
                     gantt.alert('Could not update Project[' + item.text + ']: ' + json.error);
               },
               error: function(resp) {
                  gantt.alert(resp.responseText);
               }
            });
         } else {
            $.ajax({
               url,
               type: 'POST',
               data: {
                  updateTask: 1,
                  task: {
                     id: item.linktask_id,
                     name: item.text,
                     start_date: formatFunc(item.start_date),
                     end_date: formatFunc(item.end_date)
                  }
               },
               success: function(resp) {
                  var json = JSON.parse(resp);
                  if (json.ok) {
                     $task = gantt.getTask(id);
                     $task.text = item.text;
                     $task.start_date = item.start_date;
                     $task.end_date = item.end_date;
                     gantt.updateTask(id);
                     gantt.hideLightbox();
                  } else
                     gantt.alert('Could not update Task[' + item.text + ']: ' + json.error);
               },
               error: function(resp) {
                  gantt.alert(resp.responseText);
               }
            });
         }
      });

      // handle lightbox Delete action
      gantt.attachEvent("onLightboxDelete", function(id) {

         var msg = "Item will be deleted, do you want to continue ?";
         var item = gantt.getTask(id);

         if (item.type != 'project' && gantt.hasChild(id)) {
            gantt.alert("Item cannot be deleted, please remove child items first.");
         } else {

            if (item.type == 'project')
               msg = "Move this project to trashbin ?";

            gantt.confirm({
               text: msg,
               ok: "Yes",
               cancel: "No",
               callback: function(result) {
                  if (result) {
                     if (item.type == 'project') {
                        // move project to trashbin
                        $.ajax({
                           url,
                           type: 'POST',
                           dataType: 'json',
                           data: { putInTrashbin: 1, projectId: item.id },
                           success: function(resp) {
                              if (resp.ok) {
                                 gantt.deleteTask(id);
                                 gantt.hideLightbox();
                              } else
                                 gantt.alert(resp.error);
                           },
                           error: function(resp) {
                              gantt.alert(resp.responseText);
                           }
                        });
                     } else {
                        // delete task or milestone
                        $.ajax({
                           url,
                           type: 'POST',
                           dataType: 'json',
                           data: { deleteTask: 1, taskId: item.linktask_id },
                           success: function(resp) {
                              if (resp.ok) {
                                 gantt.deleteTask(id);
                                 gantt.hideLightbox();
                              } else
                                 gantt.alert(resp.error);
                           },
                           error: function(resp) {
                              gantt.alert(resp.responseText);
                           }
                        });
                     }
                  }
               }
            });
         }
      });
   }

   gantt.attachEvent("onBeforeLinkAdd", function(id, link) {

      var sourceTask = gantt.getTask(link.source);
      var targetTask = gantt.getTask(link.target);

      if (validateLink(sourceTask, targetTask, link.type)) {
         $.ajax({
            url,
            type: 'POST',
            data: {
               addTaskLink: 1,
               taskLink: {
                  source_id: sourceTask.linktask_id,
                  source_uuid: sourceTask.id,
                  target_id: targetTask.linktask_id,
                  target_uuid: targetTask.id,
                  type: link.type,
                  lag: link.lag,
                  lead: link.lead
               }
            },
            success: function(resp) {
               var json = JSON.parse(resp);
               if (json.ok) {
                  var tempId = link.id;
                  gantt.changeLinkId(tempId, json.id);
               } else {
                  gantt.alert(json.error);
                  gantt.deleteLink(id);
               }
            },
            error: function(resp) {
               gantt.alert(resp.responseText);
               gantt.deleteLink(id);
            }
         });
      } else
         return false;
   });

   // >>>>> link double click event to handle edit/save/delete actions
   (function() {

      var modal;
      var editLinkId;
      gantt.attachEvent("onLinkDblClick", function(id, e) {

         editLinkId = id;
         var link = gantt.getLink(id);
         var linkTitle;

         switch (parseInt(link.type)) {
            case parseInt(gantt.config.links.finish_to_start):
               linkTitle = "Finish to Start: ";
               break;
            case parseInt(gantt.config.links.finish_to_finish):
               linkTitle = "Finish to Finish: ";
               break;
            case parseInt(gantt.config.links.start_to_start):
               linkTitle = "Start to Start: ";
               break;
            case parseInt(gantt.config.links.start_to_finish):
               linkTitle = "Start to Finish: ";
               break;
         }

         linkTitle += " " + gantt.getTask(link.source).text + " -> " + gantt.getTask(link.target).text;

         modal = gantt.modalbox({
            title: "<p class='gantt_cal_lsection' style='line-height:normal'>" + linkTitle + "</p>",
            text: "<div class='gantt_cal_lsection'>" +
               "<label>Lag <input type='number' class='lag-input' /></label>" +
               "</div>",
            buttons: [
               { label: "Save", css: "gantt_save_btn", value: "save" },
               { label: "Cancel", css: "gantt_cancel_btn", value: "cancel" },
               { label: "Delete", css: "gantt_delete_btn", value: "delete" }
            ],
            width: "500px",
            callback: function(result) {
               switch (result) {
                  case "save":
                     saveLink();
                     break;
                  case "cancel":
                     cancelEditLink();
                     break;
                  case "delete":
                     deleteLink();
                     break;
               }
            }
         });

         modal.querySelector(".lag-input").value = link.lag || 0;
         return false;
      });

      function endPopup() {
         modal = null;
         editLinkId = null;
      }

      function cancelEditLink() {
         endPopup();
      }

      function deleteLink() {
         deleteTaskLink(editLinkId, endPopup);
      }

      function saveLink() {
         var link = gantt.getLink(editLinkId);
         var lagValue = modal.querySelector(".lag-input").value;

         if (!isNaN(parseInt(lagValue, 10))) {
            link.lag = parseInt(lagValue, 10);
         }

         updateTaskLink(link, endPopup);
      }
   })();
   // <<<<< link double click

   // adjust elements visibility on Fullscreen expand/collapse
   gantt.attachEvent("onBeforeExpand", function() {
      $('#c_ssmenu2, #c_logo').fadeOut('fast');
      $('#gantt-features').css({
         'position': 'absolute',
         'bottom': '18px',
         'right': '10px'
      });
      return true;
   });

   gantt.attachEvent("onCollapse", function() {
      $('#c_ssmenu2, #c_logo').fadeIn('fast');
      $('#gantt-features').css({
         'position': 'initial',
         'bottom': '10px'
      });
      return true;
   });

   $('.flatpickr').flatpickr();

   // <<<<< Event handlers


   gantt.config.readonly = readonly;
   gantt.init('gantt-container');

   // load Gantt data
   $.ajax({
      url,
      type: 'POST',
      data: { getData: 1, id: $ID },
      success: function(resp) {
         var json = JSON.parse(resp);
         if (json.data) {
            gantt.parse(json);
            gantt.sort("start_date", false);
            gantt.render();

            if (readonly) {
               gantt.message.position = 'bottom';
               gantt.message({
                  type: 'warning',
                  text: 'Gantt mode: \'Readonly\'',
                  expire: -1
               });
            }
         } else
            console.log(json.error);
      },
      error: function(resp) {
         console.log(resp.responseText);
      }
   });
}


// >>>>> Functions

var formatFunc = gantt.date.date_to_str("%Y-%m-%d %H:%i");

// update parent item progress
function parentProgress(id) {
   gantt.eachParent(function(task) {
      var children = gantt.getChildren(task.id);
      var childProgress = 0;
      for (var i = 0; i < children.length; i++) {
         var child = gantt.getTask(children[i]);
         childProgress += (child.progress * 100);
      }
      task.progress = childProgress / children.length / 100;
   }, id);
   gantt.render();
}

function validateLink(source, target, type) {
   var valid = true;

   if (source.type == 'project' && target.type == 'project') {
      gantt.alert("Links between projects cannot be created.");
      valid = false;
   } else if (source.type == 'project' || target.type == 'project') {
      gantt.alert("Links between projects and tasks cannot be created.");
      valid = false;
   } else if (type == gantt.config.links.finish_to_start && target.start_date < source.end_date) {
      gantt.alert("Invalid link: Target task can't start before source task ends.");
      valid = false;
   } else if (type == gantt.config.links.start_to_start && target.start_date < source.start_date) {
      gantt.alert("Invalid link: Target task can't start before source task starts.");
      valid = false;
   } else if (type == gantt.config.links.finish_to_finish && target.end_date < source.end_date) {
      gantt.alert("Invalid link: Target task can't end before source task ends.");
      valid = false;
   } else if (type == gantt.config.links.start_to_finish && target.end_date < source.start_date) {
      gantt.alert("Invalid link: Target task can't end before the source task starts.");
      valid = false;
   }
   return valid;
}

function updateTaskLink(link, callback) {
   $.ajax({
      url,
      type: 'POST',
      data: {
         updateTaskLink: 1,
         taskLink: {
            id: link.id,
            lag: link.lag
         }
      },
      success: function(resp) {
         var json = JSON.parse(resp);
         if (json.ok) {
            callback(); // close popup
         } else
            console.log(json.error);
      },
      error: function(resp) {
         console.log(resp.responseText);
      }
   });
}

function deleteTaskLink(linkId, callback) {
   $.ajax({
      url,
      type: 'POST',
      data: {
         deleteTaskLink: 1,
         id: linkId
      },
      success: function(resp) {
         var json = JSON.parse(resp);
         if (json.ok) {
            gantt.deleteLink(linkId);
            callback(); // close popup
         } else
            console.log(json.error);
      },
      error: function(resp) {
         console.log(resp.responseText);
      }
   });
}

// <<<<< Functions
