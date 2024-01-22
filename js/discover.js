function launchIntro(endpoint, lessonId, steps, endingSteps = [], isEndingLesson = false) {
    var intro = introJs();
    intro.setOptions({
        steps: steps,
    });
    intro.oncomplete(function () {
        intro.exit();

        if (!isEndingLesson) {
            $.ajax({
                url: endpoint,
                method: 'POST',
                data: {
                    'action': 'lesson-done',
                    'lesson_id': lessonId
                }
            });

            if (endingSteps.length > 0) {
                // Use setTimeout to delay the execution of the launchIntro function
                // This allows the function to be executed after all other events in the event loop have been processed
                setTimeout(function () {
                    launchIntro(endpoint, lessonId, endingSteps, [], true);
                }, 0);
            }
        }
    });
    intro.onbeforechange(function () {
        // Disable scroll when intro is running
        document.body.style.overflow = 'hidden';

        if (steps && steps[this._currentStep] && steps[this._currentStep].actions) {
            var step = steps[this._currentStep];
            var actions = step.actions;

            var actionEntries = Object.entries(actions);
            for (var i = 0; i < actionEntries.length; i++) {
                var action = actionEntries[i][0];
                var data = actionEntries[i][1];

                switch (action) {
                    case 'dropdown-show':
                        var dropdown = document.querySelector(data.dropdown);
                        if ($(dropdown).length === 0) {
                            return;
                        }

                        // Use a promise to wait for the animation to finish before continuing
                        // This action prevents potential graphic bugs
                        return new Promise(function (resolve) {
                            // Note the setTimeout with no second argument (milliseconds) allows you to queue the function
                            -                           // on event loop and run it after all events were processed (including the click closing the dropdown)
                                setTimeout(function () {
                                    // Show dropdown
                                    $(dropdown).dropdown('show');

                                    // Block the dropdown from closing while step is running
                                    $(dropdown).on('hide.bs.dropdown', function (e) {
                                        if (step === steps[intro._currentStep]) {
                                            e.preventDefault();
                                        }
                                    });

                                    // Refresh intro when dropdown animation ends
                                    if ($(dropdown).find('.dropdown-menu').hasClass('animate__animated')) {
                                        $(dropdown).find('.dropdown-menu').on('animationend', function () {
                                            resolve();
                                        });
                                    } else {
                                        resolve();
                                    }
                                });
                        });
                }
            }
        }
    });
    intro.onexit(function () {
        // Enable scroll when intro is finished or skipped
        document.body.style.overflow = 'auto';
    });

    intro.start();
}
