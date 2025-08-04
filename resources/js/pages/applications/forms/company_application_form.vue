<script setup lang="ts">
// import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useForm } from '@inertiajs/vue3';
import { ShieldAlert } from 'lucide-vue-next';
import { useToast } from 'primevue/usetoast';
import FileCard from './file_card.vue'

import Fieldset from 'primevue/fieldset';

import Chainsaw_applicationField from './chainsaw_applicationField.vue';
import Chainsaw_companyField from './chainsaw_companyField.vue';
import Chainsaw_operationField from './chainsaw_operationField.vue';

import LoadingSpinner from '../../LoadingSpinner.vue';

import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { getApplicationNumber } = useApi();
const { insertFormData } = useFormHandler();
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const toast = useToast();
const form = useForm({
    official_receipt: null,
    date_applied: '07-01-2025',
    company_name: '',
    authorized_representative: '',
    email: '',
    surname: '',
    first_name: '',
    middlename: '',
    email_address: '',
    sex: '',
    civilStatus: '',
    address: '',
    mobile: '',
    telephone: '',
    purpose: '',
    quantity: '',
    brand: '',
    model: '',
    engineSerial: '',
    supplier: '',
    price: '',
    others: '',
    birthdate: '07-01-2025',
});
const files = ref([
    {
        name: "CALABARZON Final ICT Inventory...",
        type: "application",
        dateOpened: "Jul 28, 2025",
        icon: "app",
        thumbnail: null,
    },
    {
        name: "application-5f24d921...",
        type: "application",
        dateOpened: "Jul 28, 2025",
        icon: "app",
        thumbnail: null,
    },
    {
        name: "Diploma.pdf",
        type: "application",
        dateOpened: "Jul 16, 2025",
        icon: "app",
        thumbnail: null,
    },
])
const isloadingSpinner = ref(false);

const currentStep = ref(1);
const isLoading = ref(false);
const topProgress = ref<InstanceType<typeof TopProgressBar> | null>(null);
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Chainsaw Information', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

// Progress Bar

// End of Progress Bar

const validateForm = () => {
    const requiredFields = [
        'date_applied',
        'application_type',
        'type_of_transaction',
        'company_name',
        'company_address',
        'authorized_representative',
    ];

    const isInvalid = requiredFields.some((key) => {
        const value = company_form[key];
        return typeof value === 'string' ? value.trim() === '' : !value;
    });

    if (isInvalid) {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete',
            detail: 'Please complete all required fields before proceeding.',
            life: 3000,
        });
        return false;
    }

    return true;
};

const nextStep = async () => {
    if (currentStep.value < steps.value.length) {
        // const isValid = validateForm();
        // if (!isValid) return;
        if (currentStep.value == 1) {
            const isSaved = await saveCompanyApplication();
            if (isSaved) {
                currentStep.value++;
            }
        } else if (currentStep.value == 2) {
            const isSaved = await submitChainsawForm();
            if (isSaved) {
                currentStep.value++;
            }
        } else if (currentStep.value == 3) {
            const isSaved = await submitORPayment();
            if (isSaved) {
                currentStep.value++;
            }
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const submitForm = () => {
    form.post('/chainsaw-permit', {
        onSuccess: () => {
            alert('Application submitted successfully!');
        },
    });
};

//CHAINSAW INFORMATION
const chainsawss = ref([
    {
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
    },
]);

// Copy all fields from the first chainsaw to the selected index
const copyAllFields = (index) => {
    if (chainsaws.value[index].copyAll && index > 0) {
        const first = chainsaws.value[0];
        chainsaws.value[index] = {
            brand: first.brand,
            model: first.model,
            quantity: first.quantity,
            supplierName: first.supplierName,
            supplierAddress: first.supplierAddress,
            type: first.type,
            permitNumber: first.permitNumber,
            permitValidity: first.permitValidity, // you may clone this with new Date()
            classification: first.classification,
            price: first.price,
            dateEndorsed: first.dateEndorsed, // you may clone this with new Date()
            purpose: first.purpose,
            otherDetails: first.otherDetails,
            copyAll: true,
            letterRequest: null,
        };
    }
};

const handleFileUpload = (event, index) => {
    chainsaws.value[index].letterRequest = event.target.files[0];
};
const addChainsaw = () => {
    chainsaws.push(JSON.parse(JSON.stringify(chainsaw_form)));
};

// const addChainsaw = () => {
//     chainsaws.value.push({
//         brand: '',
//         model: '',
//         quantity: 1,
//         supplierName: '',
//         supplierAddress: '',
//         type: '',
//         permitNumber: '',
//         permitValidity: null,
//         classification: '',
//         price: '',
//         dateEndorsed: null,
//         purpose: '',
//         otherDetails: '',
//         letterRequest: null,
//         copyAll: false,
//     });
// };

const removeChainsaw = (index) => {
    if (chainsaws.length > 1) chainsaws.splice(index, 1);
};
//END OF CHAINSAW INFORMATION

const purpose = ref({
    purpose: '',
    purposeFiles: {
        mayorDTI: null,
        affidavit: null,
        otherDocs: null,
    },
});

// const handlePurposeFileUpload = (event, field) => {
//     chainsaw_form[field] = event.target.files[0];
// };

const handlePurposeFileUpload = (event, fieldName, index) => {
    const file = event.target.files[0];
    if (file) {
        chainsaws[index][fieldName] = file;
    }
};

const handleORFileUpload = (event, field) => {
    payment_form[field] = event.target.files[0];
};

const showError = () => {
    toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please complete all required fields before proceeding.',
        life: 3000,
    });
};

// Purpose options
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

const isStepValid = (stepId) => {
    // Add specific validation logic for each step
    if (stepId === 3) {
        // Example: validate fields in step 1
        // return (
        //     company_form.date_applied &&
        //     company_form.company_name &&
        //     company_form.authorized_representative &&
        //     company_form.request_letter &&
        //     company_form.soc_certificate &&
        //     company_form.c_region &&
        //     company_form.c_province &&
        //     company_form.c_city_mun &&
        //     company_form.c_barangay &&
        //     company_form.company_address &&
        //     company_form.place_of_operation_address
        // );
        // return true if filled
    } else if (stepId === 2) {
        // Step 2 validations (if needed)
        // return form.chainsawBrand && form.serialNumber;
    }

    return true; // default true if no validation
};

const handleStepClick = (targetStep) => {
    if (targetStep <= currentStep.value) {
        currentStep.value = targetStep;
    } else {
        if (isStepValid(currentStep.value)) {
            currentStep.value = targetStep;
        } else {
            showError();
        }
    }
};

//INSERT COMPANY FORM DATA
const saveCompanyApplication = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;

    const formData = new FormData();
    formData.append('request_letter', company_form.request_letter);
    formData.append('soc_certificate', company_form.soc_certificate);

    try {
        const response = await insertFormData('http://127.0.0.1:8000/api/chainsaw/company_apply', {
            ...company_form,
            ...formData,
            encoded_by: 1,
        });

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Company application submitted successfully.',
            life: 3000,
        });
        return true;
    } catch (error) {
        console.error('Failed to save application:', error);
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'There was an error saving the application.',
            life: 3000,
        });
        return false;
    } finally {
        isLoading.value = false;
        isloadingSpinner.value = false;
    }
};

const submitORPayment = async () => {
    isLoading.value = true;
    isloadingSpinner.value = true;
    const formData = new FormData();
    formData.append('official_receipt', payment_form.official_receipt);
    formData.append('permit_fee', payment_form.permit_fee);
    formData.append('application_no', chainsaw_form.application_no); // dynamic app no
    formData.append('or_copy', payment_form.or_copy); // file

    try {
        const response = await axios.post('http://127.0.0.1:8000/api/chainsaw/insert_payment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Payment Details submitted successfully',
            life: 3000,
        });

        return true;
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
        isloadingSpinner.value = false;
    }
};

const submitChainsawForm = async () => {
    isloadingSpinner.value = true;

    try {
        for (let i = 0; i < chainsaws.length; i++) {
            const chainsaw = chainsaws[i];
            const formData = new FormData();

            // Append basic chainsaw fields
            for (const key in chainsaw) {
                if (
                    chainsaw[key] !== null &&
                    chainsaw[key] !== undefined &&
                    !(chainsaw[key] instanceof File) // Exclude files for now
                ) {
                    formData.append(key, chainsaw[key]);
                }
            }

            // Append application number (global/shared)
            if (chainsaw_form.application_no) {
                formData.append('application_no', chainsaw_form.application_no);
            }

            // Append each file with standard field name (backend expects this)
            if (chainsaw.mayorDTI) formData.append('mayorDTI', chainsaw.mayorDTI);
            if (chainsaw.affidavit) formData.append('affidavit', chainsaw.affidavit);
            if (chainsaw.otherDocs) formData.append('otherDocs', chainsaw.otherDocs);
            if (chainsaw.permit) formData.append('permit', chainsaw.permit);

            await axios.post('http://127.0.0.1:8000/api/chainsaw/insertChainsawInfo', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
        }
    } catch (error) {
        console.error('Upload failed:', error);
    } finally {
        isloadingSpinner.value = false;
    }
}

onMounted(() => {
    getApplicationNumber(company_form, chainsaw_form);
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
            <Chainsaw_applicationField :form="company_form" :application_no="form.application_no"
                :insertFormData="insertFormData" />
            <Chainsaw_companyField :form="company_form" />
            <Chainsaw_operationField :form="company_form" />
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <!-- Alert Info -->
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
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

                    <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div :hidden="false">
                            <FloatLabel>
                                <InputText v-model="chainsaw_form.application_no" class="w-full" />
                                <label>Application No.</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.brand" class="w-full" />
                                <label>Brand</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <Select v-model="chainsaw.model" :options="['MS 382', 'MS 230', 'MS 162']"
                                    class="w-full" />
                                <label>Model</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.quantity" type="number" class="w-full" />
                                <label>Quantity</label>
                            </FloatLabel>
                        </div>

                        <div class="md:col-span-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw.supplier_name" class="w-full" />
                                <label>Supplier Name</label>
                            </FloatLabel>
                        </div>
                        <div class="md:col-span-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw.supplier_address" class="w-full" />
                                <label>Supplier Address</label>
                            </FloatLabel>
                        </div>

                        <div class="space-y-4 md:col-span-3">
                            <FloatLabel>
                                <Select v-model="chainsaw.purpose" :options="purposeOptions" class="w-full" />
                                <label>Purpose of Purchase</label>
                            </FloatLabel>

                            <!-- Conditional Uploads -->
                            <div
                                v-if="chainsaw.purpose === 'For selling / re-selling' || chainsaw.purpose === 'Forestry/landscaping service provider'">
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
                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw.permit_chainsaw_no" class="w-full" />
                                    <label>Permit to Sell / Re-Sell Chainsaw No.</label>
                                </FloatLabel>
                            </div>

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
            </Fieldset>
        </div>

        <div v-if="currentStep === 3" class="space-y-6">
            <Fieldset legend="Payment of Application Fee">
                <!-- Alert Info -->
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw. </span>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div :hidden="false">
                        <FloatLabel>
                            <InputText v-model="chainsaw_form.application_no" class="w-full" />
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
                        <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official Receipt</label>
                        <input type="file" accept=".jpg,.jpeg,.pdf" @change="(e) => handleORFileUpload(e, 'or_copy')"
                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 4" class="space-y-6">
            <Fieldset legend="Applicant Details" :toggleable="true">
                <!-- Applicant Info (non-file fields) -->
                <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4 text-sm text-gray-800 mt-6">
                    <div class="flex">
                        <span class="w-48 font-semibold">Application No:</span>
                        <Tag value="DENR-IV-A-2025-0009" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Date Applied:</span>
                        <span>04/08/2025</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Type of Transaction:</span>
                        <span>Environmental Compliance Certificate</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Company Name:</span>
                        <span>ABC Corporation</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Authorized Representative:</span>
                        <span>Juan Dela Cruz</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Region:</span>
                        <span>REGION IV-A (CALABARZON)</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Province:</span>
                        <span>Batangas</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Municipality:</span>
                        <span>Lipa City</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Barangay:</span>
                        <span>Barangay 1</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Complete Address:</span>
                        <span>1234 Main St., Purok 2, Lipa City, Batangas</span>
                    </div>
                    <div class="flex md:col-span-2">
                        <span class="w-48 font-semibold">Place of Operation Address:</span>
                        <span>1234 Main St., Purok 2, Lipa City, Batangas</span>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Chainsaw Information" :toggleable="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4 text-sm text-gray-800 mt-6">
                    <div class="flex">
                        <span class="w-48 font-semibold">Chainsaw No:</span>
                        <Tag value="PS-123456" severity="success" class="text-center" /><br />
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Permit Validity:</span>
                        <span>2025-08-04</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Brand:</span>
                        <span>Stihl</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Model:</span>
                        <span>MS 261</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Quantity:</span>
                        <span>5</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Name:</span>
                        <span>XYZ Supplies Co.</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Supplier Address:</span>
                        <span>123 Supplier St., Calabarzon</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Purpose of Purchase:</span>
                        <span>Business Use</span>
                    </div>
                    <div class="flex">
                        <span class="w-48 font-semibold">Other Details:</span>
                        <span>N/A</span>
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Uploaded Files" :toggleable="true">
                <div class="container">
                    <div class="file-list">
                        <FileCard v-for="(file, index) in files" :key="index" :file="file" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6 text-sm text-gray-800 mt-6">

                    <!-- Applicant Uploaded Files -->
                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Application Letter:</span>
                        <a href="application_letter.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">application_letter.pdf</span>
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Business Registration:</span>
                        <a href="business_cert.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">business_cert.pdf</span>
                        </a>
                    </div>

                    <!-- Chainsaw Uploaded Files -->
                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Permit File:</span>
                        <a href="permit_document.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">permit_document.pdf</span>
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Mayor's Permit & DTE Registration:</span>
                        <a href="dti.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">dti.pdf</span>
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Permit:</span>
                        <a href="dti.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">dti.pdf</span>
                        </a>
                    </div>

                    <!-- Official Receipt Files -->
                    <div class="flex items-center space-x-4">
                        <span class="w-48 font-semibold">Upload Scanned Copy of Official Receipt:</span>
                        <a href="official_receipt.pdf" target="_blank" class="file-preview">
                            <img src="/icons/pdf-icon.png" alt="PDF Icon" class="file-icon" />
                            <span class="file-name">official_receipt.pdf</span>
                        </a>
                    </div>

                </div>
            </Fieldset>




        </div>
        <!-- <Button class="ml-auto" @click="sec">Next</Button> -->

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep <= 3" class="ml-auto" @click="nextStep">Next</Button>
            <Button v-if="currentStep === 4" class="ml-auto" @click="submitForm" :disabled="form.processing"> Submit
                Application </Button>
        </div>
    </div>
</template>

<style>
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