<script setup lang="ts">
import { ref, watch, reactive, onMounted, toRaw, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AssessmentTable from './assessment_tbl.vue';
// UI & Icons

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import Fieldset from 'primevue/fieldset';
import { LoaderCircle, ShieldAlert, Trash2, CirclePlus, MonitorUp } from 'lucide-vue-next';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import chainsaw_individualInfoField from '../forms/chainsaw_individualInfoField.vue';
import FileCard from '../forms/file_card.vue';
import ConfirmModal from '../modal/confirmation_modal.vue';

// Composables
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useApi } from '@/composables/useApi';
import Textarea from 'primevue/textarea';

// State
const props = defineProps({
    application: Object,
    mode: String,
});
const onsite = {
    findings: '',
    recommendations: ''
};
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
const assessmentRows = ref([])

const i_city_mun = ref<number | string>(0);
const errorMessage = ref('');
const currentStep = ref(4);

// IMPORTANT: initialize chainsaws correctly using createChainsaw()
// Use ref (so handlers calling chainsaws.value.push(...) work)
const chainsaws = ref<ReturnType<typeof createChainsaw>[]>([createChainsaw()]);
const is_not_compliance = ref();
const is_compliance = ref();
const userId = page.props.auth?.user?.id ?? null;
const selectedFile = ref(null);
const showModal = ref(false);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)

const brands = ref([
    {
        name: '',
        models: [{ model: '', quantity: 1 }]
    }
])

const updateAssessment = (id, value) => {
    const row = assessmentRows.value.find(r => r.checklist_entry_id === id);
    if (row) {
        row.assessment = value; // reactive update
        // do NOT set row.is_saved yet
    }
};

// Example function to save to DB
const saveAssessment = async (row) => {
    try {
        await axios.post('/api/saveAssessment', {
            checklist_entry_id: row.checklist_entry_id,
            assessment: row.assessment,
            remarks: row.remarks
        });
        row.is_saved = true; // now lock buttons
    } catch (err) {
        console.error(err);
    }
};




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

// Laravel-ready payload
const getPayload = () => {
    return {
        brand: brand.value,
        models: models.value.map(m => m.model)
    }
}
const tabMap = {
    1: ['request_letter', 'secretary_certificate'],
    2: ['mayors_permit', 'notarized_documents', 'permit', 'others'],
    3: ['official_receipt']
};
const filesByTab = ref({
    0: [],
    1: [],
    2: [],
    3: []
});
// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Form', id: 1 },
    { label: 'Permit to Sell Chainsaw', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);
const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file
    updateFileInput.value.click();
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
        location.reload();

    } catch (error) {
        console.error(error)
        toast.add({ severity: 'error', summary: 'Failed', detail: 'Failed to update the file.', life: 3000 });
    } finally {
        updateFileInput.value.value = '' // reset file input
        selectedFileToUpdate.value = null
    }
}
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

const getDocumentaryRequirements = async () => {
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
                isPassed: null, // null = not selected, true = pass, false = fail

            }));
        } else {
            console.log('No files');
        }
    } catch (error) {
        console.error('Failed to fetch files:', error);
    }
};
const loadBrands = async () => {
    const applicationId = page.props.application.id;
    if (!applicationId) {
        errorMessage.value = 'No application ID found in the query.';
        isLoading.value = false;
        return;
    }

    const res = await axios.get(
        `http://192.168.2.106:8000/api/chainsaw/${applicationId}/brands`
    )

    // If data exists, overwrite
    if (res.data.length) {
        brands.value = res.data
    }
}

const getApplicantFile = async (id) => {
    try {
        const response = await axios.get(
            `http://192.168.2.106:8000/api/getApplicantFile/${id}`
        )

        if (response.data.status && Array.isArray(response.data.data)) {
            assessmentRows.value = response.data.data.map(row => ({
                checklist_entry_id: row.checklist_entry_id,
                application_type: row.application_type,
                requirement: row.requirement,
                remarks: row.remarks,
                file_name: row.file_name,
                file_url: row.file_url,
                uploaded_at: row.created_at
                    ? new Date(row.created_at).toLocaleDateString()
                    : null,

                // 🔥 IMPORTANT NORMALIZATION
                assessment: row.assessment === 'pass'
                    ? 'passed'
                    : row.assessment === 'fail'
                        ? 'failed'
                        : null,

                // 🔒 first-time assessment
                is_saved: row.assessment === 'passed' || row.assessment === 'failed'
            }))

        }
    } catch (err) {
        console.error(err)
    }
}
const individualRequirements = computed(() =>
    assessmentRows.value.filter(
        r => r.application_type === 'Individual'
    )
)

const companyRequirements = computed(() =>
    assessmentRows.value.filter(
        r => r.application_type === 'Company'
    )
)


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
const isPassed = ref(null); // null = not selected, true = pass, false = fail

const setStatus = (status) => {
    if (status === 'pass') {
        isPassed.value = true;
    } else if (status === 'fail') {
        isPassed.value = false;
    }
    console.log('Selected status:', isPassed.value ? 'Pass' : 'Failed');
};
onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4;
    }
    getProvinceCode();
    getApplicantFile(page.props.application.id);
    loadBrands();
    getDocumentaryRequirements()
});
</script>



<template>
    <div>
        <Toast />
        <!-- Stepper -->
        <div class="flex items-center justify-between">

        </div>
        <Button class="gap-2 mr-2" style="background-color: rgba(0,77,64,1);" @click="nextStep">Received</Button>
        <Button style="background-color: #bd1550;">Return</Button>


        <div v-if="currentStep === 1" class="space-y-4">

            <chainsaw_individualInfoField :form="individual_form" :insertFormData="insertFormData"
                :getProvinceCode="getProvinceCode" :city_mun="i_city_mun" :prov_name="prov_name" />
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
            <Fieldset legend="Chainsaw Information" :toggleable="false">
                <div class="relative space-y-6">
                    <div class="ribbon">
                        {{ chainsaw_form.status_title || "DRAFT" }}

                    </div>
                    <!-- ALERT -->
                   

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
                                        <InputText v-model="chainsaw_form.supplier_name"
                                            v-letters-numbers-dash-uppercase class="w-full" />
                                        <label>Supplier Name</label>
                                    </FloatLabel>
                                </div>

                                <!-- Supplier Address -->
                                <div class="mt-4">
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">Supplier Address</label>
                                    <Textarea v-model="chainsaw_form.supplier_address" v-letters-only-uppercase rows="3"
                                        class="w-full" />
                                </div>

                                <!-- Purpose -->
                                <div class="mt-4">
                                    <FloatLabel>
                                        <Select v-model="chainsaw_form.purpose" :options="purposeOptions"
                                            class="w-full" />
                                        <label>Purpose of Purchase</label>
                                    </FloatLabel>
                                </div>

                                <!-- Permit Validity -->
                                <div class="mt-6">
                                    <FloatLabel>
                                        <DatePicker v-model="chainsaw_form.permit_validity" class="w-full" />
                                        <label>Permit Validity</label>
                                    </FloatLabel>
                                </div>

                                <!-- Other Details -->
                                <div class="mt-6">
                                    <FloatLabel>
                                        <InputText v-model="chainsaw_form.other_details" class="w-full" />
                                        <label>Other Details</label>
                                    </FloatLabel>
                                </div>
                            </div>


                            <!-- Right Column: File Uploads -->
                            <div class="flex-1 space-y-4">
                                <div
                                    v-if="['For selling / re-selling', 'Forestry/landscaping service provider'].includes(chainsaw_form.purpose)">
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


                                <div v-else-if="['Other Legal Purpose'].includes(chainsaw_form.purpose)">
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


                                    </div>

                                </div>

                                <div v-else-if="['Other Supporting Documents'].includes(chainsaw_form.purpose)">
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
                        {{ individual_form.status_title ?? 'DRAFT' }}
                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Application No:</span>
                            <Tag :value="individual_form.application_no" severity="success" class="text-center" />
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
                            <span>{{ applicationData.applicant_complete_address }}</span>
                        </div>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">

                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Validity:</span>
                        <Tag :value="chainsaw_form.permit_validity" severity="danger" />
                    </div>

                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Name:</span>
                        <span>{{ chainsaw_form.supplier_name }}</span>
                    </div>

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
                        <Tag :value="chainsaw_form.official_receipt" severity="success" />
                    </div>

                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Fee:</span>
                        <span>₱ {{ chainsaw_form.permit_fee }}.00</span>
                    </div>

                    <!-- ✅ Brands & Models -->
                    <div class="md:col-span-2">
                        <span class="block mb-2 font-semibold">Chainsaw Details:</span>

                        <div v-for="(brand, bIndex) in brands" :key="bIndex"
                            class="mb-4 rounded-lg border bg-gray-50 p-4">
                            <div class="mb-2">
                                <span class="font-semibold">Brand:</span>
                                <span class="ml-2">{{ brand.name }}</span>
                            </div>

                            <table class="w-full text-sm border">
                                <thead class="bg-blue-900 text-white">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Model</th>
                                        <th class="px-3 py-2 text-center w-32">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(model, mIndex) in brand.models" :key="mIndex">
                                        <td class="px-3 py-2">{{ model.model }}</td>
                                        <td class="px-3 py-2 text-center">{{ model.quantity }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </Fieldset>

            <AssessmentTable v-if="individual_form.application_type === 'Individual'"
                title="Individual Applicant Requirements" 
                :rows="individualRequirements" 
                :onsite="onsite"
                @view-file="openFileModal"
                @update-assessment="updateAssessment" />

            <AssessmentTable v-else="individual_form.application_type === 'Company'"
                title="Company Applicant Requirements" :rows="companyRequirements" @view-file="openFileModal"
                @update-assessment="updateAssessment" />



    



            <Dialog v-model:visible="showModal" modal header="File Preview" :style="{ width: '70vw' }">
                <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.file_url)" width="100%" height="500"
                    allow="autoplay"></iframe>
            </Dialog>

        </div>

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep <= 3" class="ml-auto flex items-center justify-center gap-2" @click="nextStep"
                :disabled="isLoading" style="background-color: #004D40;">
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                <span>Save as Draft</span>
            </Button>
              <Button class="ml-auto flex items-center justify-center gap-2 mr-2" @click="saveAssessment"
                :disabled="isLoading" style="background-color: #004D40;">
                Submit for Assessment
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                <span></span>
            </Button>
         
            <ConfirmModal v-if="currentStep === 4" :applicationId="Number(page.props.application.id)" />
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


/* Optional: smooth transition when switching colors */
button {
    transition: background-color 0.2s ease;
}

.table-container {
    max-width: 800px;
    margin: 20px auto;
}

.chainsaw-table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
}

.chainsaw-table th,
.chainsaw-table td {
    border: 1px solid #ddd;
    padding: 10px;
}

.chainsaw-table th {
    background-color: #f3f4f6;
    font-weight: bold;
}

.input {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.actions {
    text-align: center;
}

.btn {
    padding: 6px 10px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn.add {
    background-color: #16a34a;
    color: #fff;
}

.btn.remove {
    background-color: #dc2626;
    color: #fff;
    margin-left: 5px;
}

.btn:hover {
    opacity: 0.85;
}
</style>
