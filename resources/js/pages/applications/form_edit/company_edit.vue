<script setup lang="ts">
// Imports
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Fieldset from 'primevue/fieldset';
import { ShieldAlert } from 'lucide-vue-next';

// Custom Components
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { updateChainsawForm } from '@/lib/chainsaw';
import { ChainsawData } from '@/types/chainsaw';
import LoadingSpinner from '../../LoadingSpinner.vue';
import Chainsaw_applicationField from '../forms/chainsaw_applicationField.vue';
import Chainsaw_companyField from '../forms/chainsaw_companyField.vue';
import Chainsaw_operationField from '../forms/chainsaw_operationField.vue';
import FileCard from '../forms/file_card.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    application: Object,
    mode: String,
});

// Form Data
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getProvinceCode, prov_name } = useApi();
const page = usePage();

Object.assign(company_form, page.props.application || {});
Object.assign(chainsaw_form, page.props.application || {});
Object.assign(payment_form, page.props.application || {});

// Refs & Reactives
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const toast = useToast();
const userId = page.props.auth?.user?.id;
const files = ref([]);
const isLoading = ref(false);
const isloadingSpinner = ref(false);
const applicationData = ref([]);
const currentStep = ref(4);
const errorMessage = ref('');
const selectedFile = ref(null);
const showModal = ref(false);
const showFileModal = ref(false);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)





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
    { label: 'Applicant Form', id: 1 },
    { label: 'Permit to Sell', id: 2 },
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
    if (currentStep.value < steps.value.length) {
        // const isValid = validateForm();

        // Stop if validation fails
        // if (!isValid) return;

        const handlers = [null, updateCompanyApplication, updateChainsawInformation, updatePaymentInfo];
        const isSaved = await handlers[currentStep.value]?.();

        if (isSaved) {
            currentStep.value++;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Save Failed',
                detail: 'There was an issue saving the current step. Please try again.',
                life: 3000,
            });
        }
    }
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


const openFileModal = (file) => {
    selectedFile.value = file;
    showModal.value = true;
};


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

const handleFileUpload = (event, index) => {
    chainsaws[index].letterRequest = event.target.files[0];
};

const handlePurposeFileUpload = (event, fieldName, index) => {
    chainsaws[index][fieldName] = event.target.files[0];
};

const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};


const updateCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = page.props.application.id;

    const formData = new FormData();

    // 🔥 Append ALL fields from company_form automatically
    Object.entries(company_form).forEach(([key, value]) => {
        // Skip null values safely
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    // 🔥 Add encoded_by separately
    formData.append("encoded_by", userId);

    try {
        const response = await axios.post(
            `/applications/${applicationId}/update-company-data`,
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Company application updated successfully.",
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: "error",
            summary: "Failed",
            detail: "There was an error saving the application.",
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const updateChainsawInformation = async () => {
    isLoading.value = true;
    // isloadingSpinner.value = true;
    const applicationId = page.props.application.id;
    try {
        for (const chainsaw of chainsaws) {
            const formData = new FormData();

            // Object.entries(chainsaw_form).forEach(([key, value]) => {
            //     if (value !== null && value !== undefined && !(value instanceof File)) {
            //         formData.append(key, value);
            //     }
            // });

            ['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((fileKey) => {
                if (chainsaw[fileKey]) formData.append(fileKey, chainsaw[fileKey]);
            });

            const response = await axios.put(`/applications/${applicationId}/update-chainsaw-info`, {
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

            if (response.data.status === "success") {
                toast.add({
                    severity: "success",
                    summary: "Updated",
                    detail: "Company application updated successfully.",
                    life: 3000,
                });
                return true;
            } else {
                toast.add({
                    severity: "warn",
                    summary: "No Changes",
                    detail: response.data.message,
                    life: 3000,
                });
                return false;
            }
        }
        return true;
    } catch (error) {
        console.error('Upload failed:', error);
        return false;
    } finally {
        isloadingSpinner.value = false;
    }
};

const updatePaymentInfo = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const applicationId = page.props.application.id;

    const formData = new FormData();

    // 🔥 Append ALL fields from company_form automatically
    Object.entries(payment_form).forEach(([key, value]) => {
        // Skip null values safely
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    // 🔥 Add encoded_by separately
    formData.append("encoded_by", userId);

    try {
        const response = await axios.post(
            `/applications/${applicationId}/update-company-payment-data`,
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Payment Info updated successfully.",
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: "error",
            summary: "Failed",
            detail: "There was an error saving the application.",
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitApplication = async () => {
    try {
        const applicationId = page.props.application.id;
        const officeId = page.props.application.office_title;
        
        const response = await axios.put(`/applications/${applicationId}/submit-application`, {
            application_id: applicationId,
            office:officeId
        });

        if (response.data.status === "success") {
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Company application updated successfully.",
                life: 3000,
            });
            return true;
        } else {
            toast.add({
                severity: "warn",
                summary: "No Changes",
                detail: response.data.message,
                life: 3000,
            });
            return false;
        }
    } catch (error) {

    }
}
// API Calls


// Map tab number → allowed prefixes (from your folderMap)
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

const getApplicantFile = async (id) => {
    try {
        const response = await axios.get(
            `http://192.168.2.106:8000/api/getApplicantFile/${id}`
        );

        if (response.data.status && Array.isArray(response.data.data)) {

            const fetchedFiles = response.data.data.map((file) => ({
                attachment_id: file.id,
                application_id: file.application_id,
                name: file.file_name,
                size: 'Unknown',
                dateUploaded: new Date(file.created_at).toLocaleDateString(),
                dateOpened: new Date().toLocaleDateString(),
                icon: file.file_name.split('.').pop(),
                thumbnail: null,
                url: file.file_url,
            }));

            // CLASSIFY FILES BY TAB
            Object.keys(tabMap).forEach(tabId => {
                const prefixes = tabMap[tabId];

                filesByTab.value[tabId] = fetchedFiles.filter(f =>
                    prefixes.some(prefix =>
                        f.name.toLowerCase().startsWith(prefix)
                    )
                );
            });

            // NEW: Add ALL FILES tab
            filesByTab.value[0] = fetchedFiles;


        }
    } catch (err) {
        console.error(err);
    }
}




// const getApplicantFile = async () => {
//     const applicationId = page.props.application.id;
//     if (!applicationId) return;

//     try {
//         const response = await axios.get(`http://192.168.2.106:8000/api/getApplicantFile/${applicationId}`);
//         if (response.data.status && Array.isArray(response.data.data)) {
//             files.value = response.data.data.map((file) => ({
//                 name: file.file_name,
//                 size: 'Unknown',
//                 dateUploaded: new Date(file.created_at).toLocaleDateString(),
//                 dateOpened: new Date().toLocaleDateString(),
//                 icon: 'png',
//                 thumbnail: null,
//                 url: file.file_url,
//             }));
//         }
//     } catch (error) {
//         console.error('Failed to fetch files:', error);
//     }
// };


const openFile = (file) => {
    selectedFile.value = file;
    showFileModal.value = true;

};

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

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};
onMounted(() => {
    if (props.mode === 'view') {
        currentStep.value = 4; // Jump to last step
    }

    getProvinceCode();
    getApplicantFile(page.props.application.id);
});
</script>
<template>
    <div class="mt-10 space-y-6">
        <Toast />
        <LoadingSpinner :loading="isloadingSpinner" />
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between">
            <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center"
                @click="handleStepClick(step.id)">
                <div :class="[
                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                    currentStep === step.id ? 'bg-green-600' : 'bg-gray-300',
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

            <Chainsaw_applicationField :form="company_form" :prov_name="prov_name" :insertFormData="insertFormData"
                :getProvinceCode="getProvinceCode" :activeStep="currentStep" />
            <Chainsaw_companyField :form="company_form" :app_data="applicationData" />
            <!-- <Chainsaw_operationField :form="company_form" /> -->
            <div class="md:col-span-3">
                <Fieldset legend="Uploaded Requirements" :toggleable="true">
                    <div class="container">
                        <div class="file-list grid grid-cols-1 gap-2 md:grid-cols-2">

                            <!-- Display only the files for the current tab -->
                            <FileCard v-for="(file, index) in filesByTab[currentStep]" :key="index" :file="file"
                                @openPreview="openFile" @updateFile="triggerUpdateFile" />

                            <!-- Hidden file update input -->
                            <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                            <!-- File Preview Modal -->
                            <Dialog v-model:visible="showFileModal" modal header="File Preview"
                                :style="{ width: '70vw' }">
                                <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%"
                                    height="500" allow="autoplay"></iframe>
                            </Dialog>

                        </div>
                    </div>
                </Fieldset>

            </div>
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="relative">
                    <div class="ribbon">
                        {{ applicationData.status_title || "DRAFT" }}
                    </div>


                    <div class=" flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>
                    <div v-for="(chainsaw, index) in chainsaws" :key="index"
                        class="bg-blue-40 relative mb-6 rounded-lg p-5 shadow">
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

                        <div class=" grid grid-cols-1 gap-6 md:grid-cols-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw_form.application_no" class="w-full font-bold" disabled />
                                <label>Application No.</label>
                            </FloatLabel>
                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.permit_no" class="w-full font-bold" />
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


                            <div class="md:col-span-3">
                                <FloatLabel>
                                    <InputText v-model="chainsaw_form.supplier_name" class="w-full" />
                                    <label>Supplier Name</label>
                                </FloatLabel>
                            </div>
                            <div class="md:col-span-3">
                                <label>Supplier Address</label>

                                <Textarea id="address" v-model="chainsaw_form.supplier_address" rows="6" cols="3"
                                    placeholder="Complete Address (Street, Purok, etc.)"
                                    class="w-[70rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>
                            <div class="space-y-4 md:col-span-3">
                                <FloatLabel>
                                    <Select v-model="chainsaw_form.purpose" :options="purposeOptions" class="w-full" />
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
                                        class="mt-1 w-full cursor-pointer rou   nded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
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
                                        <DatePicker v-model="chainsaw_form.permit_validity" class="w-full" />
                                        <label>Permit Validity</label>
                                    </FloatLabel>
                                </div>
                            </div>

                            <!-- <div class="md:col-span-3">
                                <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>
                                <input type="file" accept=".jpg,.jpeg,.pdf,.docx,.png"
                                    class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                    @change="(e) => (chainsaws[index].permit = e.target.files[0])" />
                            </div> -->

                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <Fieldset legend="Uploaded Requirements" :toggleable="true">
                            <div class="container">
                                <div class="file-list grid grid-cols-1 gap-2 md:grid-cols-2">

                                    <!-- Display only the files for the current tab -->
                                    <FileCard v-for="(file, index) in filesByTab[currentStep]" :key="index" :file="file"
                                        @openPreview="openFile" @updateFile="triggerUpdateFile" />

                                    <!-- Hidden file update input -->
                                    <input type="file" ref="updateFileInput" class="hidden"
                                        @change="handleFileUpdate" />

                                    <!-- File Preview Modal -->
                                    <Dialog v-model:visible="showFileModal" modal header="File Preview"
                                        :style="{ width: '70vw' }">
                                        <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%"
                                            height="500" allow="autoplay"></iframe>
                                    </Dialog>

                                </div>
                            </div>
                        </Fieldset>
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
                        {{ payment_form.status_title || "DRAFT" }}

                    </div>
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                        <span> Please complete all fields to proceed with your application for a Permit to Purchase
                            Chainsaw. </span>
                    </div>

                    <div class="mt-2 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <FloatLabel>
                                <InputText v-model="payment_form.application_no" class="w-full font-bold" disabled />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputText v-model="payment_form.permit_no" class="w-full font-bold" />
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
                        <div>
                            <FloatLabel>
                                <Textarea rows="6" class="w-[73rem]" v-model="payment_form.remarks" />
                                <label>Remarks (Memorandum/Electronic Message and Date of Compliance)</label>
                            </FloatLabel>
                        </div>

                    </div>
                </div>
            </Fieldset>
            <Fieldset legend="Uploaded Requirements" :toggleable="true">
                <div class="container">
                    <div class="file-list grid grid-cols-1 gap-2 md:grid-cols-2">

                        <!-- Display only the files for the current tab -->
                        <FileCard v-for="(file, index) in filesByTab[currentStep]" :key="index" :file="file"
                            @openPreview="openFile" @updateFile="triggerUpdateFile" />

                        <!-- Hidden file update input -->
                        <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                        <!-- File Preview Modal -->
                        <Dialog v-model:visible="showFileModal" modal header="File Preview" :style="{ width: '70vw' }">
                            <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                                allow="autoplay"></iframe>
                        </Dialog>

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
                        {{ company_form.status_title || "DRAFT" }}

                    </div>
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Application No:</span>
                            <Tag :value="company_form.application_no" severity="success" class="text-center" />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Application Type:</span>
                            <Tag :value="company_form.application_type" severity="success" class="text-center" />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Date Applied:</span>
                            <span>{{ company_form.date_applied }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Type of Transaction:</span>
                            <span>{{ company_form.type_of_transaction }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Company Name:</span>
                            <span>{{ company_form.company_name }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Authorized Representative:</span>
                            <span>{{ company_form.authorized_representative }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Region:</span>
                            <span>REGION IV-A (CALABARZON)</span>
                        </div>
                        <!-- <div class="flex">
                        <span class="w-48 font-semibold">Province:</span>
                        <span>{{ company_form.prov_name }}</span>
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
                            <span>{{ company_form.company_address }}</span>
                        </div>

                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                    <div class="flex">
                        <span class="w-48 font-semibold">Chainsaw No:</span>
                        <Tag :value="chainsaw_form.permit_chainsaw_no" severity="success" class="text-center" /><br />
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
                </div>
            </Fieldset>

            <Fieldset legend="Uploaded Files" :toggleable="true">
                <div class="container">
                    <div class="file-list">
                        <FileCard v-for="file in filesByTab[0]" :key="index" :file="file"
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
            <Button v-if="currentStep <= 3" class="ml-auto" @click="nextStep">Save as Draft</Button>
            <Button v-if="currentStep === 4" class="ml-auto" @click="submitApplication"> Submit Application </Button>
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