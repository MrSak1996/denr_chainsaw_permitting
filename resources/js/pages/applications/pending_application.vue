<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Info, List } from 'lucide-vue-next';
import PendingTable from './table/pending_tbl.vue';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pending Applications',
        href: '/applicants/pending_application',
    },
];
</script>

<template>
    <Head title="Chainsaw Permit Application" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <!-- Header Section -->
            <div class="flex items-center gap-2 text-sm">
                <List class="h-5 w-5" />
                <h1 class="text-xl font-semibold">Pending Permit Application</h1>
            </div>

            <!-- Table Box Section -->
            <div class="box">
                <h2 class="title flex items-center gap-2">
                    <Info />
                    Instruction
                </h2>
                <p class="mb-4 text-sm text-gray-700">
                    This table displays all <strong>pending chainsaw permit applications</strong>. As an administrator, you can:
                </p>
                <ul class="mb-4 list-disc pl-5 text-sm text-gray-700">
                    <li>View and review each applicant's submitted information and requirements.</li>
                    <li>Monitor the current status and track application progress in real-time.</li>
                    <li>Edit or update application details before approval.</li>
                    <li>Delete invalid or duplicate entries.</li>
                    <li>Export data for documentation and offline processing.</li>
                </ul>
                <p class="text-sm text-gray-600">
                    Use the search bar to filter applications and the buttons provided to manage each entry effectively.
                </p>

                <PendingTable />
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
