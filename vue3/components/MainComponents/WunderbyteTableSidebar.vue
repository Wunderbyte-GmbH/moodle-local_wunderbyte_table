<template>
  <div>
    Sidebar
    <div v-if="sidebardata">
      <ul>
        <li
          v-for="(value, key) in sidebardata.categories"
          :key="key"
        >
          <FilterListElement :category="value" />
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import FilterListElement from '../SidebarComponents/FilterListElement.vue'

defineProps({
  sidebardata: {
    type: Object,
    default: null,
  },
});
</script>