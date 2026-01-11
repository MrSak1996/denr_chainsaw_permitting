<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import ardts_table from './applications/table/ardts_tbl.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ARDTS Dashboard',
        href: '/ardts-dashboard',
    },
];
</script>

<template>
    <Head title="RPS Chief Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-xl font-semibold">ARDTS Dashboard</h1>
      

            <div class="grid grid-cols-1 gap-4">
                <div class="box col-span-4 flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h4 class="title flex mb-4 gap-2"> <Info/> List of Applicants Submitted</h4>                 
                    <ardts_table />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<style scoped>
.box {
    background-color: #fff;
    border-top: 4px solid #00943a;
    margin-bottom: 20px;
    padding: 20px;
    -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -o-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}

.box .title {
    border-bottom: 1px solid #e0e0e0;
    color: #432c0b !important;
    font-weight: bold;
    margin-bottom: 20px;
    margin-top: 0;
    padding-bottom: 10px;
    padding-top: 0;
    text-transform: uppercase;
    font-size: 10pt;
}
</style>
