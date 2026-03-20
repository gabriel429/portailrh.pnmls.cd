import { ref } from 'vue'
import { useUiStore } from '@/stores/ui'

export function useToast() {
    const ui = useUiStore()

    function success(message) {
        ui.addToast(message, 'success')
    }

    function error(message) {
        ui.addToast(message, 'danger')
    }

    function info(message) {
        ui.addToast(message, 'info')
    }

    return { success, error, info }
}
