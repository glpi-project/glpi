<script setup>
    /* global hotkeys, fuzzy */
    import {computed, ref, watch, useTemplateRef, onMounted, onUnmounted} from "vue";
    import { useAJAX } from "../Composables/useAJAX.js";

    let shortcut = `<kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>G</kbd>`;
    if (navigator.userAgent.includes('Mac')) {
        shortcut = `<kbd>⌥ (option)</kbd> + <kbd>⌘ (command)</kbd> + <kbd>G</kbd>`;
    }
    const shortcut_message = __("Tip: You can call this modal with %s keys combination").replace('%s', shortcut);
    const header_message = __('Go to menu');
    const placeholder = __("Start typing to find a menu");

    const input_text = ref(null);
    const all_menus = ref([]);
    const modal_el = useTemplateRef('ref_modal');
    const search_input = useTemplateRef('ref_search');
    const results_el = useTemplateRef('ref_results');
    const bs_modal = ref(null);
    const { ajaxGet } = useAJAX();

    onMounted(() => {
        bs_modal.value = new window.bootstrap.Modal(modal_el.value);
    });

    onUnmounted(() => {
        if (bs_modal.value) {
            bs_modal.value.dispose();
        }
    });

    const results = computed(() => {
        return fuzzy.filter(input_text.value, all_menus.value, {
            pre: '<b>',
            post: '</b>',
            extract: (el) => {
                return el.title;
            }
        });
    });
    // Allow using arrow keys to navigate results and make the li active
    const active_result = ref(0);
    const navigate_results = (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (active_result.value < results.value.length - 1) {
                active_result.value++;
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (active_result.value > 0) {
                active_result.value--;
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (results.value[active_result.value]) {
                window.location.href = results.value[active_result.value].original.url;
            } else if (results.value.length > 0) {
                window.location.href = results.value[0].original.url;
            }
        }
    };
    watch(input_text, () => {
        active_result.value = 0;
    });
    watch(active_result, () => {
        const active_li = results_el.value.querySelector('li.active');
        const new_active_li = results_el.value.querySelector(`li:nth-child(${active_result.value + 1})`);
        if (new_active_li) {
            if (active_li) {
                active_li.classList.remove('active');
            }
            new_active_li.classList.add('active');
            results_el.value.scrollTo({
                top: new_active_li.offsetTop - results_el.value.querySelector('li:first-child').offsetTop
            });
        }
    });

    const trigger_fuzzy = () => {
        if (all_menus.value.length === 0) {
            ajaxGet('/ajax/fuzzysearch.php', {
                params: {
                    method: 'GET',
                    dataType: 'json'
                }
            }).then(({data: menus}) => {
                all_menus.value = menus;
            });
        }
        bs_modal.value.show();
        input_text.value = '';
        search_input.value.focus();
    };
    const hideModal = () => {
        bs_modal.value.hide();
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('.trigger-fuzzy')) {
            trigger_fuzzy();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'g') {
            if ((e.ctrlKey || e.metaKey) && e.altKey) {
                e.preventDefault();
                trigger_fuzzy();
            }
        }
    });
</script>

<template>
    <Teleport to="body">
        <div ref="ref_modal" id="fuzzysearch" class="modal" tabindex="-1" @keydown.esc="hideModal" @keyup="navigate_results">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-arrow-big-right me-2"></i>
                            {{ header_message }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex" role="alert">
                            <i class="ti ti-alert-circle-filled fa-2x me-2"></i>
                            <p v-html="shortcut_message"></p>
                        </div>
                        <input ref="ref_search" type="text" class="form-control" :placeholder="placeholder" v-model="input_text">
                        <ul ref="ref_results" class="results list-group mt-2">
                            <li v-for="result in results" :key="result.index" class="list-group-item">
                                <a :href="result.original.url" v-html="result.string"></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>

</style>
