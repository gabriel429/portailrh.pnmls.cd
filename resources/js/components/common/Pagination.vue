<template>
  <nav v-if="hasPages" aria-label="Pagination">
    <ul class="pagination justify-content-center mb-0">
      <li class="page-item" :class="{ disabled: currentPage === 1 }">
        <a class="page-link" href="#" @click.prevent="$emit('page-change', currentPage - 1)">&laquo;</a>
      </li>
      <li
        v-for="page in pages"
        :key="page"
        class="page-item"
        :class="{ active: page === currentPage, disabled: page === '...' }"
      >
        <a class="page-link" href="#" @click.prevent="page !== '...' && $emit('page-change', page)">
          {{ page }}
        </a>
      </li>
      <li class="page-item" :class="{ disabled: currentPage === lastPage }">
        <a class="page-link" href="#" @click.prevent="$emit('page-change', currentPage + 1)">&raquo;</a>
      </li>
    </ul>
  </nav>
</template>

<script setup>
defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    pages: { type: Array, required: true },
    hasPages: { type: Boolean, required: true },
})

defineEmits(['page-change'])
</script>
