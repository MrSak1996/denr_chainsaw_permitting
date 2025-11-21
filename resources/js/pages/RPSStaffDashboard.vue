<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import RPSStaffTable from './applications/table/rpsstaff_tbl.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'RPS Staff Dashboard',
        href: '/rps-staff-dashboard',
    },
];
</script>

<template>

    <Head title="RPS Chief Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-xl font-semibold">RPS Staff Dashboard</h1>
            <!-- Tailwind CSS Dashboard Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-5">
                <!-- Card 1 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">TOTAL NO. OF APPLICATION</h5>
                        <div class="flex items-center gap-2 text-sm text-green-600">🟢 <span>40</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 2 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">FOR RETURN / COMPLIANCE</h5>
                        <div class="flex items-center gap-2 text-sm text-yellow-600">🟡 <span>38</span></div>

                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 3 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">ENDORSED</h5>
                        <div class="flex items-center gap-2 text-sm text-red-600">🔴 <span>26</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 4 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">APPROVED APPLICATION</h5>
                        <div class="flex items-center gap-2 text-sm text-green-600">🟢 <span>100</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 5 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">REJECTED APPLICATION</h5>
                        <div class="flex items-center gap-2 text-sm text-yellow-600">🟡 <span>For Review</span></div>

                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <!-- Middle box (takes 3 columns - wider) -->
                <div
                    class="box col-span-4 flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h4 class="title flex mb-4 gap-2">
                        <Info /> List of Applicants Submitted
                    </h4>


                    <RPSStaffTable />
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
