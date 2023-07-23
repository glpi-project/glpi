<script setup>
    import {onMounted} from "vue";

    const props = defineProps({
        current_profile: {
            type: Object,
            required: false
        },
    });

    const appendGlobals = (data, container) => {
        if (data === undefined || data === null) {
            container.append('Empty array');
            return;
        }

        let data_string = data;
        try {
            data_string = JSON.stringify(data, null, ' ');
        } catch (e) {
            if (typeof data !== 'string') {
                container.append('Empty array');
                return;
            }
        }

        const editor = new window.CodeMirror.EditorView({
            extensions: [
                window.CodeMirror.setup,
                window.CodeMirror.languages.json(),
                window.CodeMirror.EditorView.lineWrapping,
                window.CodeMirror.EditorView.contentAttributes.of({contenteditable: false}),
            ],
            doc: data_string
        });
        container.append(editor.dom);
    };

    const rand = Math.floor(Math.random() * 1000000);

    const globals = props.current_profile.globals;
    onMounted(() => {
        appendGlobals(globals['post'], $(`#debugpanel${rand} #debugpost${rand}`));
        appendGlobals(globals['get'], $(`#debugpanel${rand} #debugget${rand}`));
        appendGlobals(globals['session'], $(`#debugpanel${rand} #debugsession${rand}`));
        appendGlobals(globals['server'], $(`#debugpanel${rand} #debugserver${rand}`));
    });
</script>

<template>
    <div>
        <div :id="`debugpanel${rand}`" class="container-fluid card p-0 border-top-0">
            <ul class="nav nav-pills" data-bs-toggle="tabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" :href="`#debugpost${rand}`">POST</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" :href="`#debugget${rand}`">GET</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" :href="`#debugsession${rand}`">SESSION</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" :href="`#debugserver${rand}`">SERVER</a></li>
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
    }
</style>
