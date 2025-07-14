<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { ShieldAlert } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';

import Chainsaw_operationField from './chainsaw_operationField.vue';

import { ref } from 'vue';


const region_opts = ref([{ id: 1, name: 'CALABARZON' }]);
const form = useForm({
    official_receipt: null,
    application_no: 'DENR-IV-A-2025-07-07-0001',
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
    date_applied: '07-01-2025',
    birthdate: '07-01-2025',
});

const currentStep = ref(1);

const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Chainsaw Information', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const nextStep = () => {
    if (currentStep.value < steps.value.length) currentStep.value++;
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
</script>

<template>
    <div class="mt-10 space-y-6">
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between">
            <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center"
                @click="currentStep = step.id">
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
            <Fieldset legend="Chainsaw Application">
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw. </span>
                </div>
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="application_no" v-model="form.application_no" class="w-full font-bold"
                                :disabled="true" />
                            <label for="application_no">Application No.</label>
                        </FloatLabel>
                        <InputError :message="form.errors.application_no" />
                    </div>

                </div>
                <div class="mb-6 grid gap-6 md:grid-cols-3">

                    <!-- Date Applied -->
                    <div>
                        <FloatLabel>
                            <InputText id="date_applied" v-model="form.date_applied" type="date" class="w-full" />
                            <label for="date_applied">Date Applied</label>
                        </FloatLabel>
                        <InputError :message="form.errors.date_applied" />
                    </div>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Application No -->
                    <div>
                        <FloatLabel>
                            <InputText id="surname" v-model="form.surname" class="w-full" />
                            <label for="surname">Last Name</label>
                        </FloatLabel>
                        <InputError :message="form.errors.surname" />
                    </div>

                    <!-- Surname -->
                    <div>
                        <FloatLabel>
                            <InputText id="first_name" v-model="form.first_name" class="w-full" />
                            <label for="first_name">First Name</label>
                        </FloatLabel>
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <!-- Given Name -->
                    <div>
                        <FloatLabel>
                            <InputText id="middlename" v-model="form.middlename" class="w-full" />
                            <label for="middlename">Middle Name</label>
                        </FloatLabel>
                        <InputError :message="form.errors.middlename" />
                    </div>


                    <!-- Sex -->
                    <div>
                        <FloatLabel>
                            <Select id="sex" v-model="form.sex" :options="['Male', 'Female', 'Prefer not to say']"
                                class="w-full" />
                            <label for="sex">Sex</label>
                        </FloatLabel>
                        <InputError :message="form.errors.sex" />
                    </div>

                    <!-- Gov ID Type -->
                    <div>
                        <FloatLabel>
                            <InputText id="gov_id_type" v-model="form.gov_id_type" class="w-full" />
                            <label for="gov_id_type">Government ID Type</label>
                        </FloatLabel>
                        <InputError :message="form.errors.gov_id_type" />
                    </div>

                    <!-- Gov ID Number -->
                    <div>
                        <FloatLabel>
                            <InputText id="gov_id_number" v-model="form.gov_id_number" class="w-full" />
                            <label for="gov_id_number">ID Number</label>
                        </FloatLabel>
                        <InputError :message="form.errors.gov_id_number" />
                    </div>


                </div>
            </Fieldset>

            <Fieldset legend="Contact Information">


                <div class="grid gap-6 md:grid-cols-4">
                    <div>
                        <FloatLabel>
                            <InputText id="mobile" v-model="form.mobile" class="w-full" />
                            <label for="mobile">Mobile Number</label>
                        </FloatLabel>
                        <InputError :message="form.errors.mobile" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="municipality" v-model="form.municipality" class="w-full" />
                            <label for="municipality">Telephone Number</label>
                        </FloatLabel>
                        <InputError :message="form.errors.municipality" />
                    </div>
                    <div class="md:col-span-2">
                        <FloatLabel>
                            <InputText id="email_address" v-model="form.email_address" class="w-full" />
                            <label for="email_address">Email Address</label>
                        </FloatLabel>
                        <InputError :message="form.errors.email_address" />
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Complete Address">
           

            <div class="grid gap-6 md:grid-cols-4">
                <!-- Region -->
                <div>
                    <FloatLabel>
                        <Select filter :options="region_opts" optionValue="id" optionLabel="name" placeholder="Region"
                            class="w-full" />
                    </FloatLabel>
                    <InputError :message="form.errors.region" />
                </div>

                <!-- Province -->
                <div>
                    <FloatLabel>
                        <Select filter :options="region_opts" optionValue="id" optionLabel="name" placeholder="Province"
                            class="w-full" />
                    </FloatLabel>
                    <InputError :message="form.errors.region" />
                </div>

                <!-- Municipality -->
                <div>
                    <FloatLabel>
                        <Select filter :options="region_opts" optionValue="id" optionLabel="name"
                            placeholder="Municipality" class="w-full" />
                    </FloatLabel>
                    <InputError :message="form.errors.region" />
                </div>

                <!-- Barangay -->
                <div>
                    <FloatLabel>
                        <Select filter :options="region_opts" optionValue="id" optionLabel="name" placeholder="Barangay"
                            class="w-full" />
                    </FloatLabel>
                    <InputError :message="form.errors.region" />
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Complete
                        Address</label>
                    <Textarea id="address" v-model="form.address" rows="6"
                        placeholder="Complete Address (Street, Purok, etc.)"
                        class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                    <InputError :message="form.errors.address" />
                </div>
            </div>
        </Fieldset>

            <Chainsaw_operationField :form="form" :region_opts="region_opts" />
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                    <span> Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw. </span>
                </div>
                <div v-for="(chainsaw, index) in chainsaws" :key="index"
                    class="relative mb-6 rounded-lg border border-gray-200 bg-gray-50 p-8 shadow-sm">
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

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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

                        <div>
                            <FloatLabel>
                                <Select v-model="chainsaw.type" :options="['Gasoline', 'Electric', 'Battery Operated']"
                                    class="w-full" />
                                <label>Type of Transaction</label>
                            </FloatLabel>
                        </div>

                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.permitNumber" class="w-full" />
                                <label>Permit to Sell / Re-Sell Chainsaw No.</label>
                            </FloatLabel>
                        </div>

                        <div>
                            <FloatLabel>
                                <DatePicker v-model="chainsaw.permitValidity" class="w-full" />
                                <label>Permit Validity</label>
                            </FloatLabel>
                        </div>

                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.classification" class="w-full" />
                                <label>Classification</label>
                            </FloatLabel>
                        </div>

                        <div>
                            <FloatLabel>
                                <InputText v-model="chainsaw.price" type="number" class="w-full" />
                                <label>Purchase Price</label>
                            </FloatLabel>
                        </div>

                        <div>
                            <FloatLabel>
                                <DatePicker v-model="chainsaw.dateEndorsed" class="w-full" />
                                <label>Date Endorsed by CENRO</label>
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
                                <input type="file" accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'mayorDTI')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700" />
                            </div>

                            <div v-if="chainsaw.purpose === 'Other Legal Purpose'">
                                <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                <input type="file" accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'affidavit')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700" />
                            </div>

                            <div v-if="chainsaw.purpose === 'Other Supporting Documents'">
                                <label class="text-sm font-medium text-gray-700">Upload Supporting Document</label>
                                <input type="file" accept=".jpg,.jpeg,.pdf"
                                    @change="(e) => handlePurposeFileUpload(e, 'otherDocs')"
                                    class="mt-1 w-full rounded border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700" />
                            </div>
                        </div>

                        <div class="md:col-span-3">
                            <FloatLabel>
                                <InputText v-model="chainsaw.otherDetails" class="w-full" />
                                <label>Other Details</label>
                            </FloatLabel>
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-sm font-medium text-gray-700">Upload Permit (JPG/PDF)</label>
                            <input type="file" accept=".jpg,.jpeg,.pdf"
                                @change="(event) => handleFileUpload(event, index)"
                                class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
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
                        <input type="file" accept=".jpg,.jpeg,.pdf" @change="(event) => handleFileUpload(event, index)"
                            class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                    </div>
                </div>
            </Fieldset>
        </div>

        <div v-if="currentStep === 4" class="space-y-6">
            <Fieldset legend="Uploading of Requirements">
                <!-- Upload Requirements -->
                <div class="grid gap-6 md:grid-cols-1">
                    <label class="text-sm font-semibold text-gray-800">Upload Required Documents <span
                            class="text-gray-500">(Accepted: JPG, PDF)</span></label>
                    <!-- Letter of Request -->
                    <div>
                        <label for="letterRequest" class="block text-sm font-medium text-gray-700">1. Letter of
                            Request</label>
                        <input id="letterRequest" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="handleSpecificFileUpload($event, 'letterRequest')"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50" />
                    </div>

                    <!-- 1. Payment of Fees and OR -->
                    <div>
                        <label for="paymentFees" class="block text-sm font-medium text-gray-700">2. Payment of Fees
                            (Permit, Application, Oath) and O.R of Cash Bond</label>
                        <input id="paymentFees" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="handleSpecificFileUpload($event, 'paymentFees')"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50" />
                    </div>

                    <!-- 2. Permit to Sell Chainsaw -->
                    <div>
                        <label for="permitToSell" class="block text-sm font-medium text-gray-700">3. Permit to Sell
                            Chainsaw</label>
                        <input id="permitToSell" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="handleSpecificFileUpload($event, 'permitToSell')"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50" />
                    </div>

                    <!-- 3. Authorization Letter -->
                    <div>
                        <label for="authLetter" class="block text-sm font-medium text-gray-700">4. Authorization Letter
                            from Owner (if applicant is not the owner)</label>
                        <input id="authLetter" type="file" accept=".jpg,.jpeg,.pdf"
                            @change="handleSpecificFileUpload($event, 'authLetter')"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 hover:bg-gray-50" />
                    </div>
                </div>
            </Fieldset>
        </div>

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep < 3" class="ml-auto" @click="nextStep">Next</Button>
            <Button v-if="currentStep === 4" class="ml-auto" @click="submitForm" :disabled="form.processing"> Submit
                Application </Button>
        </div>
    </div>
</template>
