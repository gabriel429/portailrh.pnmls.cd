import { ref, computed } from 'vue'

export function usePagination() {
    const currentPage = ref(1)
    const lastPage = ref(1)
    const total = ref(0)
    const perPage = ref(25)

    function setFromResponse(paginatedData) {
        currentPage.value = paginatedData.current_page
        lastPage.value = paginatedData.last_page
        total.value = paginatedData.total
        perPage.value = paginatedData.per_page
    }

    const hasPages = computed(() => lastPage.value > 1)

    const pages = computed(() => {
        const p = []
        const current = currentPage.value
        const last = lastPage.value

        if (last <= 7) {
            for (let i = 1; i <= last; i++) p.push(i)
        } else {
            p.push(1)
            if (current > 3) p.push('...')
            for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                p.push(i)
            }
            if (current < last - 2) p.push('...')
            p.push(last)
        }
        return p
    })

    return { currentPage, lastPage, total, perPage, hasPages, pages, setFromResponse }
}
