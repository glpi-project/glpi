<script setup>
    /* global tinycolor */
    import {computed} from "vue";

    const props = defineProps({
        parent_id: {
            type: String,
            required: false,
        },
        profiler_sections: {
            type: Array,
            required: true,
        },
        nest_level: {
            type: Number,
            required: true,
            default: 0,
        },
        parent_duration: {
            type: Number,
            required: true,
            default: 0,
        },
        hide_instant_sections: {
            type: Boolean,
            required: false,
            default: false,
        }
    });

    function getProfilerCategoryColor(category) {
        const predefined_colors = {
            core: '#89a2e1',
            db: '#9252ad',
            twig: '#64ad52',
            plugins: '#a077a6',
            search: '#b6803d',
        };
        let bg_color = '';
        if (predefined_colors[category] !== undefined) {
            bg_color = predefined_colors[category];
        } else {
            let hash = 0;
            for (let i = 0; i < category.length; i++) {
                hash = category.charCodeAt(i) + ((hash << 5) - hash);
            }
            let color = '#';
            for (let i = 0; i < 3; i++) {
                const value = (hash >> (i * 8)) & 0xFF;
                color += ('00' + value.toString(16)).substr(-2);
            }
            bg_color = color;
        }

        const contrasting_color = tinycolor.mostReadable(bg_color, tinycolor(bg_color).monochromatic(), {
            includeFallbackColors: true
        }).toHexString();

        return {
            bg_color: bg_color,
            text_color: contrasting_color
        };
    }

    const col_count = 6 + props.nest_level;

    function getProfilerData(parent_id) {
        const sections = props.profiler_sections.filter((section) => section.parent_id === parent_id);
        const sections_data = [];
        for (let i = 0; i < sections.length; i++) {
            const section = sections[i];
            const cat_colors = getProfilerCategoryColor(section.category);
            const duration = section.duration || (section.end - section.start);
            let percent_of_parent = 100;
            if (props.nest_level > 0 && props.parent_duration > 0) {
                percent_of_parent = ((duration / props.parent_duration) * 100).toFixed(2);
            } else if (props.parent_duration <= 0) {
                percent_of_parent = (100 / sections.length).toFixed(2);
            }

            const data = {
                id: section.id,
                name: escapeMarkupText(section.name),
                category: escapeMarkupText(section.category),
                bg_color: cat_colors.bg_color,
                text_color: cat_colors.text_color,
                start: section.start,
                end: section.end,
                duration: duration,
                percent_of_parent: percent_of_parent,
                has_children: props.profiler_sections.filter((child) => child.parent_id === section.id).length > 0,
            };
            sections_data.push(data);
        }
        return sections_data;
    }
    const top_level_data = computed(() => {
        return getProfilerData(props.parent_id);
    });

    const instant_threshold = 1.0;

    function hasUnfilteredSections(sections) {
        if (!props.hide_instant_sections) {
            return true;
        }
        for (let i = 0; i < sections.length; i++) {
            const section = sections[i];
            if (section.duration > instant_threshold) {
                return true;
            }
        }
        return false;
    }
</script>

<template>
    <table class="table table-striped card-table" v-show="hasUnfilteredSections(top_level_data)">
        <thead>
            <tr>
                <th class="nesting-spacer" v-for="i in nest_level" :key="i" aria-hidden="true"></th>
                <th>Category</th>
                <th>Name</th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>Percent of parent</th>
            </tr>
        </thead>
        <tbody>
            <template v-for="section in top_level_data">
                <tr :data-profiler-section-id="section.id" v-show="!props.hide_instant_sections || (section.duration > instant_threshold)">
                    <td class="nesting-spacer" v-for="i in nest_level" :key="i" aria-hidden="true"></td>
                    <td data-prop="category">
                        <span class="category-badge fw-bold" :style="`background-color: ${section.bg_color}; color: ${section.text_color}`">
                            {{ section.category }}
                        </span>
                    </td>
                    <td data-prop="name">{{ section.name }}</td>
                    <td data-prop="start">{{ section.start }}</td>
                    <td data-prop="end">{{ section.end }}</td>
                    <td data-prop="duration">{{ section.duration.toFixed(0) }} ms</td>
                    <td data-prop="percent_of_parent">{{ section.percent_of_parent }}%</td>
                </tr>
                <tr v-if="section.has_children" v-show="!props.hide_instant_sections || (section.duration > instant_threshold)">
                    <td :colspan="col_count">
                        <widget-profiler-table :parent_duration="section.duration" :nest_level="props.nest_level + 1"
                                               :profiler_sections="props.profiler_sections" :parent_id="section.id" :hide_instant_sections="props.hide_instant_sections"></widget-profiler-table>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</template>

<style scoped>
    .nesting-spacer {
        min-width: 2rem;
    }
    .category-badge {
        padding: 5px;
        border-radius: 25%;
    }
</style>
