<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { ShieldAlert, Edit2, Undo2, Send } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';
import FileCard from '../forms/file_card.vue';
import { useToast } from 'primevue/usetoast';



const files = ref([]);
const page = usePage();
const toast = useToast();
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)

const showModal = ref(false);
const selectedFile = ref(null);
const showFileModal = ref(false);

const props = defineProps({
    form: Object,
    app_data: Object,
    application_no: String,
    getProvinceCode: Function,
    prov_name: Array,
    activeStep: Number
});

const handleFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) return

    // PDF validation
    if (
        file.type !== 'application/pdf' &&
        !file.name.toLowerCase().endsWith('.pdf')
    ) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid File Format',
            detail: 'Only PDF files are allowed.',
            life: 3000
        })

        target.value = '' // reset input
        return
    }

    props.form[field] = file

    // Optional success message
    toast.add({
        severity: 'success',
        summary: 'File Accepted',
        detail: 'PDF file uploaded successfully.',
        life: 3000
    })
}




const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};


const application_id = getApplicationIdFromUrl();

onMounted(() => {
});

</script>
<template>
    <div>
        <!--  -->
        <Fieldset legend="Chainsaw Application" v-if="(!application_id)">
            <div class="relative">
                <div class="ribbon">{{ props.form.status_title || 'DRAFT' }}</div>
                <!-- Alert Info -->

                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw. </span>
                </div>
                <!-- Application No -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="application_no" v-model="props.form.application_no" class="w-full font-bold"
                                :disabled="true" />
                            <label for="application_no">Application No.</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                    <FloatLabel :hidden="true">
                        <InputText id="permit_no" v-model="props.form.permit_no" class="w-full font-bold" />
                        <label for="permit_no">Permit No.</label>
                    </FloatLabel>
                    <FloatLabel>
                        <Select id="classification" v-model="props.form.classification"
                            :options="['Simple', 'Complex', 'Highly Technical']" class="w-full" />
                        <label for="classification">Classification</label>
                    </FloatLabel>



                </div>
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Date Applied -->
                    <div>
                        <FloatLabel>
                            <InputText id="date_applied" v-model="props.form.date_applied" type="date" class="w-full" />
                            <label for="date_applied">Date Applied</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select v-model="props.form.type_of_transaction" :options="['G2C', 'G2B', 'G2G']"
                                class="w-full" />
                            <label>Type of Transaction</label>
                        </FloatLabel>
                    </div>
                    <div class="md:col-span-1">
                        <FloatLabel>
                            <InputText id="company_mobile_no" v-model="props.form.company_mobile_no" class="w-full" />
                            <label for="company_mobile_no">Contact Details</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>
                <!-- Main Fields -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Company Name -->
                    <div class="md:col-span-2">
                        <FloatLabel>

                            <InputText id="surname" v-model="props.form.company_name" v-letters-only-uppercase
                                class="w-full" />
                            <label for="surname">Company / Corporation / Cooperative Name</label>
                        </FloatLabel>
                        <InputError />
                    </div>

                    <!-- Authorized Representative -->
                    <div class="md:col-span-1">
                        <FloatLabel>
                            <InputText id="first_name" v-model="props.form.authorized_representative"
                                v-letters-only-uppercase class="w-full" />
                            <label for="first_name">Name of Authorized Representative</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>



                <!-- Additional Fields -->

                <!-- Application Letter Upload -->
                <div class="grid gap-6 md:grid-cols-1">
                    <div class="flex flex-col md:col-span-2">
                        <label for="requestLetter" class="mb-2 text-sm font-medium text-gray-700"> Upload Application
                            Letter / Request Letter </label>
                        <input type="file" id="requestLetter" accept="application/pdf"
                            @change="e => handleFileUpload(e, 'request_letter')"
                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />

                    </div>
                </div>

                <!-- Soc. Certificate Upload -->
                <div class="mt-4 grid gap-6 md:grid-cols-1">
                    <div class="flex flex-col md:col-span-2">
                        <label for="socCertificate" class="mb-2 text-sm font-medium text-gray-700">
                            Upload Soc. Certificate / Business Registration
                        </label>
                        <input id="socCertificate" type="file" accept="application/pdf"
                            @change="e => handleFileUpload(e, 'soc_certificate')"
                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                    </div>
                </div>
            </div>
        </Fieldset>

        <Fieldset legend="Chainsaw Application" v-else>
            <div class="relative">
                <div class="ribbon">DRAFT

                </div>
                <!-- Alert Info -->

                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw. </span>
                </div>
                <!-- Application No -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="application_no" v-model="props.app_data.application_no"
                                class="w-full font-bold" :disabled="true" />
                            <label for="application_no">Application No.</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Date Applied -->
                    <div>
                        <FloatLabel>
                            <DatePicker id="date_applied" v-model="props.app_data.date_applied" type="date"
                                class="w-full" />
                            <label for="date_applied">Date Applied</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select v-model="props.app_data.transaction_type" :options="['G2C', 'G2B', 'G2G']"
                                class="w-full" />
                            <label>Type of Tran 0saction </label>
                        </FloatLabel>
                    </div>
                </div>

                <!-- Main Fields -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <!-- Company Name -->
                    <div class="md:col-span-2">
                        <FloatLabel>
                            <InputText id="surname" v-model="props.app_data.company_name" class="w-full" />
                            <label for="surname">Company / Corporation / Cooperative Name</label>
                        </FloatLabel>
                        <InputError />
                    </div>

                    <!-- Authorized Representative -->
                    <div class="md:col-span-1">
                        <FloatLabel>
                            <InputText id="first_name" v-model="props.app_data.authorized_representative"
                                class="w-full" />
                            <label for="first_name">Name of Authorized Representative</label>
                        </FloatLabel>
                        <InputError />
                    </div>
                </div>

                <!-- Additional Fields -->

                <!-- Application Letter Upload -->
                <div class="grid gap-6 md:grid-cols-1">
                    <div class="flex flex-col md:col-span-2">
                        <label for="requestLetter" class="mb-2 text-sm font-medium text-gray-700"> Upload Application
                            Letter / Request Letter </label>

                        <input id="requestLetter" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="(e) => handleFileUpload(e, 'request_letter')"
                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                    </div>
                </div>

                <!-- Soc. Certificate Upload -->
                <div class="mt-4 grid gap-6 md:grid-cols-1">
                    <div class="flex flex-col md:col-span-2">
                        <label for="socCertificate" class="mb-2 text-sm font-medium text-gray-700">
                            Upload Soc. Certificate / Business Registration
                        </label>
                        <input id="socCertificate" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="(e) => handleFileUpload(e, 'soc_certificate')"
                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                    </div>
                </div>
            </div>
        </Fieldset>

    </div>
</template>
