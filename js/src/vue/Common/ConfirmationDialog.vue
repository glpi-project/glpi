<script setup>
    import {onUnmounted, useTemplateRef, onMounted, useId} from "vue";

    defineProps({
        appendTo: {
            type: String,
            required: false,
            default: 'body',
        },
        title: {
            type: String,
            default: _n('Information', 'Information', 1),
        },
        message: {
            type: String,
            required: true,
        },
        confirm_class: {
            type: String,
            default: 'btn-primary',
        },
        cancel_class: {
            type: String,
            default: 'btn-outline-secondary',
        },
        confirm_text: {
            type: String,
            default: _x('button', 'Confirm'),
        },
        cancel_text: {
            type: String,
            default: _x('button', 'Cancel'),
        },
    });

    const modal_title_id = useId();
    const modal_el = useTemplateRef('modal_ref');
    const btn_confirm_el = useTemplateRef('btn_confirm_ref');
    const btn_cancel_el = useTemplateRef('btn_cancel_ref');
    let modal = null;

    defineExpose({
        confirm: async () => {
            modal.show();

            return new Promise((resolve) => {
                const onConfirm = () => {
                    resolve(true);
                };
                const onCancel = () => {
                    resolve(false);
                };
                modal_el.value.addEventListener('hidden.bs.modal', onCancel, { once: true });
                btn_confirm_el.value.addEventListener('click', onConfirm, { once: true });
                btn_cancel_el.value.addEventListener('click', onCancel, { once: true });
            });
        }
    });

    onMounted(() => {
        if (!modal) {
            modal = new bootstrap.Modal(modal_el.value, {});
            // Work around Bootstrap's poor (or lack of) focus management.
            // When closed, the focus can sometimes remain in the modal which is not valid when it adds aria-hidden="true" (https://github.com/twbs/bootstrap/issues/41005)
            modal_el.value.addEventListener('show.bs.modal', (e) => {
                e.target.inert = false;
            });
            modal_el.value.addEventListener('hide.bs.modal', (e) => {
                e.target.inert = true;
            });
        }
    });

    onUnmounted(() => {
        if (modal) {
            modal.dispose();
            modal = null;
        }
    });
</script>

<template>
    <Teleport :to="appendTo">
        <div ref="modal_ref" class="modal fade" tabindex="-1" role="dialog" :aria-labelledby="modal_title_id" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 :id="modal_title_id" class="modal-title" v-text="title"></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="_x('Close', 'button')"></button>
                    </div>
                    <div class="modal-body">
                        <p v-text="message"></p>
                    </div>
                    <div class="modal-footer">
                        <button ref="btn_cancel_ref" type="button" :class="`btn ${cancel_class}`" data-bs-dismiss="modal" v-text="cancel_text"></button>
                        <button ref="btn_confirm_ref" type="button" :class="`btn ${confirm_class}`" data-bs-dismiss="modal" v-text="confirm_text"></button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>

</style>
