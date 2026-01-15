<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Folder, Info, ShieldAlert } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import LoadingSpinner from '../../LoadingSpinner.vue';
import { ProductService } from '../service/ProductService';
import FileCard from './file_card.vue';
/* ================================
 * Types
 * ================================ */
interface ProvinceOption {
    id: string | number;
    name: string;
}

interface CityOption {
    id: string;
    name: string;
    code: string;
}

interface BarangayOption {
    id: string;
    name: string;
}

/* ================================
 * Composables & States
 * ================================ */
const { prov_name, getProvinceCode } = useApi();
const { props } = usePage();
const { company_form, chainsaw_form, payment_form } = useAppForm();
const toast = useToast();
const page = usePage();
const currentStep = ref(4);
const isLoadingSpinner = ref(false);
const showModal = ref(false);
const application = props.application;
const applicationData = ref([]);
const files = ref([]);
const errorMessage = ref('');
const cityMunOpts = ref<CityOption[]>([]);
const barangayOpts = ref<BarangayOption[]>([]);
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const selectedFile = ref(null);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Applications', href: '/applicants/index' }];

const transactionTypeOptions = ref([
    { name: 'G2C', id: '1' },
    { name: 'G2B', id: '2' },
    { name: 'G2G', id: '3' },
]);
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Chainsaw Information', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);
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

/* ================================
 * Utility Functions
 * ================================ */
const fetchCities = async (provinceCode: number | string, targetField: string) => {
    if (!provinceCode) {
        cityMunOpts.value = [];
        return;
    }

    try {
        const { data } = await axios.get(`http://10.201.12.154:8000/api/provinces/${provinceCode}/cities`);

        if (Array.isArray(data)) {
            cityMunOpts.value = data.map((item: any) => ({
                id: item.mun_code,
                name: item.mun_name,
                code: item.geo_code,
            }));

            if (!application[targetField]) {
                application[targetField] = '';
            }
        } else {
            console.error('Unexpected city response:', data);
            cityMunOpts.value = [];
        }
    } catch (error) {
        console.error('Error fetching cities:', error);
        cityMunOpts.value = [];
    }
};

const fetchBarangays = async () => {
    const province = application.operation_province_c;
    const cityMun = application.operation_city_mun_c;
    const region = application.region_code || '04';

    if (!province || !cityMun || !region) {
        barangayOpts.value = [];
        return;
    }

    try {
        const { data } = await axios.get('http://10.201.12.154:8000/api/barangays', {
            params: { reg_code: region, prov_code: province, mun_code: cityMun },
        });

        if (Array.isArray(data)) {
            barangayOpts.value = data.map((item: any) => ({
                id: item.bgy_code,
                name: item.bgy_name,
            }));

            if (!application.operation_brgy_c) {
                application.operation_brgy_c = '';
            }
        } else {
            console.error('Unexpected barangay response:', data);
            barangayOpts.value = [];
        }
    } catch (error) {
        console.error('Error fetching barangays:', error);
        barangayOpts.value = [];
    }
};

const updateApplicationStatus = async () => {
    try {
        const result = await ProductService.updateStatus(16, 'approved');
        console.log('Status updated successfully:', result);
    } catch (error) {
        console.error('Failed to update status:', error);
    }
};
const handleFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;

    const file = target.files[0];
    application[field] = file;
    application.file_name = file.name;

    console.log(`Uploaded [${field}]:`, file);
};

const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};

const isStepValid = (stepId) => {
    // Implement validation per step if needed
    return true;
};

const handleStepClick = (targetStep) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};

const nextStep = async () => {
    if (currentStep.value < steps.value.length) {
        const handlers = [null, saveCompanyApplication, submitChainsawForm, submitORPayment];
        const isSaved = await handlers[currentStep.value]?.();
        if (isSaved) currentStep.value++;
    }
};

const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;
    const formData = new FormData();
    formData.append('request_letter', company_form.request_letter);
    formData.append('soc_certificate', company_form.soc_certificate);

    try {
        const response = await insertFormData('http://10.201.12.154:8000/api/chainsaw/company_apply', {
            ...company_form,
            ...formData,
            encoded_by: userId,
        });

        toast.add({ severity: 'success', summary: 'Saved', detail: 'Company application submitted successfully.', life: 3000 });
        router.get(route('applications.index', { application_id: response.application_id }));
        return true;
    } catch (error) {
        console.error('Failed to save application:', error);
        toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitApplication = async () => {};

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

const getApplicationDetails = async () => {
    const urlParts = window.location.pathname.split('/');
    const applicationId = urlParts[2];

    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        return;
    }

    try {
        const response = await axios.get(`http://10.201.12.154:8000/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data || [];
    } catch (error) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
    }
};

const getApplicantFile = async () => {
    const urlParts = window.location.pathname.split('/');
    const applicationId = urlParts[2];
    console.log(applicationId);
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://10.201.12.154:8000/api/getApplicantFile/${applicationId}`);
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

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};
/* ================================
 * Computed
 * ================================ */
const dateApplied = computed({
    get: () => {
        if (!application.created_at) return '';
        const [day, month, year] = application.created_at.split('/');
        return `${year}-${month}-${day}`;
    },
    set: (value) => {
        if (!value) {
            application.created_at = '';
            return;
        }
        const [year, month, day] = value.split('-');
        application.created_at = `${day}/${month}/${year}`;
    },
});

/* ================================
 * Watchers
 * ================================ */
watch(
    () => application.prov_code,
    (newVal) => fetchCities(newVal, 'city_mun'),
    { immediate: true },
);

watch(
    () => application.operation_province_c,
    (newVal) => fetchCities(newVal, 'operation_city_mun_c'),
    { immediate: true },
);

watch(() => application.operation_city_mun_c, fetchBarangays, { immediate: true });

/* ================================
 * Lifecycle
 * ================================ */
onMounted(async () => {
    await getProvinceCode();
    await getApplicationDetails();
    await getApplicantFile();
    application.prov_code = Number(application.prov_code);
    application.operation_province_c = Number(application.operation_province_c);
});
</script>

<template>
    <Head title="Chainsaw Permit Application" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center gap-2 text-sm">
                <Folder class="h-5 w-5" />
                <h1 class="text-xl font-semibold">Chainsaw Permit Application Form</h1>
            </div>

            <div class="box">
                <!-- Application Instructions -->
                <h2 class="title flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2">
                        <Info class="text-primary" />
                        Application Instructions
                    </span>
                </h2>

                <div class="mt-10 space-y-6">
                    <Toast />
                    <LoadingSpinner :loading="isLoadingSpinner" />
                    <div class="mb-6 flex items-center justify-between">
                        <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center" @click="handleStepClick(step.id)">
                            <div
                                :class="[
                                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                                    currentStep === step.id ? 'bg-green-600' : 'bg-gray-300',
                                ]"
                            >
                                {{ step.id }}
                            </div>
                            <div class="mt-2 text-sm font-medium" :class="currentStep === step.id ? 'text-green-600' : 'text-gray-500'">
                                {{ step.label }}
                            </div>
                        </div>
                    </div>  
                    <div v-if="currentStep === 1" class="space-y-4">
                        <!-- Chainsaw Application -->
                        <Fieldset legend="Chainsaw Application">
                            <div class="relative">
                                <div class="ribbon">DRAFT</div>

                                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                                    <span>Please complete all fields to proceed with your application for a Permit to Purchase Chainsaw.</span>
                                </div>

                                <!-- Application Number -->
                                <div class="mb-6 grid gap-6 md:grid-cols-3">
                                    <FloatLabel>
                                        <InputText v-model="application.application_no" class="w-full font-bold" disabled />
                                        <label>Application No.</label>
                                    </FloatLabel>
                                </div>

                                <!-- Date Applied & Transaction Type -->
                                <div class="mb-6 grid gap-6 md:grid-cols-3">
                                    <FloatLabel>
                                        <InputText v-model="dateApplied" type="date" class="w-full" />
                                        <label>Date Applied</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <Select
                                            v-model="application.transaction_type"
                                            :options="transactionTypeOptions"
                                            optionValue="id"
                                            optionLabel="name"
                                            class="w-full"
                                        />
                                        <label>Type of Transaction</label>
                                    </FloatLabel>
                                </div>

                                <!-- Company Info -->
                                <div class="mb-6 grid gap-6 md:grid-cols-3">
                                    <FloatLabel class="md:col-span-2">
                                        <InputText v-model="application.company_name" class="w-full" />
                                        <label>Company / Corporation / Cooperative Name</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText v-model="application.authorized_representative" class="w-full" />
                                        <label>Name of Authorized Representative</label>
                                    </FloatLabel>
                                </div>

                                <!-- File Uploads -->
                                <div class="grid gap-6">
                                    <div class="flex flex-col md:col-span-2">
                                        <label for="requestLetter" class="mb-2 text-sm font-medium text-gray-700">
                                            Upload Application Letter / Request Letter
                                        </label>

                                        <input
                                            id="requestLetter"
                                            type="file"
                                            accept=".jpg,.jpeg,.pdf"
                                            @change="(e) => handleFileUpload(e, 'request_letter')"
                                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        />
                                        <div v-if="application.files?.request_letter" class="mt-2 text-sm text-red-600">
                                            Selected: <strong>{{ application.files.request_letter }}</strong>
                                        </div>
                                    </div>

                                    <div class="flex flex-col md:col-span-2">
                                        <label for="socCertificate" class="mb-2 text-sm font-medium text-gray-700">
                                            Upload Soc. Certificate / Business Registration
                                        </label>
                                        <input
                                            id="socCertificate"
                                            type="file"
                                            accept=".jpg,.jpeg,.pdf"
                                            @change="(e) => handleFileUpload(e, 'soc_certificate')"
                                            class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        />
                                        <div v-if="application.files?.secretary_certificate" class="mt-2 text-sm text-red-600">
                                            Selected: <strong>{{ application.files.secretary_certificate }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Fieldset>

                        <!-- Address Fields -->
                        <Fieldset legend="Company Address">
                            <div class="grid gap-6 md:grid-cols-4">
                                <InputText value="REGION IV-A (CALABARZON)" class="w-full" disabled />

                                <Select
                                    v-model="application.prov_code"
                                    filter
                                    :options="prov_name"
                                    optionValue="id"
                                    optionLabel="name"
                                    placeholder="Province"
                                    class="w-full"
                                />

                                <Select
                                    v-model="application.city_mun"
                                    filter
                                    :options="cityMunOpts"
                                    optionValue="id"
                                    optionLabel="name"
                                    placeholder="City/Municipality"
                                    class="w-full"
                                />

                                <Select
                                    v-model="application.brgy"
                                    filter
                                    :options="barangayOpts"
                                    optionValue="id"
                                    optionLabel="name"
                                    placeholder="Barangay"
                                    class="w-full"
                                />

                                <Textarea
                                    v-model="application.company_address"
                                    rows="6"
                                    placeholder="Complete Address (Street, Purok, etc.)"
                                    class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>
                        </Fieldset>
                    </div>
                    <div v-if="currentStep === 2" class="space-y-4">
                        <Fieldset legend="Chainsaw Information">
                            <div class="relative">
                                <div class="ribbon">DRAFT</div>

                                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                                    <span> Please complete all fields to proceed with your application for a Permit to Purchase application. </span>
                                </div>
                                <div v-for="(chainsaw, index) in chainsaws" :key="index" class="">
                                    <!-- Remove Button -->
                                    <button
                                        v-if="index > 0"
                                        @click="removeChainsaw(index)"
                                        class="absolute top-2 right-2 text-red-600 hover:text-red-800"
                                        title="Remove"
                                    >
                                        ✕
                                    </button>

                                    <!-- Copy All Checkbox -->
                                    <div v-if="index > 0" class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox" v-model="application.copyAll" @change="copyAllFields(index)" />
                                        <label>Same details as first chainsaw</label>
                                    </div>

                                    <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
                                        <div :hidden="false">
                                            <FloatLabel>
                                                <InputText v-model="application.application_no" class="w-full" disabled />

                                                <label>Application No.</label>
                                            </FloatLabel>
                                        </div>

                                        <div>
                                            <FloatLabel>
                                                <InputText v-model="application.brand" class="w-full" />
                                                <label>Brand</label>
                                            </FloatLabel>
                                        </div>
                                        <div>
                                            <FloatLabel>
                                                <Select v-model="application.model" :options="['MS 382', 'MS 230', 'MS 162']" class="w-full" />
                                                <label>Model</label>
                                            </FloatLabel>
                                        </div>
                                        <div>
                                            <FloatLabel>
                                                <InputText v-model="application.quantity" type="number" class="w-full" />
                                                <label>Quantity</label>
                                            </FloatLabel>
                                        </div>

                                        <div class="mt-4 md:col-span-3">
                                            <FloatLabel>
                                                <InputText v-model="application.supplier_name" class="w-full" />
                                                <label>Supplier Name</label>
                                            </FloatLabel>
                                        </div>
                                        <div class="mt-4 md:col-span-3">
                                            <FloatLabel>
                                                <InputText v-model="application.supplier_address" class="w-full" />
                                                <label>Supplier Address</label>
                                            </FloatLabel>
                                        </div>

                                        <div class="mt-4 space-y-4 md:col-span-3">
                                            <FloatLabel>
                                                <Select v-model="application.purpose" :options="purposeOptions" class="w-full" />
                                                <label>Purpose of Purchase</label>
                                            </FloatLabel>

                                            <!-- Conditional Uploads -->
                                            <div
                                                v-if="
                                                    application.purpose === 'For selling / re-selling' ||
                                                    application.purpose === 'Forestry/landscaping service provider'
                                                "
                                            >
                                                <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI Registration</label>
                                                <input
                                                    type="file"
                                                    accept=".jpg,.jpeg,.pdf,.docx,.png"
                                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                                    @change="(e) => (chainsaws[index].mayorDTI = e.target.files[0])"
                                                />
                                            </div>

                                            <div v-if="application.purpose === 'Other Legal Purpose'">
                                                <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                                <input
                                                    type="file"
                                                    accept=".jpg,.jpeg,.pdf,.docx,.png"
                                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                                    @change="(e) => (chainsaws[index].affidavit = e.target.files[0])"
                                                />
                                            </div>

                                            <div v-if="application.purpose === 'Other Supporting Documents'">
                                                <label class="text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                                <input
                                                    type="file"
                                                    accept=".jpg,.jpeg,.pdf,.docx,.png"
                                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                                    @change="(e) => (chainsaws[index].otherDocs = e.target.files[0])"
                                                />
                                            </div>
                                        </div>

                                        <div class="md:col-span-3">
                                            <FloatLabel>
                                                <InputText v-model="application.others_details" class="w-full" />
                                                <label>Other Details</label>
                                            </FloatLabel>
                                        </div>
                                        <div class="mt-4 grid gap-6 md:col-span-3 md:grid-cols-2">
                                            <!-- Permit Number -->
                                            <div>
                                                <FloatLabel>
                                                    <InputText v-model="application.permit_chainsaw_no" class="w-full" />
                                                    <label>Permit to Sell / Re-Sell Chainsaw No.</label>
                                                </FloatLabel>
                                            </div>

                                            <!-- Permit Validity -->
                                            <div>
                                                <FloatLabel>
                                                    <DatePicker v-model="application.permit_validity" class="w-full" />
                                                    <label>Permit Validity</label>
                                                </FloatLabel>
                                            </div>
                                        </div>

                                        <div class="md:col-span-3">
                                            <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>

                                            <input
                                                type="file"
                                                accept=".jpg,.jpeg,.pdf,.docx,.png"
                                                class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                                @change="(e) => (chainsaws[index].permit = e.target.files[0])"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Button -->
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        @click="addChainsaw"
                                        class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                                    >
                                        <span class="text-xl">＋</span> Add Another Chainsaw
                                    </button>
                                </div>
                            </div>
                        </Fieldset>
                    </div>
                    <div v-if="currentStep === 3" class="space-y-6">
                        <Fieldset legend="Payment of Application Fee">
                            <div class="relative">
                                <div class="ribbon">DRAFT</div>
                                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                                    <span> Please complete all fields to proceed with your application for a Permit to Purchase Chainsaw. </span>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div :hidden="false">
                                        <FloatLabel>
                                            <InputText v-model="application.application_no" class="w-full" disabled />
                                            <label>Application No.</label>
                                        </FloatLabel>
                                    </div>
                                    <div>
                                        <FloatLabel>
                                            <InputText class="w-full" v-model="application.official_receipt" disabled />
                                            <label>O.R No.</label>
                                        </FloatLabel>
                                    </div>
                                    <div>
                                        <FloatLabel>
                                            <InputNumber class="w-full" v-model="application.permit_fee" />
                                            <label>Permit Fee</label>
                                        </FloatLabel>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official Receipt</label>
                                        <input
                                            type="file"
                                            accept=".jpg,.jpeg,.pdf"
                                            @change="(e) => handleORFileUpload(e, 'or_copy')"
                                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        />
                                    </div>
                                </div>
                            </div>
                        </Fieldset>
                    </div>
                    <div v-if="currentStep === 4" class="space-y-6">
                        <div class="relative">
                            <div class="ribbon">DRAFT</div>
                            <Fieldset legend="Applicant Details" :toggleable="true">
                                <!-- Applicant Info (non-file fields) -->
                                <!-- <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1> -->
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
                                    <!-- <div class="flex">
                        <span class="w-48 font-semibold">Province:</span>
                        <span>{{ applicationData.prov_name }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Municipality:</span>
                        <span>Lipa City</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Barangay:</span>
                        <span>Barangay 1</span>
                    </div> -->
                                    <div class="flex">
                                        <span class="w-48 font-semibold">Complete Address:</span>
                                        <span>{{ applicationData.company_address }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="w-48 font-semibold">Place of Operation Address:</span>
                                        <span>{{ applicationData.operation_complete_address }}</span>
                                    </div>
                                </div>
                            </Fieldset>
                        </div>

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
                                    <FileCard v-for="(file, index) in files" :key="index" :file="file" @openPreview="openFileModal" />
                                </div>
                            </div>

                            <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                                <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500" allow="autoplay"></iframe>
                            </Dialog>
                        </Fieldset>
                    </div>

                    <div class="flex justify-between pt-6">
                        <Button v-if="currentStep > 1" @click="prevStep" variant="outline" class="btn-back">Back</Button>
                        <Button v-if="currentStep <= 3" class="btn-back ml-auto" @click="nextStep">Save as Draft</Button>
                        <Button v-if="currentStep === 4" class="ml-auto" @click="submitApplication"> Submit Application </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* HTML: */
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
    clip-path: polygon(
        100% calc(100% - var(--f)),
        100% 100%,
        calc(100% - var(--f)) calc(100% - var(--f)),
        var(--f) calc(100% - var(--f)),
        0 100%,
        0 calc(100% - var(--f)),
        999px calc(100% - var(--f) - 999px),
        calc(100% - 999px) calc(100% - var(--f) - 999px)
    );
    transform: translate(calc((1 - cos(45deg)) * 100%), -100%) rotate(45deg);
    transform-origin: 0% 100%;
    background-color: #bd1550;
    /* the main color */
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
    transition:
        background-color 0.3s ease,
        filter 0.3s ease;
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

.btn-back {
    background-color: #166534;
}
</style>
