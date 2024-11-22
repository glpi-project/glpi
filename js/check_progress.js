
/**
 * @param parameters
 * @param {string} parameters.key Mandatory. The progress bar's unique key.
 * @param {function} parameters.progress_callback The function that will be called for each progress response. If the return value is "false", this stops the progress checks.
 * @param {function} parameters.error_callback The function that will be called for each error, either exceptions or non-200 HTTP responses. Stops the progress checks by default, unless you return a true-ish value from the callback.
 */
function check_progress(parameters)
{
    if (!parameters.key) {
        throw new Error('Progress key is mandatory.');
    }

    const start_timeout = 250;

    setTimeout(async () => {
        try {
            const res = await fetch('/progress/check/' + parameters.key, {
                method: 'POST',
            });

            if (res.status === 404) {
                const cb_err_result = parameters.error_callback('Not found');
                if (!cb_err_result) {
                    return;
                }
            }

            if (res.status >= 300) {
                parameters.error_callback(`Invalid response from server, expected 200 or 404, found "${res.status}".`);
                return;
            }

            const json =  await res.json();

            debugger;
            if (json['key'] && json['started_at']) {
                if (parameters.progress_callback(json) !== false) {
                    // Recursive call, including the timeout
                    check_progress(parameters);
                }

                return;
            }

            parameters.error_callback(`Result error when checking progress:\n${err.message || err.toString()}`);
        } catch (err) {
            parameters.error_callback(`Request error when checking progress:\n${err.message || err.toString()}`);
        }
    }, start_timeout);
}
