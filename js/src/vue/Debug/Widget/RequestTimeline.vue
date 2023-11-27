<script setup>
    import {computed, onMounted, ref} from "vue";

    const props = defineProps({
        initial_request: {
            type: Object,
            required: true
        },
        ajax_requests: {
            type: Array,
            required: true
        },
        content_area: {
            type: Object,
            required: true
        },
    });

    const emit = defineEmits(['change_request']);

    const TIMING_COLORS = {
        queued: '#808080',
        redirect: '#00aaaa',
        fetch: '#004400',
        dns: '#00cc88',
        connection: '#ffaa00',
        initial_connection: '#ffaa88',
        ssl: '#cc00cc',
        request: '#00aa00',
        response: '#0000ee',
    };

    const request_timings = computed(() => {
        const navigation_timings = window.performance.getEntriesByType('navigation')[0];
        const resource_timings = window.performance.getEntriesByType('resource');

        const timings = [];
        timings.push({
            type: 'navigation',
            name: navigation_timings.name,
            start: navigation_timings.startTime,
            end: navigation_timings.responseEnd,
            bounds: {},
            sections: {
                queued: [navigation_timings.startTime, navigation_timings.redirectStart],
                redirect: [navigation_timings.redirectStart, navigation_timings.redirectEnd],
                fetch: [navigation_timings.redirectEnd, navigation_timings.redirectStart],
                dns: [navigation_timings.domainLookupStart, navigation_timings.domainLookupEnd],
                connection: [navigation_timings.connectStart, navigation_timings.connectEnd],
                initial_connection: [navigation_timings.connectStart, navigation_timings.secureConnectionStart],
                ssl: [navigation_timings.secureConnectionStart, navigation_timings.connectEnd],
                request: [navigation_timings.requestStart, navigation_timings.responseStart], // Mainly waiting for the server to respond
                response: [navigation_timings.responseStart, navigation_timings.responseEnd],
            }
        });

        $.each(resource_timings, (i, resource_timing) => {
            timings.push({
                type: resource_timing.initiatorType,
                name: resource_timing.name,
                start: resource_timing.startTime,
                end: resource_timing.responseEnd,
                bounds: {},
                sections: {
                    queued: [resource_timing.startTime, resource_timing.redirectStart !== 0 ? resource_timing.redirectStart : resource_timing.domainLookupStart],
                    redirect: [resource_timing.redirectStart, resource_timing.redirectEnd],
                    fetch: [resource_timing.redirectEnd, resource_timing.redirectStart],
                    dns: [resource_timing.domainLookupStart, resource_timing.domainLookupEnd],
                    connection: [resource_timing.connectStart, resource_timing.connectEnd],
                    initial_connection: [resource_timing.connectStart, resource_timing.secureConnectionStart],
                    ssl: [resource_timing.secureConnectionStart, resource_timing.connectEnd],
                    request: [resource_timing.requestStart, resource_timing.responseStart], // Mainly waiting for the server to respond
                    response: [resource_timing.responseStart, resource_timing.responseEnd],
                }
            });
        });

        // find the longest duration based on the response end
        let longest_duration = 0;
        $.each(timings, (i, timing) => {
            const response_end = timing.sections.response[1];
            if (response_end > longest_duration) {
                longest_duration = response_end;
            }
        });

        return {
            end_ts: longest_duration,
            timings: timings
        };
    });

    const end_ts = request_timings.value.end_ts;
    const timings = request_timings.value.timings;
    const time_origin = window.performance.timeOrigin;

    // group timings into sections so that there are no overlaps (based on start and end times)
    const sections = [];
    const hasOverlap = (section, start, end) => {
        // check if the start or end time would fall within any of the timings in the given section
        let overlap = false;
        $.each(section, (i, timing) => {
            if ((start >= timing.start && start <= timing.end) || (end >= timing.start && end <= timing.end)) {
                overlap = true;
                return false;
            }
        });
        return overlap;
    };

    $.each(timings, (i, timing) => {
        let section = null;
        $.each(sections, (i, s) => {
            if (!hasOverlap(s, timing.start, timing.end)) {
                section = s;
                return false;
            }
        });

        if (section === null) {
            section = [timing];
            sections.push(section);
        } else {
            section.push(timing);
        }
    });

    const TIMELINE_REFRESH_RATE = 10; // 10 FPS
    const DIVIDER_WIDTH = 150;
    const ROW_HEIGHT = 4;
    const ROW_MARGIN = 2;

    const canvas_height = computed(() => {
        return sections.length * (ROW_HEIGHT + ROW_MARGIN) + 12;
    });

    const canvas_el = ref(null);
    /** @var Ref<CanvasRenderingContext2D> */
    const ctx = ref(null);
    const text_color = computed(() => {
        return canvas_el.value.css('color');
    });
    onMounted(() => {
        canvas_el.value = $(props.content_area).find('canvas').eq(0);
        ctx.value = canvas_el.value[0].getContext('2d');
        const refresh = window.setInterval(() => {
            if (canvas_el.value.length === 0) {
                window.clearInterval(refresh);
                return;
            }
            canvas_el.value.trigger('render');
        }, 1000 / TIMELINE_REFRESH_RATE);
        canvas_el.value.on('render', onCanvasRender);
    });

    const division_length = 100;

    function is_entry_selected(entry, entry_i, all_entries) {
        const selected_request = $(props.content_area).data('requests_request_id');
        let is_selected = false;
        if (selected_request === props.initial_request.id && entry.type === 'navigation') {
            is_selected = true;
        } else {
            const ajax_request = props.ajax_requests.find(r => r.id === selected_request);
            if (ajax_request === undefined) {
                return false;
            }
            const matches_by_url = [];
            $.each(all_entries, (i, e) => {
                if (e.name.endsWith(ajax_request.url)) {
                    matches_by_url.push({
                        i: i,
                        entry: e,
                    });
                }
            });
            if (matches_by_url.length === 1 && matches_by_url[0].i === entry_i) {
                is_selected = true;
            } else {
                // find the match that is closest to the section start
                let closest_match = null;
                matches_by_url.forEach((i, request) => {
                    if (closest_match === null) {
                        closest_match = request;
                        return;
                    }
                    const ajax_start = request.start - time_origin;
                    if (Math.abs(ajax_start - entry.start) < Math.abs(closest_match.start - entry.start)) {
                        closest_match = request;
                    }
                });
                is_selected = closest_match !== null && closest_match.i === entry_i;
            }
        }
        return is_selected;
    }

    const hover_data = ref(null);

    function onCanvasRender() {
        canvas_el.value.attr('width', canvas_el.value.parent().width());
        const canvas_width = canvas_el.value.width();
        const canvas_height = canvas_el.value.height();

        // round end_ts to nearest 100 ms
        const end_ts_rounded = Math.ceil(end_ts / 100) * 100;
        const division_count = Math.min(Math.ceil(canvas_width / DIVIDER_WIDTH), Math.ceil(end_ts_rounded / division_length));
        const dividers = [];
        for (let i = 0; i < division_count; i++) {
            dividers.push({
                canvas_x: Math.round(canvas_width / division_count * i),
                time: Math.ceil((end_ts_rounded / division_count) * i / 100) * 100
            });
        }

        ctx.value.fillStyle = '#80808040';
        ctx.value.fillRect(0, 0, canvas_width, canvas_height);
        // draw division lines
        $.each(dividers, (i, divider) => {
            ctx.value.fillStyle = text_color.value;
            ctx.value.strokeStyle = text_color.value;
            ctx.value.font = ctx.value.font.replace(/\d+px/, '10px');
            ctx.value.beginPath();
            ctx.value.moveTo(divider.canvas_x, 0);
            ctx.value.lineTo(divider.canvas_x, canvas_height);
            ctx.value.stroke();
            ctx.value.fillText(`${divider.time} ms`, divider.canvas_x + 2, 10);
        });

        // draw sections
        $.each(sections, (i, row) => {
            const row_y = (i * (ROW_HEIGHT + ROW_MARGIN)) + 12;
            $.each(row, (i, entry) => {
                const is_selected = is_entry_selected(entry, i, row);
                const timings = entry.sections;
                $.each(timings, (t, timing) => {
                    // if timing start and end are 0, skip
                    if (timing[0] === 0 && timing[1] === 0) {
                        return;
                    }
                    const color = TIMING_COLORS[t] || '#00aa00';
                    const width = Math.round((timing[1] - timing[0]) / end_ts * canvas_width);
                    const x = Math.round(timing[0] / end_ts * canvas_width);
                    entry.bounds[t] = {
                        x: x,
                        y: row_y,
                        width: width,
                        height: ROW_HEIGHT
                    };
                    ctx.value.fillStyle = color;
                    ctx.value.fillRect(x, row_y, width, ROW_HEIGHT);
                    if (is_selected) {
                        const stroke_style = ctx.value.strokeStyle;
                        ctx.value.strokeStyle = '#ffff00';
                        ctx.value.strokeRect(x, row_y, width, ROW_HEIGHT);
                        ctx.value.strokeRect(x - 1, row_y - 1, width + 2, ROW_HEIGHT + 2);
                        ctx.value.strokeStyle = stroke_style;
                    }
                });
            });
        });

        // draw tooltip
        if (hover_data.value !== null) {
            ctx.value.fillStyle = '#808080';
            const section = hover_data.value.target.section;
            const duration = section.sections[hover_data.value.target.timing][1] - section.sections[hover_data.value.target.timing][0];
            let section_name = section.name;
            if (section_name.length > 100) {
                section_name = section_name.slice(0, 100) + '...';
            }
            const text = `${section_name} ${hover_data.value.target.timing} (${duration.toFixed(0)} ms)`;
            ctx.value.font = ctx.value.font.replace(/\d+px/, '14px');
            const text_width = ctx.value.measureText(text).width;

            ctx.value.fillRect(section.bounds[hover_data.value.target.timing].x, section.bounds[hover_data.value.target.timing].y + ROW_HEIGHT, text_width + 4, 18);
            ctx.value.fillStyle = text_color.value;
            ctx.value.fillText(text, section.bounds[hover_data.value.target.timing].x + 2, section.bounds[hover_data.value.target.timing].y + ROW_HEIGHT + 14);
        }
    }

    function onCanvasMouseMove(e) {
        // get canvas x and y
        const canvas_x = e.offsetX;
        const canvas_y = e.offsetY;

        // find the section that the mouse is over
        let hover_target = null;
        $.each(sections, (i, row) => {
            $.each(row, (i, entry) => {
                $.each(entry.bounds, (t, bounds) => {
                    if (canvas_x >= bounds.x && canvas_x <= bounds.x + bounds.width && canvas_y >= bounds.y && canvas_y <= bounds.y + bounds.height) {
                        hover_target = {
                            section: entry,
                            timing: t
                        };
                        return false;
                    }
                });
                if (hover_target !== null) {
                    return false;
                }
            });
            if (hover_target !== null) {
                return false;
            }
        });
        if (hover_target !== null) {
            hover_data.value = {
                target: hover_target,
            };
            canvas_el.value.css('cursor', 'pointer');
        } else {
            hover_data.value = null;
            canvas_el.value.css('cursor', 'default');
        }
    }

    function onCanvasMouseLeave() {
        hover_data.value = null;
        canvas_el.value.css('cursor', 'default');
    }

    function onCanvasClick() {
        if (hover_data.value !== null) {
            const section = hover_data.value.target.section;
            let selected_request_id = null;
            if (section.type === 'navigation') {
                selected_request_id = props.initial_request.id;
            } else {
                const matches_by_url = [];
                $.each(props.ajax_requests, (i, request) => {
                    if (section.name.endsWith(request.url)) {
                        matches_by_url.push(request);
                    }
                });
                if (matches_by_url.length === 1) {
                    selected_request_id = matches_by_url[0].id;
                } else {
                    // find the match that is closest to the section start
                    let closest_match = null;
                    matches_by_url.forEach((i, request) => {
                        if (closest_match === null) {
                            closest_match = request;
                            return;
                        }
                        const ajax_start = request.start - time_origin;
                        if (Math.abs(ajax_start - section.start) < Math.abs(closest_match.start - section.start)) {
                            closest_match = request;
                        }
                    });
                    if (closest_match !== null) {
                        selected_request_id = closest_match.id;
                    }
                }
            }

            if (selected_request_id !== null) {
                $(props.content_area).data('requests_request_id', selected_request_id);
                emit('change_request');
                canvas_el.value.trigger('render');
            }
        }
    }
</script>

<template>
    <canvas :height="canvas_height" @mousemove="onCanvasMouseMove"
            @mouseleave="onCanvasMouseLeave" @click="onCanvasClick">
    </canvas>
</template>

<style scoped>

</style>
