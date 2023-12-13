<script setup>
    import {onMounted, ref} from "vue";

    const emit = defineEmits(['refreshButton']);

    const themes = ref([]);
    const rand = Math.floor(Math.random() * 1000000000);

    onMounted(() => {
        $.ajax({
            url: CFG_GLPI['root_doc'] + '/ajax/debug.php',
            data: {
                action: 'get_themes'
            }
        }).then((data) => {
            themes.value = data;
        });
    });

    function getCurrentTheme() {
        return document.documentElement.attributes['data-glpi-theme'].value;
    }

    function onThemeChange() {
        const selection = $(`#theme${rand}`).val();
        const new_theme = themes.value.find((theme) => theme['key'] === selection);
        if (new_theme !== undefined) {
            document.documentElement.attributes['data-glpi-theme'].value = new_theme['key'];
            document.documentElement.attributes['data-glpi-theme-dark'].value = new_theme['is_dark'] ? '1' : '0';
        }
        emit('refreshButton');
    }
</script>

<template>
    <div class="py-2 px-3 col-12">
        <div class="alert alert-info">
            <span>This change only applies to the current page</span>
        </div>
        <div class="d-flex">
            <div class="form-group row">
                <label class="col-5" :for="`theme${rand}`">Palette</label>
                <div class="col-7">
                    <div class="input-group">
                        <select class="form-select" :id="`theme${rand}`" :value="getCurrentTheme()" @change="onThemeChange">
                            <option v-for="theme in themes" :key="theme['key']" :value="theme['key']" v-text="theme['name']"></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
