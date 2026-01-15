<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Folder, Info } from 'lucide-vue-next';
import ToggleButton from 'primevue/togglebutton';
import { ref, watch, onMounted } from 'vue';
import application_form from './forms/application_form.vue';
import company_application_form from './forms/company_application_form.vue';

// ---------------------
// STATE
// ---------------------
const checked = ref<boolean | null>(null);
const hasSelected = ref(false);

// ---------------------
// BREADCRUMBS
// ---------------------
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applicants/index' },
];

// ---------------------
// UTILITY: Get URL Parameter Safely
// ---------------------
const getApplicationTypeFromUrl = (): string | null => {
    const params = new URLSearchParams(window.location.search);
    return params.get('type');
};

// ---------------------
// UI REACTION: When user toggles button
// ---------------------
watch(checked, (value) => {
    if (value !== null) {
        hasSelected.value = true;
    }
});

const showIndividualApplicant = async () => {
    checked.value = false;
}

// ---------------------
// AUTO-SELECT BASED ON URL
// ---------------------
onMounted(() => {
    const type = getApplicationTypeFromUrl();
  
    if (type === 'individual') {
        checked.value = false;
        hasSelected.value = true;
    }
    else if (type === 'company') {
        checked.value = true;
        hasSelected.value = true;
    }
});
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
    background-color: #22c55e;
    /* green-500 */
    color: white;
    transition: background-color 0.3s ease, filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
    filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
    background-color: #15803d !important;
    /* green-700 */
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

        <div class="flex flex-col gap-6 rounded-xl p-4 sm:grid-cols-3">
            <div class="flex items-center gap-2 text-sm">
                <Folder class="h-5 w-5" />
                <h1 class="text-xl font-semibold">Chainsaw Permit Application Form</h1>
            </div>

            <div class="box">

                <!-- SHOW BUTTONS ONLY BEFORE SELECTION -->
                <div v-if="!hasSelected" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">

                    <!-- Individual Applicant -->
                    <button @click="() => { checked = false; hasSelected = true; }"
                        class="flex flex-col items-center justify-center gap-3 p-6 rounded-2xl shadow-md border hover:shadow-lg transition-all bg-white hover:bg-gray-50 ">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-lg font-semibold">Individual Applicant</span>
                    </button>

                    <!-- Business / Private Corporation -->
                    <button @click="() => { checked = true; hasSelected = true; }"
                        class="flex flex-col items-center justify-center gap-3 p-6 rounded-2xl shadow-md border hover:shadow-lg transition-all bg-white hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 21h18M6 21V9m12 12V5m-6 16V3" />
                        </svg>
                        <span class="text-lg font-semibold">Business / Private Corporation</span>
                    </button>

                   

                </div>

                <!-- SHOW FORM AFTER SELECTION -->
                <div v-if="hasSelected">
                    <application_form v-if="checked === false" />
                    <company_application_form v-else />
                </div>

            </div>

        </div>
    </AppLayout>
</template>
