import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAuth() {
    const page = usePage()

    const user = computed(() => page.props.auth.user)
    const userId = computed(() => user.value?.id)

    return { user, userId }
}
