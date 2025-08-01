<script setup lang="ts">
import { useAppForm } from '@/composables/useAppForm';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Info, List } from 'lucide-vue-next';
import PendingTable from './table/pending_tbl.vue';
const { chainsaw_form, chainsaw } = useAppForm();

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
                <h1 class="text-xl font-semibold">Permit Application</h1>
            </div>

            <!-- Table Box Section -->
            <div class="box">
                <h2 class="title flex items-center gap-2">
                    <Info />
                    Kindly fill out the following details below to filter and retrieve specific permit application records efficiently.
                </h2>
                <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText v-model="chainsaw_form.application_no" class="w-full" />
                            <label>Application No.</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText class="w-full" />
                            <label>Application Type</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText class="w-full" />
                            <label>Applicant Name</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <DatePicker class="w-full" />
                            <label>Date of Application</label>
                        </FloatLabel>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="mt-4">
                        <FloatLabel>
                            <Select :options="['MS 382', 'MS 230', 'MS 162']" class="w-full" />
                            <label>Chainsaw Number</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText v-model="chainsaw_form.application_no" class="w-full" />
                            <label>Brand</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText class="w-full" />
                            <label>Model</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <Select :options="['MS 382', 'MS 230', 'MS 162']" class="w-full" />
                            <label>Office</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <InputText class="w-full" />
                            <label>Encoded By</label>
                        </FloatLabel>
                    </div>
                    <div class="mt-4">
                        <FloatLabel>
                            <DatePicker class="w-full" />
                            <label>Date of Expiration</label>
                        </FloatLabel>
                    </div>
                </div>

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
