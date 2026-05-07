/**
 * useRoleLayout — single source of truth for role-based layout selection.
 *
 * Priority order (first match wins):
 *   Client       → ClientLayout
 *   Employee     → EmployeeLayout
 *   Finance Admin → FinanceLayout
 *   (all others: Super Admin, Branch Manager) → AdminLayout
 *
 * Usage in any page component:
 *   import { useRoleLayout } from '@/composables/useRoleLayout';
 *   const { currentLayout } = useRoleLayout();
 *   // template: <component :is="currentLayout"> ... </component>
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout   from '@/Layouts/AdminLayout.vue';
import ClientLayout  from '@/Layouts/ClientLayout.vue';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import FinanceLayout from '@/Layouts/FinanceLayout.vue';

export function useRoleLayout() {
    const page = usePage();

    const currentLayout = computed(() => {
        const roles = page.props.auth?.user?.roles ?? [];
        if (roles.includes('Client'))        return ClientLayout;
        if (roles.includes('Employee'))      return EmployeeLayout;
        if (roles.includes('Finance Admin')) return FinanceLayout;
        return AdminLayout; // Super Admin, Branch Manager
    });

    return { currentLayout };
}
