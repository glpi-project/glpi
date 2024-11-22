
/**
 * @param parameters
 * @param {HTMLElement} parameters.container Mandatory. The progress bar's unique key.
 * @param {string} parameters.key Mandatory. The progress bar's unique key.
 * @param {null|function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
 * @param {null|function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress checks by default, unless you return a true-ish value from the callback, or unless the error is non-recoverable and implies stopping
 *
 * @return {{start: function, stop: function}}
 */
function create_progress_bar(parameters)
{
    if (!parameters.key) {
        throw new Error('Progress key is mandatory.');
    }
    if (!parameters.container) {
        throw new Error('Progress container is mandatory.');
    }
    if (!(parameters.container instanceof HTMLElement)) {
        throw new Error('Progress key must be an HTML element, "' + (parameters.container?.constructor?.name || typeof parameters.container) + '" found.');
    }

    const main_container = document.createElement('div');
    main_container.innerHTML = `
        <div style="padding-left: 20px; padding-right: 20px;">
            <div class="progress" style="height: 15px;">
                <div class="progress-bar bg-info" role="progressbar" style="width:0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div class="messages_container"></div>
        </div>
    `;
    const progress_bar = main_container.querySelector('.progress-bar');
    const messages_container = main_container.querySelector('.messages_container');

    parameters.container.appendChild(main_container);

    const start_timeout = 250;

    let is_running = true;
    let abort_controller = new AbortController();

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * @param {null|number} percentage
     */
    function set_bar_percentage(percentage) {
        progress_bar.style.width = `${typeof percentage === 'number' ? percentage : 0}%`;
        progress_bar.innerHTML = typeof percentage === 'number' ? `${Math.floor(percentage)}%` : '-';
        progress_bar.setAttribute('aria-valuenow', percentage || '0');

    }

    /**
     * @param {number} value
     * @param {number} max
     * @param {null|string} text
     */
    function update_progress(value, max, text) {
        value = value || 0;
        max = max || 1;
        const percentage = (value / max * 100);

        set_bar_percentage(percentage);

        if (text && text.length) {
            console.info('Text', {text});
            messages_container.innerHTML = escapeHtml(text.trim()).replace(/\n/gi, '<br>');
        }
    }

    function reset_progress() {
        is_running = false;

        set_bar_percentage(null);
        progress_bar.classList.remove('bg-info');
        progress_bar.classList.add('bg-warning');
    }

    async function check_progress() {
        setTimeout(async () => {
            is_running = true;
            try {
                const res = await fetch('/progress/check/' + parameters.key, {
                    method: 'POST',
                    signal: abort_controller.signal,
                });

                if (res.status === 404) {
                    const cb_err_result = parameters?.error_callback('Not found');
                    if (!cb_err_result) {
                        reset_progress();
                        return;
                    }
                }

                if (res.status >= 300) {
                    parameters?.error_callback(`Invalid response from server, expected 200 or 404, found "${res.status}".`);
                    reset_progress();
                    return;
                }

                const json =  await res.json();

                if (json['key'] && json['started_at']) {
                    update_progress(json.current, json.max, json.data);
                    if (
                        (
                            !parameters.progress_callback
                            || (parameters.progress_callback && parameters.progress_callback(json) !== false)
                        )
                        && !json['finished_at']
                    ) {
                        // Recursive call, including the timeout
                        await check_progress();
                    }

                    return;
                }

                parameters?.error_callback(`Result error when checking progress:\n${err.message || err.toString()}`);
                reset_progress();
            } catch (err) {
                parameters?.error_callback(`Request error when checking progress:\n${err.message || err.toString()}`);
                reset_progress();
            }
        }, start_timeout);
    }

    function stop(new_percentage) {
        if (!is_running) {
            return;
        }

        try {
            abort_controller.abort();
        } finally {
            abort_controller = new AbortController();
        }
        if (typeof new_percentage === 'number') {
            set_bar_percentage(new_percentage);
        }
    }

    function start() {
        progress_bar.classList.add('bg-info');
        progress_bar.classList.remove('bg-warning');
        set_bar_percentage(0);

        check_progress().then(() => console.info('Progress started'));
    }

    return {
        start,
        stop,
    };
}
