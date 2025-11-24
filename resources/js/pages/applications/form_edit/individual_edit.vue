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
const { individual_form, chainsaw_form, payment_form } = useAppForm();
const page = usePage();

// Extract your application data
Object.assign(individual_form, page.props.application);


const { insertFormData, updateFormData } = useFormHandler();
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
        fields: [
            'date_applied',
            'application_type',
            'type_of_transaction',
            'geo_code',
            'last_name',
            'first_name',
            'sex'
        ],
        labels: {
            date_applied: 'Date Applied',
            application_type: 'Application Type',
            type_of_transaction: 'Type of Transaction',
            geo_code: 'Geo Code',
            last_name: 'Last Name',
            first_name: 'First Name',
            sex: 'Sex'
        }
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
            'purpose'
        ],
        labels: {
            permit_validity: 'Permit Validity',
            permit_chainsaw_no: 'Permit Chainsaw No',
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


// -------------------------
// Individual Form Validation
// -------------------------
const validateForm = () => {
    const stepRules = formValidationRules[currentStep.value]

    if (!stepRules || !stepRules.fields || stepRules.fields.length === 0) return true

    let formToCheck: any[] = []

    // Determine which form to validate
    if (stepRules.form === 'individual_form') {
        formToCheck = [individual_form]
    } else if (stepRules.form === 'chainsaw_form') {
        formToCheck = chainsaws
    } else if (stepRules.form === 'payment_form') {
        formToCheck = [payment_form]
    }

    const missingFields: string[] = []

    formToCheck.forEach((form, index) => {
        stepRules.fields.forEach((field) => {
            if (form[field] === '' || form[field] === null || form[field] === undefined) {
                const label = stepRules.labels[field] || field
                if (formToCheck.length > 1) {
                    missingFields.push(`${label} (Chainsaw ${index + 1})`)
                } else {
                    missingFields.push(label)
                }
            }
        })
    })

    if (missingFields.length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete Fields',
            detail: `Please fill out the following fields: ${missingFields.join(', ')}`,
            life: 5000,
        })
        return false
    }

    return true
}

const openFileModal = (file) => {
    selectedFile.value = file;
    showModal.value = true;
};

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
        3: submitORPayment
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
            console.error("Application details missing after save. Step will not advance.");
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
const saveIndividualApplication = async() => {
    console.log(individual_form);
    isLoading.value = true;
    try {
        // PUT request with JSON
        const response = await axios.put(
            `/applications/${props.page.application.id}/update-applicant-data`,
            {
                application_type: 'Individual',
                last_name: individual_form.last_name,
                first_name: individual_form.first_name,
                middle_name: individual_form.middle_name,
                type_of_transaction: individual_form.type_of_transaction,
                date_applied: individual_form.date_applied,
                gov_id_number: individual_form.gov_id_number,
                encoded_by: userId,
            }
        );

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Updated',
                detail: 'Individual application updated successfully.',
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: 'warn',
                summary: 'No Changes',
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: error.message || 'Error saving application.',
            life: 3000,
        });
        console.error(error);
        return false;
    } finally {
        isLoading.value = false;
    }
}




const submitChainsawInfo = async () => {
    isLoading.value = true;

    // 🔥 Get type from URL
    const routeParams = new URLSearchParams(window.location.search);
    const applicantType = routeParams.get("type");
    const applicationId = routeParams.get("application_id");

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
            await axios.post(
                'http://10.201.13.78:8000/api/chainsaw/insertChainsawInfo',
                formData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }
            );
        }

        return true;
    } catch (error) {
        console.error(error);
    }
};


const submitORPayment = async () => {
    isLoading.value = true;

    const formData = new FormData();
    const routeParams = new URLSearchParams(window.location.search);
    const applicantType = routeParams.get("type");
    const applicationId = routeParams.get("application_id");

    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('permit_no', applicationData.value.permit_no);
    formData.append('or_copy', payment_form.or_copy);
    formData.append('applicant_type', applicantType);
    formData.append('application_id', applicationId);
    try {

        const response = await axios.post(
            'http://10.201.13.78:8000/api/chainsaw/insert_payment',
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Payment Details submitted successfully',
            life: 3000
        });

        // FIXED: Accessing correct response field
        const newUrl = route('applications.index', {
            application_id: response.data.application_id,
            type: 'individual'
        });

        window.history.pushState({}, '', newUrl);

        return true;  // <-- THIS WILL NOW WORK
    } catch (error) {
        console.error('Failed to save payment details:', error);

        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000
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

        const response = await axios.get(`http://10.201.13.78:8000/api/getApplicationDetails/${applicationId}`);
        applicationData.value = response.data.data || [];
        i_city_mun.value = response.data.data.i_city_mun;
    } catch (error) {
        errorMessage.value = error.message || 'Error fetching application data.';
    } finally {
        isLoading.value = false;

    }
};


const getApplicantFile = async () => {
    const applicationId = application.id;
    if (!applicationId) return;

    try {
        const response = await axios.get(`http://10.201.13.78:8000/api/getApplicantFile/${applicationId}`);
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
        } else {
            console.log("kim")
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
const getDocumentTitle = (fileName) => {
    if (!fileName) return "";

    const name = fileName.toLowerCase();

    if (name.startsWith("permit_")) return "Permit to Purchase / Chainsaw Permit";
    if (name.startsWith("mayors_")) return "Mayor’s Permit";
    if (name.startsWith("notarized_")) return "Notarized Application Form";
    if (name.startsWith("official_")) return "Official Receipt";
    if (name.startsWith("request_")) return "Request Letter";
    if (name.startsWith("secretary_")) return "Secretary’s Certificate";

    return "Supporting Document";
};

const getFileType = (fileName) => {
    if (!fileName) return "";
    return fileName.split('.').pop().toLowerCase();
}




onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4; // Jump to last step
    }


    // getApplicationNumber(individual_form, chainsaw_form);
    // getApplicationDetails();
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

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="relative">
                    <div class="ribbon">
                        {{ application.status_title || 'DRAFT' }}
                    </div>

                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>
                    <div v-for="(chainsaw, index) in chainsaws" :key="index">
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

                        <div class="mt-2 grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <FloatLabel>
                                    <InputText v-model="application.application_no" class="w-full" disabled />
                                    <label>Application No.</label>
                                </FloatLabel>
                            </div>
                            <!-- <div v-if="applicationData.permit_no">
                                <FloatLabel>
                                    <InputText v-model="applicationData.permit_no" class="w-full" />
                                    <label>Permit No.</label>
                                </FloatLabel>
                            </div> -->
                            <div>

                            </div>
                            <div>

                            </div>
                            <div>
                                <FloatLabel>
                                    <InputText v-model="application.quantity" type="number" class="w-full" />
                                    <label>Quantity</label>
                                </FloatLabel>
                            </div>
                            <div>
                                <FloatLabel>
                                    <InputText v-model="application.brand" class="w-full" />
                                    <label>Brand</label>
                                </FloatLabel>
                            </div>
                            <FloatLabel>
                                <InputText v-model="application.model" class="w-full" />
                                <label>Model</label>
                            </FloatLabel>

                            <div class="md:col-span-3">
                                <FloatLabel>
                                    <InputText v-model="application.supplier_name" class="w-full" />
                                    <label>Supplier Name</label>
                                </FloatLabel>
                            </div>
                            <div>

                            </div>
                            <div class="md:col-span-3">

                                <Textarea id="address" v-model="application.supplier_address" rows="6" cols="3"
                                    placeholder="Complete Address (Street, Purok, etc.)"
                                    class="w-[70.5rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>


                            <div class="space-y-4 md:col-span-3">
                                <FloatLabel>
                                    <Select v-model="application.purpose" :options="purposeOptions" class="w-full" />
                                    <label>Purpose of Purchase</label>
                                </FloatLabel>

                                <!-- Conditional Uploads -->
                                <div v-if="
                                    application.purpose === 'For selling / re-selling' ||
                                    application.purpose === 'Forestry/landscaping service provider'
                                ">
                                    <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI
                                        Registration</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].mayorDTI = e.target.files[0])" />
                                </div>

                                <div v-if="application.purpose === 'Other Legal Purpose'">
                                    <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].affidavit = e.target.files[0])" />
                                </div>

                                <div v-if="application.purpose === 'Other Supporting Documents'">
                                    <label class="text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                    <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].otherDocs = e.target.files[0])" />
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <FloatLabel>
                                    <InputText v-model="application.others_details" class="w-full" />
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
                                        <DatePicker v-model="application.permit_validity" class="w-full" />
                                        <label>Valid until:</label>
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
                        {{ application.status_title || 'DRAFT' }}
                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>

                    <div class="mt-2 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <FloatLabel>
                                <InputText v-model="application.application_no" class="w-full" />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>
                        <div v-if="application.permit_no">
                            <FloatLabel>
                                <InputText v-model="application.permit_no" class="w-full" />
                                <label>Permit No.</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputText class="w-full" v-model="application.official_receipt" />
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
                            <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official
                                Receipt</label>
                            <input type="file" accept=".jpg,.jpeg,.pdf"
                                @change="(e) => handleORFileUpload(e, 'or_copy')"
                                class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                        </div>
                        <div>
                            <FloatLabel>
                                <Textarea rows="6" class="w-[70rem]" v-model="application.remarks" />
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
                        {{ application.status_title ?? "DRAFT" }}
                    </div>

                    <div class="grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Application No:</span>
                            <Tag :value="application.application_no" severity="success" class="text-center" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Date Applied:</span>
                            <span>{{ application.date_applied }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Type of Transaction:</span>
                            <span>{{ application.type_of_transaction }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Classification:</span>
                            <span>{{ application.classification }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Contact Details:</span>
                            <span>{{ application.mobile_no }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Applicant Name:</span>
                            <span>{{ application.first_name }} {{ application.middle_name }} {{
                                application.last_name }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Email Address:</span>
                            <span>{{ application.email_address }}</span>
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
                            <span>{{ application.applicant_complete_address }}</span>
                        </div>


                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit No:</span>
                        <Tag :value="application.permit_no" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Validity:</span>
                        <Tag :value="application.permit_validity" severity="danger" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Brand:</span>
                        <span>{{ application.brand }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Model:</span>
                        <span>{{ application.model }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Quantity:</span>
                        <span>{{ application.quantity }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Name:</span>
                        <span>{{ application.supplier_name }}</span>
                    </div>
                    <!-- <div class="flex">
                        <span class="w-48 font-semibold">Supplier Address:</span>
                        <span>123 Supplier St., Calabarzon</span>
                    </div> -->
                    <div class="flex">
                        <span class="w-48 font-semibold">Purpose of Purchase:</span>
                        <span>{{ application.purpose }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Other Details:</span>
                        <span>{{ application.other_details }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Official Receipt:</span>
                        <Tag :value="application.official_receipt" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Fee:</span>
                        <span>₱ {{ application.permit_fee }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Remarks:</span>
                        <span>{{ application.remarks }}</span>
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
