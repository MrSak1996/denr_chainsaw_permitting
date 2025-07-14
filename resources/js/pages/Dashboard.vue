<script setup lang="ts">
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import dayGridPlugin from '@fullcalendar/daygrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head,Link } from '@inertiajs/vue3';

const calendarOptions = {
    plugins: [dayGridPlugin],
    initialView: 'dayGridMonth',
    height: 450, // or 'auto'

    events: [
        
        { title: 'Office Meeting', date: '2025-06-22' },
        { title: 'Site Visit', date: '2025-06-23' },
    ],
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const activeTab = ref<'my' | 'office'>('my');
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-xl font-semibold">Dashboard</h1>
            <div class="grid auto-rows-min gap-4 md:grid-cols-5">
                <!-- Card 1 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">No. of Leave Credits</h5>
                        <div class="flex items-center gap-2 text-sm text-green-600">🟢 <span>Confirmed</span></div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-blue-600">✅ <span>Completed</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 2 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">Status 1</h5>
                        <div class="flex items-center gap-2 text-sm text-yellow-600">🟡 <span>Pending</span></div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-orange-600">⏳ <span>In Progress</span>
                        </div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 3 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">Status 2</h5>
                        <div class="flex items-center gap-2 text-sm text-red-600">🔴 <span>Rejected</span></div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-gray-600">❌ <span>Cancelled</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 4 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">Status 3</h5>
                        <div class="flex items-center gap-2 text-sm text-green-600">🟢 <span>Approved</span></div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-blue-600">✅ <span>Completed</span></div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>

                <!-- Card 5 -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border relative flex aspect-video flex-col justify-between overflow-hidden rounded-xl border p-4">
                    <div>
                        <h5 class="title mb-2">Status 4</h5>
                        <div class="flex items-center gap-2 text-sm text-yellow-600">🟡 <span>For Review</span></div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-orange-600">⏳ <span>In Progress</span>
                        </div>
                    </div>
                    <button class="mt-4 text-sm font-medium text-green-700 hover:underline">View More Details →</button>
                </div>
            </div>

            <div class="grid grid-cols-6 gap-4">
                <!-- Left box (2 columns) -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border col-span-2 h-[500px] overflow-hidden rounded-xl border p-4">
                    <h4 class="title mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-[#432c0b] uppercase">
                        Programs and Projects</h4>

                    <ul class="list-disc space-y-2 pl-5 text-sm text-gray-700">
                        <li>
                            <a href="https://riis.denrcalabarzon.com" title="Document Management System" target="_blank"
                                class="transition hover:text-green-700 hover:underline">
                                Regional ICT Inventory System
                            </a>
                        </li>
                        <li>
                            <a href="#" title="Electronic Client Satisfaction Measure System" target="_blank"
                                class="transition hover:text-green-700 hover:underline">
                                DENR E-Serv (Queuing Management System)
                            </a>
                        </li>
                        <li>
                            <Link href="/cps/dash_cps" title="Electronic Client Satisfaction Measure System - Admin Panel"
                               class="transition hover:text-green-700 hover:underline">
                                Chainsaw Permitting Management System
                        </Link>
                        </li>
                        <li>
                            <a href="#" title="Electronic Client Satisfaction Measure System - Admin Panel"
                                target="_blank" class="transition hover:text-green-700 hover:underline">
                                DATS (DENR Application Tracking System)
                            </a>
                        </li>
                        <li>
                            <a href="#" title="Electronic Client Satisfaction Measure System - Admin Panel"
                                target="_blank" class="transition hover:text-green-700 hover:underline">
                                Chainsaw Registration System
                            </a>
                        </li>
                        <li>
                            <a href="#" title="Electronic Client Satisfaction Measure System - Admin Panel"
                                target="_blank" class="transition hover:text-green-700 hover:underline">
                                Travel Order Management System
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Middle box (takes 3 columns - wider) -->
                <div
                    class="box border-sidebar-border/70 dark:border-sidebar-border col-span-4 flex h-[590px] flex-col overflow-hidden rounded-xl border p-4">
                    <h4 class="title mb-4">Calendar of Activities</h4>

                    <!-- Tabs -->
                    <div class="mb-4 flex border-b border-gray-200">
                        <button @click="activeTab = 'my'" :class="[
                            'border-b-2 px-4 py-2 text-sm font-medium transition',
                            activeTab === 'my'
                                ? 'border-green-600 text-green-700'
                                : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600',
                        ]">
                            My Activities
                        </button>
                        <button @click="activeTab = 'office'" :class="[
                            'border-b-2 px-4 py-2 text-sm font-medium transition',
                            activeTab === 'office'
                                ? 'border-green-600 text-green-700'
                                : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600',
                        ]">
                            Office Activities
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-1 space-y-4 overflow-y-auto">
                        <!-- My Activities -->
                        <div v-if="activeTab === 'my'" class="space-y-2 text-sm text-gray-700">
                            <p>🗓️ Your personal upcoming activities will be listed here.</p>
                            <div class="h-auto w-full rounded-md border border-gray-200 p-2 shadow-sm">
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>

                        <!-- Office Activities -->
                        <div v-else class="space-y-2 text-sm text-gray-700">
                            <p>🏢 All office-wide activities will be displayed here.</p>
                            <div class="h-auto w-full rounded-md border border-gray-200 p-2 shadow-sm">
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>
                    </div>
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
