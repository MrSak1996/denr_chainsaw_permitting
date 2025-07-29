<script setup lang="ts">
// import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useApi } from '@/composables/useApi';
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useForm } from '@inertiajs/vue3';
import { ShieldAlert } from 'lucide-vue-next';
import { useToast } from 'primevue/usetoast';

import Fieldset from 'primevue/fieldset';

import Chainsaw_applicationField from './chainsaw_applicationField.vue';
import Chainsaw_companyField from './chainsaw_companyField.vue';
import Chainsaw_operationField from './chainsaw_operationField.vue';

import { onMounted, ref } from 'vue';
const { company_form } = useAppForm();
const { getApplicationNumber } = useApi();
const { insertFormData } = useFormHandler();

const toast = useToast();
const form = useForm({
    official_receipt: null,
    application_no: 'DENR-IV-A-2025-07-07-0001',
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
            const isSaved = await saveChainsawInformation();
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
const chainsaws = ref([
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

const removeChainsaw = (index) => {
    if (chainsaws.value.length > 1) chainsaws.value.splice(index, 1);
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

const handlePurposeFileUpload = (event, field) => {
    purpose.value.purposeFiles[field] = event.target.files[0];
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
        return (
            company_form.date_applied &&
            company_form.company_name &&
            company_form.authorized_representative &&
            company_form.request_letter &&
            company_form.soc_certificate &&
            company_form.c_region &&
            company_form.c_province &&
            company_form.c_city_mun &&
            company_form.c_barangay &&
            company_form.company_address &&
            company_form.place_of_operation_address
        ); // return true if filled
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
    }
};

const saveChainsawInformation = async () => {
    isLoading.value = true;
    const formData = new FormData();
    formData.append('mayorDTI', company_form.mayorDTI);
    formData.append('affidavit', company_form.affidavit);
    formData.append('otherDocs', company_form.otherDocs);
    formData.append('permit', company_form.permit);

    try {
        const response = await insertFormData('http://127.0.0.1:8000/api/chainsaw/save_chainsaw_info', {
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
    }

};

onMounted(() => {
    getApplicationNumber(company_form);
});
</script>

<template>
    <div class="mt-10 space-y-6">
        <Toast />
        <!-- Stepper -->
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
            <Chainsaw_applicationField :form="company_form" :application_no="form.application_no" :insertFormData="insertFormData" />
            <Chainsaw_companyField :form="company_form" />
            <Chainsaw_operationField :form="company_form" />
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <!-- Alert Info -->
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase Chainsaw. </span>
                </div>
                <div v-for="(chainsaw, index) in chainsaws" :key="index" class="bg-blue-40 relative mb-6 rounded-lg p-5 shadow">
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
                        <input type="checkbox" v-model="chainsaw.copyAll" @change="copyAllFields(index)" />
                        <label>Same details as first chainsaw</label>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.brand" class="w-full" />
                                <label>Brand</label>
                            </FloatLabel>
                        </div>
                        <div>
                            <FloatLabel>
                                <Select v-model="chainsaw.model" :options="['MS 382', 'MS 230', 'MS 162']" class="w-full" />
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
                                <InputText v-model="chainsaw.supplierName" class="w-full" />
                                <label>Supplier Name</label>
                            </FloatLabel>
                        </div>
                        <div class="md:col-span-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw.supplierAddress" class="w-full" />
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
                                v-if="chainsaw.purpose === 'For selling / re-selling' || chainsaw.purpose === 'Forestry/landscaping service provider'"
                            >
                                <label class="text-sm font-medium text-gray-700">Upload Mayor's Permit & DTI Registration</label>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'mayorDTI')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700"
                                />
                            </div>

                            <div v-if="chainsaw.purpose === 'Other Legal Purpose'">
                                <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'affidavit')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700"
                                />
                            </div>

                            <div v-if="chainsaw.purpose === 'Other Supporting Documents'">
                                <label class="text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'otherDocs')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw.otherDetails" class="w-full" />
                                <label>Other Details</label>
                            </FloatLabel>
                        </div>
                        <div class="grid gap-6 md:col-span-3 md:grid-cols-2">
                            <!-- Permit Number -->
                            <div>
                                <FloatLabel>
                                    <InputText v-model="chainsaw.permitNumber" class="w-full" />
                                    <label>Permit to Sell / Re-Sell Chainsaw No.</label>
                                </FloatLabel>
                            </div>

                            <!-- Permit Validity -->
                            <div>
                                <FloatLabel>
                                    <DatePicker v-model="chainsaw.permitValidity" class="w-full" />
                                    <label>Permit Validity</label>
                                </FloatLabel>
                            </div>
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.pdf"
                                @change="(event) => handleFileUpload(event, 'permit')"
                                class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
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
            </Fieldset>
        </div>

        <div v-if="currentStep === 3" class="space-y-6">
            <Fieldset legend="Payment of Application Fee">
                <!-- Alert Info -->
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase Chainsaw. </span>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <FloatLabel>
                            <InputText class="w-full" />
                            <label>O.R No.</label>
                        </FloatLabel>
                    </div>
                    <div>
                        <FloatLabel>
                            <InputNumber class="w-full" />
                            <label>Permit Fee</label>
                        </FloatLabel>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-sm font-medium text-gray-700">Upload Scanned copy of Official Receipt</label>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.pdf"
                            @change="(event) => handleFileUpload(event, index)"
                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                        />
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 4" class="space-y-6">
            <Fieldset legend="Uploading of Requirements">
                <!-- Upload Requirements -->
                <h1 class="font-xl">MAGKAKARON NG CHECKLIST NG LAHAT NG MGA FOR UPLOADING NA REQUIREMENTS? GREEN IF FILLED , RED IF NOT</h1>
                <div class="grid gap-6 md:grid-cols-1">
                    <label class="text-sm font-semibold text-gray-800"
                        >Upload Required Documents <span class="text-gray-500">(Accepted: JPG, PDF)</span></label
                    >
                    <!-- Letter of Request -->
                    <div>
                        <label for="letterRequest" class="block text-sm font-medium text-gray-700">1. Letter of Request</label>
                        <input
                            id="letterRequest"
                            type="file"
                            accept=".jpg,.jpeg,.pdf"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50"
                        />
                    </div>

                    <!-- 1. Payment of Fees and OR -->
                    <div>
                        <label for="paymentFees" class="block text-sm font-medium text-gray-700"
                            >2. Payment of Fees (Permit, Application, Oath) and O.R of Cash Bond</label
                        >
                        <input
                            id="paymentFees"
                            type="file"
                            accept=".jpg,.jpeg,.pdf"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50"
                        />
                    </div>

                    <!-- 2. Permit to Sell Chainsaw -->
                    <div>
                        <label for="permitToSell" class="block text-sm font-medium text-gray-700">3. Permit to Sell Chainsaw</label>
                        <input
                            id="permitToSell"
                            type="file"
                            accept=".jpg,.jpeg,.pdf"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50"
                        />
                    </div>

                    <!-- 3. Authorization Letter -->
                    <div>
                        <label for="authLetter" class="block text-sm font-medium text-gray-700"
                            >4. Authorization Letter from Owner (if applicant is not the owner)</label
                        >
                        <input
                            id="authLetter"
                            type="file"
                            accept=".jpg,.jpeg,.pdf"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50"
                        />
                    </div>
                </div>
            </Fieldset>
        </div>
        <!-- <Button class="ml-auto" @click="sec">Next</Button> -->

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep < 3" class="ml-auto" @click="nextStep">Next</Button>
            <Button v-if="currentStep === 3" class="ml-auto" @click="submitForm" :disabled="form.processing"> Submit Application </Button>
        </div>
    </div>
</template>
