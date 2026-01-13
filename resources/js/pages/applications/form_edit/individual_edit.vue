<script setup lang="ts">
import { ref, watch, reactive, onMounted, toRaw } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';

// UI & Icons
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import Fieldset from 'primevue/fieldset';
import { LoaderCircle, ShieldAlert } from 'lucide-vue-next';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import chainsaw_individualInfoField from '../forms/chainsaw_individualInfoField.vue';
import FileCard from '../forms/file_card.vue';
import ConfirmModal from '../modal/confirmation_modal.vue';

// Composables
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useApi } from '@/composables/useApi';

// State
const props = defineProps({
    application: Object,
    mode: String,
});
const toast = useToast();
const { createChainsaw, individual_form, chainsaw_form, payment_form } = useAppForm();
const page = usePage();

// Merge incoming application props into individual_form (if you want to prefill)
Object.assign(individual_form, page.props.application || {});
Object.assign(chainsaw_form, page.props.application || {});
Object.assign(payment_form, page.props.application || {});

const { insertFormData, updateFormData } = useFormHandler();
const { getProvinceCode, getApplicationNumber, prov_name } = useApi();
const isLoading = ref(false);
const applicationData = ref<any>({});
const files = ref<any[]>([]);
const i_city_mun = ref<number | string>(0);
const errorMessage = ref('');
const currentStep = ref(1);

// IMPORTANT: initialize chainsaws correctly using createChainsaw()
// Use ref (so handlers calling chainsaws.value.push(...) work)
const chainsaws = ref<ReturnType<typeof createChainsaw>[]>([createChainsaw()]);

const userId = page.props.auth?.user?.id ?? null;
const selectedFile = ref(null);
const showModal = ref(false);

// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Form', id: 1 },
    { label: 'Permit to Sell', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const formValidationRules = {
    1: {
        form: 'individual_form',
        fields: [
            'date_applied',
            'application_type',
            'type_of_transaction',
            'geo_code',
            'last_name',
            'first_name',
            'sex',
        ],
        labels: {
            date_applied: 'Date Applied',
            application_type: 'Application Type',
            type_of_transaction: 'Type of Transaction',
            geo_code: 'Geo Code',
            last_name: 'Last Name',
            first_name: 'First Name',
            sex: 'Sex',
        },
    },

    2: {
        form: 'chainsaw_form',
        fields: [
            'permit_validity',
            'permit_chainsaw_no',
            'brand',
            'model',
            'quantity',
            'supplier_name',
            'supplier_address',
            'purpose',
        ],
        labels: {
            permit_validity: 'Permit Validity',
            permit_chainsaw_no: 'Permit Chainsaw No',
            brand: 'Brand',
            model: 'Model',
            quantity: 'Quantity',
            supplier_name: 'Supplier Name',
            supplier_address: 'Supplier Address',
            purpose: 'Purpose',
        },
    },
    3: {
        form: 'payment_form',
        fields: ['official_receipt', 'permit_fee', 'date_of_payment'],
        labels: {
            official_receipt: 'Official Receipt',
            permit_fee: 'Permit Fee',
            date_of_payment: 'Date of Payment',
        },
    },
};

// -------------------------
// Individual Form Validation
// -------------------------
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value];

    if (!stepRules || !stepRules.fields || stepRules.fields.length === 0) return true;

    let formToCheck: any[] = [];

    // Determine which form to validate
    if (stepRules.form === 'individual_form') {
        formToCheck = [individual_form];
    } else if (stepRules.form === 'chainsaw_form') {
        formToCheck = chainsaws.value;
    } else if (stepRules.form === 'payment_form') {
        formToCheck = [payment_form];
    }

    const missingFields: string[] = [];

    formToCheck.forEach((form, index) => {
        stepRules.fields.forEach((field) => {
            if (form[field] === '' || form[field] === null || form[field] === undefined) {
                const label = stepRules.labels[field] || field;
                if (formToCheck.length > 1) {
                    missingFields.push(`${label} (Chainsaw ${index + 1})`);
                } else {
                    missingFields.push(label);
                }
            }
        });
    });

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

const openFileModal = (file: any) => {
    selectedFile.value = file;
    showModal.value = true;
};

const handleORFileUpload = (event: Event, field: string) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        (payment_form as any)[field] = target.files[0];
    }
};

// -------------------------
// Next Step Logic
// -------------------------
const nextStep = async () => {
    if (currentStep.value >= steps.value.length) return;

    isLoading.value = true;

    const handlers: Record<number, Function> = {
        1: saveIndividualApplication,
        2: updateChainsawInfo,
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

    currentStep.value++;
    isLoading.value = false;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// ─────────────────────────────────────────────────────────────
// FORM SUBMISSION
// ─────────────────────────────────────────────────────────────

const saveIndividualApplication = async () => {
    isLoading.value = true;
    const applicationId = page.props.application.id;

    try {
        const response = await axios.put(`/applications/${applicationId}/update-applicant-data`, {
            application_type: 'Individual',
            last_name: individual_form.last_name,
            first_name: individual_form.first_name,
            middle_name: individual_form.middle_name,
            type_of_transaction: individual_form.type_of_transaction,
            date_applied: individual_form.date_applied,
            gov_id_number: individual_form.gov_id_number,
            government_id: individual_form.gov_id_type,
            sex: individual_form.sex,
            applicant_contact_details: individual_form.mobile_no,
            applicant_telephone_no: individual_form.telephone_no,
            applicant_email_address: individual_form.email_address,
            applicant_province_c: individual_form.i_province,
            applicant_city_mun_c: individual_form.i_city_mun,
            applicant_brgy_c: individual_form.i_barangay,
            applicant_complete_address: individual_form.i_complete_address,
            encoded_by: userId,
        });

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Individual application updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }
    } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Failed', detail: error.message || 'Error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
    }
};

const updateChainsawInfo = async (chainsawForm) => {
    isLoading.value = true;
    const applicationId = page.props.application.id;
    try {

        const response = await axios.put(
            `/applications/${applicationId}/update-chainsaw-info`, {
            application_id: applicationId,
            permit_chainsaw_no: chainsaw_form.permit_chainsaw_no,
            permit_validity: chainsaw_form.permit_validity,
            brand: chainsaw_form.brand,
            model: chainsaw_form.model,
            quantity: chainsaw_form.quantity,
            supplier_name: chainsaw_form.supplier_name,
            supplier_address: chainsaw_form.supplier_address,
            purpose: chainsaw_form.purpose,
            other_details: chainsaw_form.other_details,
        });

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Chainsaw Information updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to update chainsaw info',
            life: 4000,
        });
        return null;
    }
};


const submitORPayment = async () => {
    isLoading.value = true;
    const applicationId = page.props.application.id;
    try {
        const response = await axios.put(
            `/applications/${applicationId}/update-payment-info`, {
            official_receipt: payment_form.official_receipt,
            permit_fee: payment_form.permit_fee,
            or_copy: payment_form.or_ccopy,
            application_id: applicationId,
            application_no: payment_form.application_no,
        });

        if (response.data.status === 'success') {
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Payment Information updated successfully.', life: 3000 });
            return true;
        } else {
            toast.add({ severity: 'warn', summary: 'No Changes', detail: response.data.message, life: 3000 });
            return false;
        }

        return true;
    } catch (error) {
        console.error('Failed to save payment details:', error);
        toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
        return false;
    } finally {
        isLoading.value = false;
    }
};

const getApplicationIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('application_id') || urlParams.get('id');
};

const application_id = getApplicationIdFromUrl();


const getApplicationDetails = async () => {
    const applicationId = getApplicationIdFromUrl();
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data ?? {};
        i_city_mun.value = response.data.data?.i_city_mun ?? i_city_mun.value;
    } catch (error: any) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;
    }
};

const getApplicantFile = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicantFile/${applicationId}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file: any) => ({
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: 'png',
                thumbnail: null,
                url: file.file_url,
            }));
        } else {
            console.log('No files');
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};

const getEmbedUrl = (url: string) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

// ─────────────────────────────────────────────────────────────
// CHAINSaw Section
// ─────────────────────────────────────────────────────────────

const addChainsaw = () => {
    chainsaws.value.push(createChainsaw());
};

const removeChainsaw = (index: number) => {
    if (chainsaws.value.length > 1) chainsaws.value.splice(index, 1);
};

const copyAllFields = (index: number) => {
    if (chainsaws.value[index].copyAll && index > 0) {
        chainsaws.value[index] = {
            ...chainsaws.value[0],
            copyAll: true,
            letterRequest: null,
        };
    }
};

const handleFileUpload = (event: Event, index: number, field = 'letterRequest') => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (chainsaws.value[index] as any)[field] = file;
};

// ─────────────────────────────────────────────────────────────
// PURPOSE Section
// ─────────────────────────────────────────────────────────────
const purpose = ref({
    purpose: '',
    purposeFiles: {
        mayorDTI: null,
        affidavit: null,
        otherDocs: null,
    },
});

const handlePurposeFileUpload = (event: Event, field: string) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (purpose.value.purposeFiles as any)[field] = file;
};

const isStepValid = (stepId: number) => true;

const handleStepClick = (targetStep: number) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};

const showError = () => {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please complete all required fields before proceeding.', life: 3000 });
};

const purposeOptions = [
    'For cutting of trees with legal permit',
    'For Post-calamity cleaning operations',
    'For farm lot/tree orchard maintenance',
    'For cutting-trimming of trees posing danger within a private property',
    'For selling / re-selling',
    'For cutting of trees to be used for house repair or perimeter fencing',
    'Forestry/landscaping service provider',
    'Other Legal Purpose',
    'Other Supporting Documents',
];

const getDocumentTitle = (fileName?: string) => {
    if (!fileName) return '';
    const name = fileName.toLowerCase();
    if (name.startsWith('permit_')) return 'Permit to Purchase / Chainsaw Permit';
    if (name.startsWith('mayors_')) return 'Mayor’s Permit';
    if (name.startsWith('notarized_')) return 'Notarized Application Form';
    if (name.startsWith('official_')) return 'Official Receipt';
    if (name.startsWith('request_')) return 'Request Letter';
    if (name.startsWith('secretary_')) return 'Secretary’s Certificate';
    return 'Supporting Document';
};

const getFileType = (fileName?: string) => {
    if (!fileName) return '';
    return fileName.split('.').pop()?.toLowerCase() ?? '';
};

onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4;
    }
    getProvinceCode();
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
                    currentStep === step.id ? 'bg-green-900' : step.id < currentStep ? 'bg-blue-400' : 'cursor-not-allowed bg-gray-300',
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
            <chainsaw_individualInfoField :form="individual_form" :insertFormData="insertFormData"
                :getProvinceCode="getProvinceCode" :city_mun="i_city_mun" :prov_name="prov_name" />
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
            <Fieldset legend="Chainsaw Information">
                <div class="relative">
                    <div class="ribbon">
                        {{ page.props.application.status_title || 'DRAFT' }}
                    </div>
                    <div v-for="(chainsaw, index) in chainsaws" :key="index" class="p-4 rounded-lg mb-6 relative">
                        <!-- Remove Button -->
                        <button v-if="index > 0" @click="chainsaws.splice(index, 1)"
                            class="absolute top-2 right-2 text-red-600 hover:text-red-800">
                            ✕
                        </button>

                        <!-- COPY ALL FROM FIRST -->
                        <div v-if="index > 0" class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" v-model="chainsaw_form.copyAll" @change="copyAllFields(index)" />
                            <label>Same details as first chainsaw</label>
                        </div>

                        <!-- FORM CONTENT -->
                        <div class="mb-6 grid gap-6 md:grid-cols-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw_form.application_no" class="w-full font-bold" disabled />
                                <label>Application No.</label>
                            </FloatLabel>
                            <div>
                                <FloatLabel>
                                    <InputText :disabled="true" v-model="chainsaw_form.permit_no"
                                        class="w-full font-bold" />
                                    <label>Permit No.</label>
                                </FloatLabel>
                            </div>

                            <div></div>

                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.quantity" type="number" class="w-full" />
                                    <label>Quantity</label>
                                </FloatLabel>
                            </div>

                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.brand" class="w-full" />
                                    <label>Brand</label>
                                </FloatLabel>
                            </div>

                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.model" class="w-full" />
                                    <label>Model</label>
                                </FloatLabel>
                            </div>


                            <div class="grid grid-cols-1 gap-4 md:col-span-3 md:grid-cols-2">
                                <!-- Supplier Name -->
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.supplier_name" class="w-full" />
                                    <label>Supplier Name</label>
                                </FloatLabel>

                                <!-- Engine Serial No -->
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.engine_serial_no" class="w-full" />
                                    <label>Engine Serial No</label>
                                </FloatLabel>
                            </div>
                            <div class="md:col-span-3">
                                <Textarea v-model="chainsaw_form.supplier_address" rows="6"
                                    placeholder="Complete Address"
                                    class="w-full rounded-md border border-gray-300 p-2 text-sm" />
                            </div>

                            <div class="space-y-4 md:col-span-3">
                                <FloatLabel>
                                    <Select v-model="chainsaw_form.purpose" :options="purposeOptions" class="w-full" />
                                    <label>Purpose of Purchase</label>
                                </FloatLabel>

                                <!-- Conditional Uploads -->
                                <!-- <div v-if="
                                        page.props.application.purpose === 'For selling / re-selling' ||
                                        page.props.application.purpose === 'Forestry/landscaping service provider'
                                    ">
                                        <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI
                                            Registration</label>
                                        <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                            @change="(e) => (chainsaws[index].mayorDTI = e.target.files[0])" />
                                    </div>

                                    <div v-if="page.props.application.purpose === 'Other Legal Purpose'">
                                        <label class="text-sm font-medium text-gray-700">Upload Notarized
                                            Affidavit</label>
                                        <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                            @change="(e) => (chainsaws[index].affidavit = e.target.files[0])" />
                                    </div>

                                    <div v-if="page.props.application.purpose === 'Other Supporting Documents'">
                                        <label class="text-sm font-medium text-gray-700">Upload Supporting
                                            Document</label>
                                        <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                            @change="(e) => (chainsaws[index].otherDocs = e.target.files[0])" />
                                    </div> -->
                            </div>

                            <div class="md:col-span-3">
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.other_details" class="w-full" />
                                    <label>Other Details</label>
                                </FloatLabel>
                            </div>
                            <div class="grid gap-6 md:col-span-3 md:grid-cols-2">
                                <!-- Permit Number -->
                                <!-- <div>
                                    <FloatLabel>
                                        <InputText v-model="chainsaw.permit_chainsaw_no" class="w-full" />
                                        <label>Permit to Sell / Re-Sell Chainsaw No.</label>
                                    </FloatLabel>
                                </div> -->

                                <!-- Permit Validity -->
                                <div>
                                    <FloatLabel>
                                        <InputText v-model="chainsaw_form.permit_chainsaw_no" class="w-full" />
                                        <label>Permit to Sell No.</label>
                                    </FloatLabel>
                                </div>
                                <div>
                                    <FloatLabel>
                                        <DatePicker type="date" id="permit_validity"
                                            v-model="chainsaw_form.permit_validity" class="w-full" />
                                        <label>Permit Validity</label>
                                    </FloatLabel>
                                </div>
                            </div>
                            <!-- 
                                <div class="md:col-span-3">
                                    <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>

                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].permit = e.target.files[0])" />
                                </div> -->

                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="addChainsaw"
                            class="mt-4 inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                            <span class="text-xl">＋</span> Add Another Chainsaw
                        </button>
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 3" class="space-y-6">
            <Fieldset legend="Payment of Application Fee">
                <div class="relative">
                    <div class="ribbon">
                        {{ page.props.application.status_title || 'DRAFT' }}
                    </div>


                    <!-- FORM CONTENT -->
                    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">

                        <!-- Application No -->
                        <div>
                            <FloatLabel>
                                <InputText v-model="payment_form.application_no" class="w-full font-bold" readonly />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>

                        <!-- Permit No (only when available) -->
                        <div v-if="payment_form.permit_no">
                            <FloatLabel>
                                <InputText v-model="payment_form.permit_no" class="w-full font-bold" readonly />
                                <label>Permit No.</label>
                            </FloatLabel>
                        </div>

                        <!-- Official Receipt -->
                        <div>
                            <FloatLabel>
                                <InputText v-model="payment_form.official_receipt" class="w-full" />
                                <label>O.R No.</label>
                            </FloatLabel>
                        </div>

                        <!-- Permit Fee -->
                        <div>
                            <FloatLabel>
                                <InputNumber v-model="payment_form.permit_fee" class="w-full" mode="currency"
                                    currency="PHP" />
                                <label>Permit Fee</label>
                            </FloatLabel>
                        </div>

                        <!-- Remarks (FULL WIDTH) -->
                        <div class="md:col-span-2">
                            <FloatLabel>
                                <Textarea v-model="payment_form.remarks" rows="4" class="w-full" />
                                <label>
                                    Remarks (Memorandum / Electronic Message and Date of Compliance)
                                </label>
                            </FloatLabel>
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
                        {{ individual_form.status_title ?? "DRAFT" }}
                    </div>

                    <div class="grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-bold">Application No:</span>
                            <Tag :value="individual_form.application_no" severity="success"
                                class="text-center font-extrabold" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Date Applied:</span>
                            <span>{{ individual_form.date_applied }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Type of Transaction:</span>
                            <span>{{ individual_form.type_of_transaction }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Classification:</span>
                            <span>{{ individual_form.classification }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Contact Details:</span>
                            <span>{{ individual_form.mobile_no }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Applicant Name:</span>
                            <span>{{ individual_form.first_name }} {{ individual_form.middle_name }} {{
                                individual_form.last_name }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Email Address:</span>
                            <span>{{ individual_form.email_address }}</span>
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
                            <span>{{ individual_form.i_complete_address }}</span>
                        </div>


                    </div>
                </div>
            </Fieldset>
            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit No:</span>
                        <Tag :value="chainsaw_form.permit_no" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Validity:</span>
                        <Tag :value="chainsaw_form.permit_validity" severity="danger" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Brand:</span>
                        <span>{{ chainsaw_form.brand }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Model:</span>
                        <span>{{ chainsaw_form.model }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Quantity:</span>
                        <span>{{ chainsaw_form.quantity }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Name:</span>
                        <span>{{ chainsaw_form.supplier_name }}</span>
                    </div>
                    <!-- <div class="flex">
                        <span class="w-48 font-semibold">Supplier Address:</span>
                        <span>123 Supplier St., Calabarzon</span>
                    </div> -->
                    <div class="flex">
                        <span class="w-48 font-semibold">Purpose of Purchase:</span>
                        <span>{{ chainsaw_form.purpose }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Other Details:</span>
                        <span>{{ chainsaw_form.other_details }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Official Receipt:</span>
                        <Tag :value="chainsaw_form.official_receipt" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Fee:</span>
                        <span>₱ {{ chainsaw_form.permit_fee }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Remarks:</span>
                        <span>{{ chainsaw_form.remarks }}</span>
                    </div>
                </div>
            </Fieldset>
            <Fieldset legend="Uploaded Files" :toggleable="true">
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full border border-gray-300 rounded-lg bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 border text-left">#</th>
                                <th class="px-4 py-2 border text-left">Documentary Requirements</th>
                                <th class="px-4 py-2 border text-left">File Name</th>
                                <th class="px-4 py-2 border text-left">Type</th>
                                <th class="px-4 py-2 border text-left">Size</th>
                                <th class="px-4 py-2 border text-left">Uploaded At</th>
                                <th class="px-4 py-2 border text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(file, index) in files" :key="index" class="hover:bg-gray-50">
                                <td class="px-4 py-2 border">{{ index + 1 }}</td>

                                <!-- AUTOMATIC DOCUMENT TITLE -->
                                <td class="px-4 py-2 border">{{ getDocumentTitle(file.name) }}</td>

                                <td class="px-4 py-2 border">{{ file.name }}</td>
                                <td class="px-4 py-2 border">{{ getFileType(file.name) || 'Unknown' }}</td>
                                <td class="px-4 py-2 border">{{ file.size }}</td>
                                <td class="px-4 py-2 border">{{ file.created_at }}</td>

                                <td class="px-4 py-2 border text-center">
                                    <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                                        @click="openFileModal(file)">
                                        Preview
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="files.length === 0">
                                <td colspan="7" class="text-center py-4 text-gray-500">
                                    No uploaded files.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                    <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                        allow="autoplay"></iframe>
                </Dialog>

            </Fieldset>
        </div>

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <!-- <Button v-if="currentStep < 3" class="ml-auto" @click="nextStep">Next</Button> -->
            <Button v-if="currentStep <= 3" class="ml-auto flex items-center justify-center gap-2" @click="nextStep"
                :disabled="isLoading" style="background-color: #004D40;">
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                <span>Save as Draft</span>
            </Button>
            <!-- 
            <Button style="background-color: #004D40;" v-if="currentStep === 4" class="ml-auto" @click="submitForm"> Submit
                Application </Button> -->
            <ConfirmModal v-if="currentStep === 4" :applicationId="Number(application_id)" />
        </div>
    </div>
</template>

<style>
/* HTML: <div class="ribbon">Your text content</div> */
.ribbon {
    font-size: 19px;
    font-weight: bold;
    color: #fff;
}

.ribbon {
    --r: .8em;
    /* control the cutout */
    margin-left: 934px;
    margin-top: -20px;
    position: relative;
    border-block: .5em solid #0000;
    padding-inline: calc(var(--r) + .25em) .5em;
    line-height: 1.8;
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, var(--r) calc(100% - .25em), 0 50%, var(--r) .25em);
    background:
        radial-gradient(.2em 50% at right, #000a, #0000) border-box,
        #BD1550 padding-box;
    /* the color  */
    width: fit-content;
}
</style>
