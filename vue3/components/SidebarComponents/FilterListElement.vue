<template>
  <div class="filter-element">
    <div class="filter-header">
      <span>{{ category.name }}</span>
      <button @click="toggleExpanded" class="expand-button">
        <i v-if="expanded" class="fas fa-minus" />
        <i v-else class="fas fa-plus" />
      </button>
    </div>
    <transition 
      name="fade"
      mode="out-in"
    >
      <div v-if="expanded && category.default" class="filter-content">
        <FilterExpansions :values="category.default.values" />
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import FilterExpansions from './FilterExpansions.vue'

defineProps({
  category: {
    type: Object,
    default: null,
  },
});

const expanded = ref(false);

const toggleExpanded = () => {
  expanded.value = !expanded.value;
};
</script>

<style scoped>
.filter-element {
  width: 100%;
  margin: 1rem;
  padding: 1rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.expand-button {
  background: none;
  border: none;
  cursor: pointer;
}

.filter-content {
  padding: 1rem;
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.5s ease;
}

.fade-enter-from, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
  opacity: 0;
  transform: translateX(30px);
}
</style>