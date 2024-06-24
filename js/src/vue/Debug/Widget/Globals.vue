<script setup>
    import {onMounted, watch} from "vue";

    const props = defineProps({
        current_profile: {
            type: Object,
            required: false
        },
    });

    const monaco_editors = [];

    const appendGlobals = (data, container) => {
        if (data === undefined || data === null) {
            container.append('Empty array');
            return;
        }

        container.empty();
        let data_string = data;
        try {
            data_string = JSON.stringify(data, null, ' ');
        } catch (e) {
            if (typeof data !== 'string') {
                container.append('Empty array');
                return;
            }
        }

        const editor_element_id = `monacoeditor${Math.floor(Math.random() * 1000000)}`;
        const editor_element = document.createElement('div');
        editor_element.setAttribute('id', editor_element_id);
        editor_element.classList.add('monaco-editor-container');
        container.append(editor_element);
        window.GLPI.Monaco.createEditor(editor_element_id, 'javascript', data_string, [], {
            readOnly: true,
        }).then((editor) => {
            // Fold everything recursively by default except the first level
            editor.editor.trigger('fold', 'editor.foldAll');
            editor.editor.trigger('unfold', 'editor.unfold', {
                levels: 1
            });
            editor.editor.layout();
            monaco_editors.push(editor);
        });
    };

    const updateEditorLayouts = () => {
        monaco_editors.forEach((editor) => {
            editor.editor.layout();
        });
    };

    const rand = Math.floor(Math.random() * 1000000);

    function refreshGlobals() {
        appendGlobals(props.current_profile.globals['post'], $(`#debugpanel${rand} #debugpost${rand}`));
        appendGlobals(props.current_profile.globals['get'], $(`#debugpanel${rand} #debugget${rand}`));
        appendGlobals(props.current_profile.globals['session'], $(`#debugpanel${rand} #debugsession${rand}`));
        appendGlobals(props.current_profile.globals['server'], $(`#debugpanel${rand} #debugserver${rand}`));
    }

    onMounted(() => {
        refreshGlobals();
    });
    watch(() => props.current_profile.globals, () => {
        refreshGlobals();
    });
</script>

<template>
    <div>
        <div :id="`debugpanel${rand}`" class="container-fluid card p-0 border-top-0">
            <ul class="nav nav-pills" data-bs-toggle="tabs">
                <li class="nav-item" @click="updateEditorLayouts"><a class="nav-link active" data-bs-toggle="tab" :href="`#debugpost${rand}`">POST</a></li>
                <li class="nav-item" @click="updateEditorLayouts"><a class="nav-link" data-bs-toggle="tab" :href="`#debugget${rand}`">GET</a></li>
                <li class="nav-item" @click="updateEditorLayouts"><a class="nav-link" data-bs-toggle="tab" :href="`#debugsession${rand}`">SESSION</a></li>
                <li class="nav-item" @click="updateEditorLayouts"><a class="nav-link" data-bs-toggle="tab" :href="`#debugserver${rand}`">SERVER</a></li>
            </ul>

            <div class="card-body overflow-auto p-1">
                <div class="tab-content">
                    <div :id="`debugpost${rand}`" class="cm-s-default tab-pane active"></div>
                    <div :id="`debugget${rand}`" class="cm-s-default tab-pane"></div>
                    <div :id="`debugsession${rand}`" class="cm-s-default tab-pane"></div>
                    <div :id="`debugserver${rand}`" class="cm-s-default tab-pane"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .container-fluid {
        min-width: 400px;
        max-width: 90vw;

        .tab-content {
            min-height: 30vh;
        }

        &::v-deep(.monaco-editor-container .monaco-editor) {
            min-height: 30vh !important;
        }
    }
</style>
