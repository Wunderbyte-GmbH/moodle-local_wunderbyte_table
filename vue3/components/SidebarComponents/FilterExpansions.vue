<template>
  <div class="scrollable-list">
    <div v-if="values">
      <ul>
        <li
          v-for="(value, key) in values"
          :key="key"
        >
          {{ value.value }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>

defineProps({
  values: {
    type: Object,
    default: null,
  },
});
</script>

<style scoped>
.scrollable-list {
  max-height: 400px;
  overflow-y: auto;
}
</style>