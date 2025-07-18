<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Folder, Info } from 'lucide-vue-next';
import ToggleButton from 'primevue/togglebutton';
import { ref, watch } from 'vue';
import application_form from './forms/application_form.vue';
import company_application_form from './forms/company_application_form.vue';

const checked = ref<boolean | null>(null); // no default selected
const hasSelected = ref(false); // only show form after user clicks toggle

watch(checked, (val) => {
    if (val !== null) hasSelected.value = true;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applicants/index' },
];
</script>

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
/* Base style for ToggleButton - Green (unchecked/default state) */
/* Default state - Green */
/* Base style for ToggleButton - Green (unchecked/default state) */
.p-togglebutton {
  font-weight: 600;
  font-size: 1rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  border: none;
  background-color: #22c55e; /* green-500 */
  color: white;
  transition: background-color 0.3s ease, filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
  filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
  background-color: #15803d !important; /* green-700 */
  border-color: #166534;
  color: rgb(0, 0, 0);
}

/* Fix inner white background */
.p-togglebutton.p-togglebutton-checked .p-togglebutton-content {
  background-color: #15803d !important;
  box-shadow: none;
  color: white !important;
}

/* Ensure label and icon are white in all states */
.p-togglebutton .p-togglebutton-icon,
.p-togglebutton .p-togglebutton-label {
  color: white !important;
}




</style>


<template>

    <Head title="Chainsaw Permit Application" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <div class="flex items-center gap-2 text-sm">
                <Folder class="h-5 w-5" />
                <h1 class="text-xl font-semibold">Chainsaw Permit Application Form</h1>
            </div>

            <div class="box">
                <h2 class="title flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2">
                        <Info class="text-primary" />
                        Application Instructions
                    </span>

                    <div class="flex items-center gap-4 mb-4">
                        <label class="text-gray-700 font-medium">Select Applicant Type:</label>

                        <ToggleButton v-model="checked" onLabel="Company" offLabel="Individual" onIcon="pi pi-briefcase"
                            offIcon="pi pi-user" />

                    </div>
                </h2>

                <p class="mb-4 text-sm text-gray-700">
                    Please complete all required fields in this form to apply for a <strong>Permit to Purchase
                        Chainsaw</strong>. The information you
                    provide must be accurate and verifiable. Incomplete or false entries may result in the disapproval
                    of your application.
                </p>

                <ul class="mb-4 list-disc pl-5 text-sm text-gray-700">
                    <li>Fill in your complete name, civil status, and date of birth accurately.</li>
                    <li>Ensure your contact details (mobile number, barangay, municipality, province, and region) are
                        correct and reachable.</li>
                    <li>Provide the date when the application is filed.</li>
                    <li>Use the calendar picker to set your birthdate and application date properly.</li>
                    <li>All fields marked as required must be completed before proceeding to the next step.</li>
                </ul>

                <!-- Conditional form display -->
                <div v-if="hasSelected">
                    <application_form v-if="checked === false" />
                    <company_application_form v-else />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
