<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" style="background: #2A7B9B;
background: linear-gradient(90deg, rgba(42, 123, 155, 1) 0%, rgba(159, 237, 191, 1) 50%, rgba(87, 193, 225, 1) 100%, rgba(237, 221, 83, 1) 100%);"/>
        <AppContent >
            <slot />
        </AppContent>
    </AppShell>
</template>
