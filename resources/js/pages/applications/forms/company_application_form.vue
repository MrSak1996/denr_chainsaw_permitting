<script setup lang="ts">
// Imports
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Fieldset from 'primevue/fieldset';
import { LoaderCircle, ShieldAlert, Trash2, CirclePlus, MonitorUp } from 'lucide-vue-next';

// Custom Components
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { submitChainsawForm } from '@/lib/chainsaw';
import { ChainsawData } from '@/types/chainsaw';
import Chainsaw_applicationField from './chainsaw_applicationField.vue';
import Chainsaw_companyField from './chainsaw_companyField.vue';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import FileCard from './file_card.vue';
import { Button } from '@/components/ui/button';
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'

const props = defineProps({
    application: Object,
    mode: String,
});

// Form Data
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getApplicationNumber } = useApi();

// Refs & Reactives
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const toast = useToast();
const page = usePage();
const userId = page.props.auth?.user?.id;
const files = ref([]);
const isLoading = ref(false);
const isloadingSpinner = ref(false);
const applicationData = ref([]);
const currentStep = ref(1);
const errorMessage = ref('');
const selectedFile = ref(null);
const showModal = ref(false);


const brands = ref([
    {
        name: '',
        models: [{ model: '', quantity: 1 }]
    }
])

// BRAND ACTIONS
const addBrand = () => {
    brands.value.push({
        name: '',
        models: [{ model: '', quantity: 1 }]
    })
}

const removeBrand = (index: number) => {
    if (brands.value.length > 1) {
        brands.value.splice(index, 1)
    }
}

// MODEL ACTIONS
const addModel = (brandIndex: number) => {
    brands.value[brandIndex].models.push({ model: '', quantity: 1 })
}

const removeModel = (brandIndex: number, modelIndex: number) => {
    const models = brands.value[brandIndex].models
    if (models.length > 1) {
        models.splice(modelIndex, 1)
    }
}


const purposeOptions = ref([
    'For cutting of trees with legal permit',
    'For Post-calamity cleaning operations',
    'For farm lot/tree orchard maintenance',
    'For cutting-trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair or perimeter fencing',
    'Forestry/landscaping service provider',
    'Other Legal Purpose',
    'Other Supporting Documents',
]);

// Utility
const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

// Step Navigation
const isStepValid = (stepId) => {
    // Implement validation per step if needed
    return true;
};

// const handleStepClick = (targetStep) => {
//     if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
//         currentStep.value = targetStep;
//     } else {
//         showError();
//     }
// };



// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Permit to Sell Chainsaw', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const formValidationRules = {
    1: {
        form: 'company_form',
        fields: [
            'application_no',
            'application_type',
            'company_name',
            'company_address',
            'authorized_representative',
            'c_province',
            'c_city_mun',
            'c_barangay',

        ],
        labels: {
            application_no: 'Application No',
            application_type: 'Application Type',
            company_name: 'Company Name',
            company_address: 'Company Address',
            authorized_representative: 'Authorized Representative',
            c_province: 'Company Province',
            c_city_mun: 'Company City/Municipality',
            c_barangay: 'Company Barangay',
            p_place_of_operation_address: 'Place of Operation Address',
            p_province: 'Place of Operation Province',
            p_city_mun: 'Place of Operation City/Municipality',
            p_barangay: 'Place of Operation Barangay'
        }
    },
    2: {
        form: 'chainsaw_form',
        fields: [
            'permit_validity',
            // 'permit_chainsaw_no',
            'brand',
            'model',
            'quantity',
            'supplier_name',
            'supplier_address',
            'purpose'
        ],
        labels: {
            permit_validity: 'Permit Validity',
            // permit_chainsaw_no: 'Permit Chainsaw No',
            brand: 'Brand',
            model: 'Model',
            quantity: 'Quantity',
            supplier_name: 'Supplier Name',
            supplier_address: 'Supplier Address',
            purpose: 'Purpose'
        }
    },
    3: {
        form: 'payment_form',
        fields: [
            'official_receipt',
            'permit_fee',
            'date_of_payment'
        ],
        labels: {
            official_receipt: 'Official Receipt',
            permit_fee: 'Permit Fee',
            date_of_payment: 'Date of Payment'
        }
    }
};

/**
 * ✅ Validate the current step form dynamically and return missing fields
 */
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value];

    if (!stepRules || !stepRules.fields || stepRules.fields.length === 0) return true;

    let formToCheck = [];

    // ✅ Determine which form to validate
    if (stepRules.form === 'company_form') {
        formToCheck = [company_form]; // wrap in array for uniform processing
    } else if (stepRules.form === 'chainsaw_form') {
        formToCheck = chainsaws; // this is an array of chainsaws
    } else if (stepRules.form === 'payment_form') {
        formToCheck = [payment_form];
    }

    const missingFields = [];

    // ✅ Loop through each form entry
    formToCheck.forEach((form, index) => {
        stepRules.fields.forEach((field) => {
            if (
                form[field] === '' ||
                form[field] === null ||
                form[field] === undefined
            ) {
                // If multiple chainsaws, indicate which one is missing
                const label = stepRules.labels[field] || field;
                if (formToCheck.length > 1) {
                    missingFields.push(`${label} (Chainsaw ${index + 1})`);
                } else {
                    missingFields.push(label);
                }
            }
        });
    });

    // ✅ Show toast if any missing fields
    if (missingFields.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete Fields',
            detail: `Please fill out the following fields: ${missingFields.join(', ')}`,
            life: 5000,
        });

        return false;
    }

    return true;
};

/**
 * ✅ Next step logic when user clicks "Next" button
 */
const nextStep = async () => {
    if (currentStep.value >= steps.value.length) return;

    isLoading.value = true;

    const handlers: Record<number, Function> = {
        1: saveCompanyApplication,
        2: submitChainsawForm,
        3: submitORPayment,
    };

    const handler = handlers[currentStep.value];

    if (handler) {
        const isSaved = await handler();

        if (!isSaved) {
            isLoading.value = false;
            return;
        }

        await getApplicationDetails();

        if (!applicationData.value || !applicationData.value.application_no) {
            console.error('Application details missing after save. Step will not advance.');
            isLoading.value = false;
            return;
        }
    }

    isLoading.value = false;
};

const handleStepClick = (targetStep) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const showError = () => {
    toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please complete all required fields before proceeding.',
        life: 3000,
    });
};

// File Preview Modal
const openFileModal = (file) => {
    selectedFile.value = file;
    showModal.value = true;
};

// Chainsaw Handlers
const copyAllFields = (index) => {
    if (chainsaws[index].copyAll && index > 0) {
        const first = chainsaws[0];
        chainsaws[index] = {
            ...first,
            copyAll: true,
            letterRequest: null,
        };
    }
};

const addChainsaw = () => {
    chainsaws.push(JSON.parse(JSON.stringify(chainsaw_form)));
};

const removeChainsaw = (index) => {
    if (chainsaws.length > 1) chainsaws.splice(index, 1);
};


const handlePurposeFileUpload = (event, fieldName, index) => {
    chainsaws[index][fieldName] = event.target.files[0];
};

const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};

// Form Submissions
const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = false;
    const formData = new FormData();
    formData.append('request_letter', company_form.request_letter);
    formData.append('soc_certificate', company_form.soc_certificate);

    try {
        const response = await insertFormData('http://192.168.2.106:8000/api/chainsaw/company_apply', {
            ...company_form,
            ...formData,
            encoded_by: userId,
        });

        toast.add({ severity: 'success', summary: 'Saved', detail: 'Company application submitted successfully.', life: 3000 });
        router.get(route('applications.index', {
            application_id: response.application_id,
            type: 'company',
            step: 2
        }));

        return true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitChainsawForm = async () => {

    isloadingSpinner.value = true
    const applicationId = getApplicationIdFromUrl()

    try {
        const formData = new FormData()

        formData.append('id', applicationId)
        formData.append('applicant_type', applicationData.value.application_type);

        // ✅ send brands as JSON
        formData.append('brands', JSON.stringify(brands.value))

            // ✅ send files ONCE
            ;['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((key) => {
                const file = applicationData[key]
                if (file instanceof File) {
                    formData.append(key, file)
                }
            })

        const response = await axios.post(
            'http://192.168.2.106:8000/api/chainsaw/insertChainsawInfo',
            formData
        )
        router.get(route('applications.index', {
            application_id: response.application_id,
            type: 'company',
            step: 3
        }));

        return true;
    } catch (error) {
        console.error(error)
        return false
    } finally {
        isloadingSpinner.value = false
    }
}


const submitORPayment = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;
    const applicationId = getApplicationIdFromUrl();
    const formData = new FormData();

    formData.append('id', applicationId);
    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('application_no', applicationData.value.application_no);
    formData.append('or_copy', payment_form.or_copy);

    try {
        await axios.post('http://192.168.2.106:8000/api/chainsaw/insert_payment', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment Details submitted successfully', life: 3000 });
        router.get(route('applications.index', { application_id: applicationId }));
        return true;
    } catch (error) {
        console.error('Failed to save payment details:', error);
        toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

// API Calls
const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();


    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data || [];
    } catch (error) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;
    }
};

const getApplicantFile = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicantFile/${applicationId}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file) => ({
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: 'png',
                thumbnail: null,
                url: file.file_url,
            }));
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};

const handleApplicationFileUpload = (
    event: Event,
    field: 'mayorDTI' | 'affidavit' | 'otherDocs' | 'permit'
) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file) return

    const maxSize = 5 * 1024 * 1024

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        toast.add({ severity: 'warn', summary: 'Invalid File Format', detail: 'Only PDF files allowed' })
        target.value = ''
        return
    }

    if (file.size > maxSize) {
        toast.add({ severity: 'warn', summary: 'File Too Large', detail: 'Max 5MB' })
        target.value = ''
        return
    }

    applicationData[field] = file
}



onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4; // Jump to last step
    }

    const urlParams = new URLSearchParams(window.location.search);
    currentStep.value = Number(urlParams.get('step')) || 1;
    getApplicationNumber(company_form, chainsaw_form);
    getApplicationDetails();
    getApplicantFile();
});
</script>
<template>
    <div class="mt-10 space-y-6">
        <Toast />
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between">
            <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center"
                @click="handleStepClick(step.id)">
                <div :class="[
                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                    currentStep === step.id ? 'bg-green-900' : 'bg-gray-300',
                ]">
                    {{ step.id }}
                </div>
                <div class="mt-2 text-sm font-medium"
                    :class="currentStep === step.id ? 'text-green-600' : 'text-gray-500'">
                    {{ step.label }}
                </div>
            </div>
        </div>


        <div v-if="currentStep === 1" class="space-y-4">
            <Chainsaw_applicationField :form="company_form" :insertFormData="insertFormData"
                :app_data="applicationData" />
            <Chainsaw_companyField :form="company_form" :app_data="applicationData" />
            <!-- <Chainsaw_operationField :form="company_form" /> -->
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="relative space-y-6">
  <div class="ribbon">
                        {{ applicationData.status_title || "DRAFT" }}

                    </div>
                    <!-- ALERT -->
                    <div class="flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700 shadow-sm">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span>
                            Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw.
                        </span>
                    </div>

                    <!-- BRANDS -->
                    <div class="space-y-6">
                        <div v-for="(brand, bIndex) in brands" :key="bIndex"
                            class="bg-white border rounded-lg shadow-sm p-5 space-y-4">
                            <!-- BRAND HEADER -->
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <FloatLabel class="flex-1">
                                    <InputText v-model="brand.name" v-letters-numbers-dash-uppercase class="w-full" />
                                    <label>Brand Name</label>
                                </FloatLabel>

                                <Button icon="pi pi-times" severity="danger" text
                                    class="bg-red-900 hover:bg-red-700 self-start" @click="removeBrand(bIndex)"
                                    v-if="brands.length > 1">
                                    <Trash2 :size="15" />
                                </Button>
                            </div>

                            <!-- MODELS TABLE -->
                            <DataTable :value="brand.models" responsiveLayout="scroll"
                                class="border rounded-lg overflow-hidden">
                                <Column header="Model"
                                    :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                                    <template #body="{ data }">
                                        <InputText v-model="data.model" v-letters-numbers-dash-uppercase
                                            placeholder="Enter model" class="w-full" />
                                    </template>
                                </Column>

                                <Column header="Quantity" style="width: 150px"
                                    :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                                    <template #body="{ data }">
                                        <InputNumber v-model="data.quantity" :min="1" class="w-full" />
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 120px"
                                    :headerStyle="{ backgroundColor: '#0D47A1', color: '#fff', fontWeight: 'bold' }">
                                    <template #body="{ index }">
                                        <div class="flex gap-2 justify-center">
                                            <Button icon="pi pi-plus" severity="success" text @click="addModel(bIndex)"
                                                class="bg-green-900 hover:bg-green-700">
                                                <CirclePlus :size="15" />
                                            </Button>
                                            <Button icon="pi pi-times" severity="danger" text
                                                @click="removeModel(bIndex, index)" v-if="brand.models.length > 1"
                                                class="bg-red-900 hover:bg-red-700">
                                                <Trash2 :size="15" />
                                            </Button>
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- ADD BRAND BUTTON -->
                        <div class="flex justify-end">
                            <Button icon="pi pi-plus" label="Add Brand" class="bg-green-900 hover:bg-green-700"
                                @click="addBrand">
                                <CirclePlus :size="15" />
                            </Button>
                        </div>
                    </div>

                    <!-- SUPPLIER & PURPOSE -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Supplier Information</h3>

                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Left Column: Data Capture -->
                            <div class="flex-1">
                                <!-- Supplier Name -->
                                <div class="mt-4">
                                    <FloatLabel>
                                        <InputText v-model="applicationData.supplier_name"
                                            v-letters-numbers-dash-uppercase class="w-full" />
                                        <label>Supplier Name</label>
                                    </FloatLabel>
                                </div>

                                <!-- Supplier Address -->
                                <div class="mt-4">
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">Supplier Address</label>
                                    <Textarea v-model="applicationData.supplier_address" v-letters-only-uppercase
                                        rows="3" class="w-full" />
                                </div>

                                <!-- Purpose -->
                                <div class="mt-4">
                                    <FloatLabel>
                                        <Select v-model="applicationData.purpose" :options="purposeOptions"
                                            class="w-full" />
                                        <label>Purpose of Purchase</label>
                                    </FloatLabel>
                                </div>

                                <!-- Permit Validity -->
                                <div class="mt-6">
                                    <FloatLabel>
                                        <DatePicker v-model="applicationData.permit_validity" class="w-full" />
                                        <label>Permit Validity</label>
                                    </FloatLabel>
                                </div>

                                <!-- Other Details -->
                                <div class="mt-4">
                                    <FloatLabel>
                                        <InputText v-model="applicationData.others_details" class="w-full" />
                                        <label>Other Details</label>
                                    </FloatLabel>
                                </div>
                            </div>


                            <!-- Right Column: File Uploads -->
                            <div class="flex-1 space-y-4">
                                <div
                                    v-if="['For selling / re-selling', 'Forestry/landscaping service provider'].includes(applicationData.purpose)">
                                    <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI
                                        Registration</label>
                                    <div
                                        class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400 rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                        <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />

                                        <p class="text-center text-gray-700 text-sm mb-2">
                                            Drag & drop files here or click to upload
                                        </p>
                                        <p class="text-center text-gray-400 text-xs">
                                            Allowed: PDF only, max 5 MB
                                        </p>
                                        <input type="file" accept="application/pdf"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="(e) => handleApplicationFileUpload(e, 'mayorDTI')" />

                                    </div>
                                </div>


                                <div v-else-if="['Other Legal Purpose'].includes(applicationData.purpose)">
                                    <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                    <div
                                        class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400  rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                        <!-- Upload Icon -->
                                        <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />

                                        <!-- Instructions -->
                                        <p class="text-center text-gray-700 text-sm mb-2">
                                            Drag & drop notarized affidavit here or click to upload
                                        </p>
                                        <p class="text-center text-gray-400 text-xs">
                                            Allowed: PDF only, max 5 MB

                                        </p>

                                        <!-- Hidden Input -->

                                        <input type="file" accept="application/pdf"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="(e) => handleApplicationFileUpload(e, 'affidavit')" />

                                        />
                                    </div>

                                </div>

                                <div v-else-if="['Other Supporting Documents'].includes(applicationData.purpose)">
                                    <label class="text-sm font-medium text-gray-700">Upload Supporting Documents</label>

                                    <div
                                        class="mt-1 w-full h-[330px] border-4 border-dashed border-blue-400  rounded-xl bg-white flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50 transition relative">
                                        <!-- Upload Icon -->
                                        <MonitorUp :size="64" class="h-12 w-12 text-blue-400 mb-4" />


                                        <!-- Instructions -->
                                        <p class="text-center text-gray-700 text-sm mb-2">
                                            Drag & drop supporting document here or click to upload
                                        </p>
                                        <p class="text-center text-gray-400 text-xs">
                                            Allowed: PDF only, max 5 MB

                                        </p>

                                        <!-- Hidden File Input -->


                                        <input type="file" accept="application/pdf"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="(e) => handleApplicationFileUpload(e, 'permit')" />



                                    </div>

                                </div>

                                <div v-else
                                    class=" w-full flex items-center justify-center p-4 border-2 border-gray-300 rounded-xl bg-gray-50 text-gray-600 h-[380px] space-x-2">
                                    <!-- Info Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>

                                    <!-- Message -->
                                    <span class="text-sm font-medium">
                                        No additional documents are required for this purpose.
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </Fieldset>
        </div>


        <div v-if="currentStep === 3" class="space-y-6">
            <Fieldset legend="Payment of Application Fee">
                <div class="relative">
                    <div class="ribbon">
                        {{ applicationData.status_title || "DRAFT" }}

                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div :hidden="false">
                            <FloatLabel>
                                <InputText v-model="applicationData.application_no" class="w-full" />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputText class="w-full" v-model="payment_form.official_receipt" />
                                <label>O.R No.</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputNumber class="w-full" v-model="payment_form.permit_fee" />
                                <label>Permit Fee</label>
                            </FloatLabel>
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official
                                Receipt</label>
                            <input type="file" accept=".jpg,.jpeg,.pdf"
                                @change="(e) => handleORFileUpload(e, 'or_copy')"
                                class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                        </div>
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 4" class="space-y-6">
            <Fieldset legend="Applicant Details" :toggleable="true">
                <!-- Applicant Info (non-file fields) -->
                <!-- <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1> -->
                <div class="relative">
                    <div class="ribbon">
                        {{ applicationData.status_title || "DRAFT" }}

                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Application No:</span>
                            <Tag :value="applicationData.application_no" severity="success" class="text-center" />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Date Applied:</span>
                            <span>{{ applicationData.date_applied }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Type of Transaction:</span>
                            <span>{{ applicationData.transaction_type }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Company Name:</span>
                            <span>{{ applicationData.company_name }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Authorized Representative:</span>
                            <span>{{ applicationData.authorized_representative }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Region:</span>
                            <span>REGION IV-A (CALABARZON)</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Complete Address:</span>
                            <span>{{ applicationData.company_address }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Place of Operation Address:</span>
                            <span>{{ applicationData.operation_complete_address }}</span>
                        </div>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                    <div class="flex">
                        <span class="w-48 font-semibold">Chainsaw No:</span>
                        <Tag :value="applicationData.permit_chainsaw_no" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Validity:</span>
                        <Tag :value="applicationData.permit_validity" severity="danger" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Brand:</span>
                        <span>{{ applicationData.brand }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Model:</span>
                        <span>{{ applicationData.model }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Quantity:</span>
                        <span>{{ applicationData.quantity }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Name:</span>
                        <span>{{ applicationData.supplier_name }}</span>
                    </div>
                    <!-- <div class="flex">
                        <span class="w-48 font-semibold">Supplier Address:</span>
                        <span>123 Supplier St., Calabarzon</span>
                    </div> -->
                    <div class="flex">
                        <span class="w-48 font-semibold">Purpose of Purchase:</span>
                        <span>{{ applicationData.purpose }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Other Details:</span>
                        <span>{{ applicationData.other_details }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Official Receipt:</span>
                        <Tag :value="applicationData.official_receipt" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Fee:</span>
                        <span>₱ {{ applicationData.permit_fee }}</span>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Uploaded Files" :toggleable="true">
                <div class="container">
                    <div class="file-list">
                        <FileCard v-for="(file, index) in files" :key="index" :file="file"
                            @openPreview="openFileModal" />
                    </div>
                </div>

                <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                    <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                        allow="autoplay"></iframe>
                </Dialog>
            </Fieldset>
        </div>
        <!-- <Button class="ml-auto" @click="sec">Next</Button> -->

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep <= 3" class="ml-auto bg-green-900" :disabled="isLoading" @click="nextStep">
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                Save as Draft
            </Button>
            <Button v-if="currentStep === 4" class="ml-auto bg-green-900" @click="submitForm"> Submit Application
            </Button>
        </div>
    </div>
</template>

<style>
/* HTML:  */
.ribbon {
    font-weight: bold;
    color: #fff;
}

.ribbon {
    --f: 0.5em;
    /* control the folded part */
    z-index: 10;
    /* ensure it's on top */
    font-size: 16px;
    /* or adjust as needed */
    position: absolute;
    top: 0;
    right: 0;
    line-height: 1.8;
    padding-inline: 1lh;
    padding-bottom: var(--f);
    border-image: conic-gradient(#0008 0 0) 51% / var(--f);
    clip-path: polygon(100% calc(100% - var(--f)),
            100% 100%,
            calc(100% - var(--f)) calc(100% - var(--f)),
            var(--f) calc(100% - var(--f)),
            0 100%,
            0 calc(100% - var(--f)),
            999px calc(100% - var(--f) - 999px),
            calc(100% - 999px) calc(100% - var(--f) - 999px));
    transform: translate(calc((1 - cos(45deg)) * 100%), -100%) rotate(45deg);
    transform-origin: 0% 100%;
    background-color: #bd1550;
    /* the main color  */
}

.file-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2563eb;
    /* blue */
    font-weight: 500;
    text-decoration: none;
    transition: color 0.3s ease;
}

.file-preview:hover {
    color: #1e40af;
    /* darker blue */
    text-decoration: underline;
}

.file-icon {
    width: 30px;
    height: 40px;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #f9f9f9;
    padding: 4px;
}

.file-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.file-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    width: 100%;
}
</style>
