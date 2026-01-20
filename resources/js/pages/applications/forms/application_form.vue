<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

// UI & Icons
import { Button } from '@/components/ui/button';
import { LoaderCircle, ShieldAlert } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import ConfirmModal from '../modal/confirmation_modal.vue';
import chainsaw_individualInfoField from './chainsaw_individualInfoField.vue';
import FileCard from './file_card.vue';

// Composables
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';

// State
const props = defineProps({
    application: Object,
    mode: String,
});

const toast = useToast();
const { individual_form, chainsaw_form, payment_form } = useAppForm();
const page = usePage();

// Extract your application data
const application = page.props.application;

console.log(application); // You will see your full groupedDetails object

const { insertFormData } = useFormHandler();
const { getProvinceCode, getApplicationNumber, prov_name } = useApi();
const isLoading = ref(false);
const applicationData = ref([]);
const files = ref([]);
const i_city_mun = ref(0);
const errorMessage = ref('');
const currentStep = ref(1);
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const userId = page.props.auth?.user?.id;
const selectedFile = ref(null);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)
const showModal = ref(false);

// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Permit to Sell', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const formValidationRules = {
    1: {
        form: 'individual_form', // ✅ new step for individual form
        fields: ['date_applied', 'application_type', 'type_of_transaction', 'geo_code', 'last_name', 'first_name', 'sex'],
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
        fields: ['permit_validity', 'permit_chainsaw_no', 'brand', 'model', 'quantity', 'supplier_name', 'supplier_address', 'purpose'],
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
        formToCheck = chainsaws;
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

const openFileModal = (file) => {
    selectedFile.value = file;
    showModal.value = true;
};
const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file
    updateFileInput.value.click()
}
const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0]
    if (!newFile || !selectedFileToUpdate.value) return

    try {
        const formData = new FormData()
        formData.append('application_id', selectedFileToUpdate.value.application_id)
        formData.append('file', newFile)
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id)
        formData.append('name', selectedFileToUpdate.value.name)

        const response = await axios.post('http://192.168.2.106:8000/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        // Update file list
        const updatedIndex = files.value.findIndex(f => f.id === selectedFileToUpdate.value.id)
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile
        }

        toast.add({ severity: 'success', summary: 'Successful', detail: 'File updated successfully', life: 3000 });

    } catch (error) {
        console.error(error)
        toast.add({ severity: 'error', summary: 'Successful', detail: 'Failed to update the file.', life: 3000 });

    } finally {
        updateFileInput.value.value = '' // reset file input
        selectedFileToUpdate.value = null
    }
}


const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};
// -------------------------
// Next Step Logic
// -------------------------
const nextStep = async () => {
    // If already at the last step, stop
    if (currentStep.value >= steps.value.length) return;

    isLoading.value = true;

    // Step-specific save handlers
    const handlers = {
        1: saveIndividualApplication,
        2: submitChainsawInfo,
        3: submitORPayment,
        // Step 4 has no draft-saving handler
    };

    const handler = handlers[currentStep.value];

    // If the current step has a save handler → run it
    if (handler) {
        const isSaved = await handler();

        // Stop here if save failed
        if (!isSaved) {
            isLoading.value = false;
            return;
        }

        // Refresh application data after saving
        await getApplicationDetails();

        // If application data did not load properly, stop
        if (!applicationData.value || !applicationData.value.application_no) {
            console.error('Application details missing after save. Step will not advance.');
            isLoading.value = false;
            return;
        }
    }

    // Move to the next step
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

    try {
        const response = await insertFormData('http://192.168.2.106:8000/api/chainsaw/apply', { ...individual_form, encoded_by: userId });

        // ⚡ Change URL WITHOUT RELOAD
        const newUrl = route('applications.index', {
            application_id: response.application_id,
            type: 'individual',
        });
        window.history.pushState({}, '', newUrl);

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Individual application submitted successfully.',
            life: 3000,
        });

        return true; // let nextStep() move to the next tab
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
    }
};

const submitChainsawInfo = async () => {
    isLoading.value = true;
    // 🔥 Get type from URL
    const routeParams = new URLSearchParams(window.location.search);
    const applicantType = routeParams.get('type');
    const applicationId = routeParams.get('application_id');

    try {
        for (const chainsaw of chainsaws) {
            const formData = new FormData();

            Object.entries(chainsaw).forEach(([key, value]) => {
                if (value !== null && value !== undefined && !(value instanceof File)) {
                    formData.append(key, value);
                }
            });

            // Existing data
            formData.append('application_no', applicationData.value.application_no);

            // 🔥 Add applicant type
            formData.append('applicant_type', applicantType);
            formData.append('application_id', applicationId);

            // Attach files
            ['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((fileKey) => {
                if (chainsaw[fileKey]) formData.append(fileKey, chainsaw[fileKey]);
            });

            // Send to API
            await axios.post('http://192.168.2.106:8000/api/chainsaw/insertChainsawInfo', formData,
                {
                    params: { id: applicationId },
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
        }

        return true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000,
        });
        return false;
    }
};

const submitORPayment = async () => {
    isLoading.value = true;

    const formData = new FormData();
    const routeParams = new URLSearchParams(window.location.search);
    const applicantType = routeParams.get('type');
    const applicationId = routeParams.get('application_id');

    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('permit_no', applicationData.value.permit_no);
    formData.append('or_copy', payment_form.or_copy);
    formData.append('applicant_type', applicantType);
    formData.append('application_id', applicationId);
    try {
        const response = await axios.post('http://192.168.2.106:8000/api/chainsaw/insert_payment', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Payment Details submitted successfully',
            life: 3000,
        });

        // FIXED: Accessing correct response field
        const newUrl = route('applications.index', {
            application_id: response.data.application_id,
            type: 'individual',
        });

        window.history.pushState({}, '', newUrl);

        return true; // <-- THIS WILL NOW WORK
    } catch (error) {
        console.error('Failed to save payment details:', error);

        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000,
        });

        return false;
    } finally {
        isLoading.value = false;
    }
};

const submitForm = () => {
    form.post('/chainsaw-permit', {
        onSuccess: () => {
            alert('Application submitted successfully!');
        },
    });
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
        applicationData.value = response.data.data || [];
        i_city_mun.value = response.data.data.i_city_mun;
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

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

// ─────────────────────────────────────────────────────────────
// CHAINSaw Section
// ─────────────────────────────────────────────────────────────

const addChainsaw = () => {
    chainsaws.value.push({
        brand: '',
        model: '',
        quantity: 1,
        supplierName: '',
        supplierAddress: '',
        type: '',
        permitNumber: '',
        permitValidity: null,
        classification: '',
        price: '',
        dateEndorsed: null,
        purpose: '',
        otherDetails: '',
        letterRequest: null,
        copyAll: false,
    });
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

const handleFileUpload = (event: Event, index: number) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    chainsaws.value[index].letterRequest = file;
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
    purpose.value.purposeFiles[field] = file;
};

const isStepValid = (stepId) => {
    return true;
};

const handleStepClick = (targetStep) => {
    if (targetStep <= currentStep.value || isStepValid(currentStep.value)) {
        currentStep.value = targetStep;
    } else {
        showError();
    }
};

const showError = () => {
    toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please complete all required fields before proceeding.',
        life: 3000,
    });
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

onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4; // Jump to last step
    }

    getApplicationNumber(individual_form, chainsaw_form);
    getApplicationDetails();
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
                :app_data="applicationData" :getProvinceCode="getProvinceCode" :city_mun="i_city_mun"
                :getApplicationNumber="getApplicationNumber" :prov_name="prov_name" />
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="relative">
                    <div class="ribbon">
                        {{ applicationData.status_title || 'DRAFT' }}
                    </div>

                    <div class="flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>
                    <div v-for="(chainsaw, index) in chainsaws" :key="index"
                        class="bg-blue-40 relative rounded-lg p-5 shadow">
                        <!-- Remove Button -->
                        <button v-if="index > 0" @click="removeChainsaw(index)"
                            class="absolute top-2 right-2 text-red-600 hover:text-red-800" title="Remove">
                            ✕
                        </button>

                        <!-- Copy All Checkbox -->
                        <div v-if="index > 0" class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" v-model="chainsaw.copyAll" @change="copyAllFields(index)" />
                            <label>Same details as first chainsaw</label>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <FloatLabel>
                                    <InputText v-model="applicationData.application_no" class="w-full" />
                                    <label>Application No.</label>
                                </FloatLabel>
                            </div>
                            <div v-if="applicationData.permit_no">
                                <FloatLabel>
                                    <InputText v-model="applicationData.permit_no" class="w-full" />
                                    <label>Permit No.</label>
                                </FloatLabel>
                            </div>
                            <div></div>
                            <div></div>
                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw.quantity" type="number" class="w-full" />
                                    <label>Quantity</label>
                                </FloatLabel>
                            </div>
                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw.brand" class="w-full" />
                                    <label>Brand</label>
                                </FloatLabel>
                            </div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.model" class="w-full" />
                                <label>Model</label>
                            </FloatLabel>

                            <div class="grid grid-cols-1 gap-4 md:col-span-3 md:grid-cols-2">
                                <!-- Supplier Name -->
                                <FloatLabel>
                                    <InputText v-model="chainsaw.supplier_name" class="w-full" />
                                    <label>Supplier Name</label>
                                </FloatLabel>

                                <!-- Engine Serial No -->
                                <FloatLabel>
                                    <InputText v-model="chainsaw.engine_serial_no" class="w-full" />
                                    <label>Engine Serial No</label>
                                </FloatLabel>
                            </div>

                            <div class="md:col-span-3">
                                <Textarea id="address" v-model="chainsaw.supplier_address" rows="6" cols="3"
                                    placeholder="Complete Address (Street, Purok, etc.)"
                                    class="w-[70.5rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>

                            <div class="space-y-4 md:col-span-3">
                                <FloatLabel>
                                    <Select v-model="chainsaw.purpose" :options="purposeOptions" class="w-full" />
                                    <label>Purpose of Purchase</label>
                                </FloatLabel>

                                <!-- Conditional Uploads -->
                                <div v-if="
                                    chainsaw.purpose === 'For selling / re-selling' ||
                                    chainsaw.purpose === 'Forestry/landscaping service provider'
                                ">
                                    <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI
                                        Registration</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].mayorDTI = e.target.files[0])" />
                                </div>

                                <div v-if="chainsaw.purpose === 'Other Legal Purpose'">
                                    <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].affidavit = e.target.files[0])" />
                                </div>

                                <div v-if="chainsaw.purpose === 'Other Supporting Documents'">
                                    <label class="text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].otherDocs = e.target.files[0])" />
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <FloatLabel>
                                    <InputText v-model="chainsaw.others_details" class="w-full" />
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
                                        <DatePicker v-model="chainsaw.permit_validity" class="w-full" />
                                        <label>Permit Validity</label>
                                    </FloatLabel>
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>

                                <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                    @change="(e) => (chainsaws[index].permit = e.target.files[0])" />
                            </div>
                        </div>
                    </div>

                    <!-- Add Button -->
                    <div class="flex justify-end">
                        <button type="button" @click="addChainsaw"
                            class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700">
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
                        {{ applicationData.status_title || 'DRAFT' }}
                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <FloatLabel>
                                <InputText v-model="applicationData.application_no" class="w-full" />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>
                        <div v-if="applicationData.permit_no">
                            <FloatLabel>
                                <InputText v-model="applicationData.permit_no" class="w-full" />
                                <label>Permit No.</label>
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
                        <div>
                            <FloatLabel>
                                <Textarea rows="6" class="w-[70rem]" v-model="payment_form.remarks" />
                                <label>Remarks (Memorandum/Electronic Message and Date of Compliance)</label>
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
                        {{ applicationData.status_title ?? 'DRAFT' }}
                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
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
                            <span>{{ applicationData.type_of_transaction }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Classification:</span>
                            <span>{{ applicationData.classification }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Contact Details:</span>
                            <span>{{ applicationData.mobile_no }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Applicant Name:</span>
                            <span>{{ applicationData.first_name }} {{ applicationData.middle_name }} {{
                                applicationData.last_name }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Email Address:</span>
                            <span>{{ applicationData.email_address }}</span>
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
                            <span>{{ applicationData.applicant_complete_address }}</span>
                        </div>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                    <div class="flex">
                        <span class="w-48 font-semibold">Chainsaw Permit No:</span>
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
                        <span>₱ {{ applicationData.permit_fee }}.00</span>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Uploaded Files" :toggleable="true">
                <div class="container">
                    <div class="file-list">
                        <FileCard v-for="(file, index) in files" :key="index" :file="file" @openPreview="openFileModal"
                            @updateFile="triggerUpdateFile" />
                        <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                    </div>
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
                :disabled="isLoading" style="background-color: #004d40">
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
</style>
