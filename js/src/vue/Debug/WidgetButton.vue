<script setup>
    const props = defineProps({
        id: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        icon: {
            type: String,
            default: null,
        },
    });
</script>

<template>
  <li class="debug-toolbar-widget d-inline-block p-2" :data-glpi-debug-widget-id="id">
      <div :title="title" data-bs-toggle="tooltip" data-bs-placement="top">
          <button type="button" class="btn btn-icon border-0 p-1" data-bs-toggle="tab">
              <i v-if="icon" :class="icon + ' me-1'"></i>
              <span class="debug-text"></span>
          </button>
      </div>
  </li>
</template>

<style scoped>

</style>
