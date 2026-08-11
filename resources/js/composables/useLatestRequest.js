import { onBeforeUnmount, ref } from 'vue'

export function useLatestRequest() {
    const pending = ref(false)
    let currentToken = 0

    function next() {
        const token = ++currentToken
        pending.value = true

        return {
            token,
            isCurrent: () => token === currentToken,
            done: () => {
                if (token === currentToken) {
                    pending.value = false
                }
            },
        }
    }

    function cancel() {
        currentToken += 1
        pending.value = false
    }

    onBeforeUnmount(cancel)

    return {
        pending,
        next,
        cancel,
        isCurrent: token => token === currentToken,
    }
}
