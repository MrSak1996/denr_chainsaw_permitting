<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useForm } from '@inertiajs/vue3';

// UI & Icons
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import Fieldset from 'primevue/fieldset';
import { LoaderCircle, ShieldAlert } from 'lucide-vue-next';
import Chainsaw_operationField from './chainsaw_operationField.vue';

// Composables
import { useAppForm } from '@/composables/useAppForm';
import { useFormHandler } from '@/composables/useFormHandler';
import { useApi } from '@/composables/useApi';

// State
const toast = useToast();
const { individual_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getProvinceCode, getApplicationNumber, prov_name } = useApi();
const isLoading = ref(false);
const currentStep = ref(1);

// ─────────────────────────────────────────────────────────────
// STEPPER
// ─────────────────────────────────────────────────────────────
const steps = ref([
    { label: 'Applicant Details', id: 1 },
    { label: 'Chainsaw Information', id: 2 },
    { label: 'Payment of Application Fee', id: 3 },
    { label: 'Submit and Review', id: 4 },
]);

const validateForm = () => {
    const requiredFields = [
        'date_applied',
        'application_type',
        'type_of_transaction',
        'geo_code',
        'last_name',
        'first_name',
        'sex',
    ];

    const isInvalid = requiredFields.some((key) => !individual_form[key]);
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
        const isValid = validateForm();
        if (!isValid) return;

        const isSaved = await saveCompanyApplication();
        if (isSaved) {
            currentStep.value++;
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// ─────────────────────────────────────────────────────────────
// FORM SUBMISSION
// ─────────────────────────────────────────────────────────────
const saveCompanyApplication = async () => {
    isLoading.value = true;

    try {
        const response = await insertFormData('http://127.0.0.1:8000/api/chainsaw/apply', {
            ...individual_form,
            encoded_by: 1,
        });

        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Company application submitted successfully.',
            life: 3000,
        });

        console.log('Saved with ID:', response.id);
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


const submitForm = () => {
    form.post('/chainsaw-permit', {
        onSuccess: () => {
            alert('Application submitted successfully!');
        },
    });
};

// ─────────────────────────────────────────────────────────────
// CHAINSaw Section
// ─────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────
// GEO LOCATION HANDLERS
// ─────────────────────────────────────────────────────────────
const region_opts = ref([{ id: 1, name: 'CALABARZON' }]);
const city_mun_opts = ref<{ id: any; name: any; code: any }[]>([]);
const barangay_opts = ref<{ id: any; name: any }[]>([]);

watch(() => individual_form.i_city_mun, (newCityMun) => {
    const selected = city_mun_opts.value.find((item) => item.id === newCityMun);
    individual_form.geo_code = selected?.code ?? '';
});

watch(() => individual_form.i_province, async (newProvince) => {
    if (!newProvince) return city_mun_opts.value = [];

    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/provinces/${newProvince}/cities`);
        city_mun_opts.value = Array.isArray(data)
            ? data.map(({ mun_code, mun_name, geo_code }) => ({
                id: mun_code,
                name: mun_name,
                code: geo_code,
            }))
            : [];
        individual_form.i_city_mun = '';
    } catch (err) {
        console.error('Error fetching cities:', err);
        city_mun_opts.value = [];
    }
});

watch(() => individual_form.i_city_mun, async (newCityMun) => {
    if (!newCityMun) return barangay_opts.value = [];

    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/barangays`, {
            params: {
                reg_code: individual_form.i_region,
                prov_code: individual_form.i_province,
                mun_code: newCityMun,
            },
        });

        barangay_opts.value = Array.isArray(data)
            ? data.map(({ bgy_code, bgy_name }) => ({ id: bgy_code, name: bgy_name }))
            : [];
        individual_form.i_barangay = '';
    } catch (err) {
        console.error('Error fetching barangays:', err);
        barangay_opts.value = [];
    }
});

// ─────────────────────────────────────────────────────────────
// ON MOUNT
// ─────────────────────────────────────────────────────────────
onMounted(() => {
    getProvinceCode();
    getApplicationNumber(individual_form);
});
</script>


<template>
    <div class="mt-10 space-y-6">
        <Toast />
        <!-- Stepper -->
        <div class="mb-6 flex items-center justify-between">
            <div v-for="step in steps" :key="step.id" class="flex-1 cursor-pointer text-center" @click="nextStep">
                <div :class="[
                    'mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                    currentStep === step.id ? 'bg-green-600' : step.id < currentStep ? 'bg-blue-400' : 'cursor-not-allowed bg-gray-300',
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
                    <span>Please complete all fields to proceed with your application for a Permit to Purchase
                        Chainsaw.</span>
                </div>

                <!-- Application Number -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="application_no" v-model="individual_form.application_no"
                                class="w-full font-bold" :disabled="true" />
                            <label for="application_no">Application No.</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.application_no" />
                    </div>
                </div>

                <!-- Date & Transaction -->
                <div class="mb-6 grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="date_applied" v-model="individual_form.date_applied" type="date"
                                class="w-full" />
                            <label for="date_applied">Date Applied</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.date_applied" />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select id="type_of_transaction" v-model="individual_form.type_of_transaction"
                                :options="['G2C', 'G2B', 'G2G']" class="w-full" />
                            <label for="type_of_transaction">Type of Transaction</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.type_of_transaction" />
                    </div>
                </div>

                <!-- Name Fields -->
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <FloatLabel>
                            <InputText id="surname" v-model="individual_form.last_name" class="w-full" />
                            <label for="surname">Last Name</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.last_name" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="first_name" v-model="individual_form.first_name" class="w-full" />
                            <label for="first_name">First Name</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.first_name" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="middlename" v-model="individual_form.middle_name" class="w-full" />
                            <label for="middlename">Middle Name</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.middle_name" />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select id="sex" v-model="individual_form.sex"
                                :options="['Male', 'Female', 'Prefer not to say']" class="w-full" />
                            <label for="sex">Sex</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.sex" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="gov_id_type" v-model="individual_form.gov_id_type" class="w-full" />
                            <label for="gov_id_type">Government ID Type</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.gov_id_type" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="gov_id_number" v-model="individual_form.gov_id_number" class="w-full" />
                            <label for="gov_id_number">ID Number</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.gov_id_number" />
                    </div>
                </div>
            </Fieldset>

            <!-- Contact Information -->
            <Fieldset legend="Contact Information">
                <div class="grid gap-6 md:grid-cols-4">
                    <div>
                        <FloatLabel>
                            <InputText id="mobile" v-model="individual_form.mobile_no" class="w-full" />
                            <label for="mobile">Mobile Number</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.mobile_no" />
                    </div>
                    <div>
                        <FloatLabel>
                            <InputText id="telephone" v-model="individual_form.telephone_no" class="w-full" />
                            <label for="telephone">Telephone Number</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.telephone_no" />
                    </div>
                    <div class="md:col-span-2">
                        <FloatLabel>
                            <InputText id="email_address" v-model="individual_form.email_address" class="w-full" />
                            <label for="email_address">Email Address</label>
                        </FloatLabel>
                        <InputError :message="individual_form.errors.email_address" />
                    </div>
                </div>
            </Fieldset>

            <!-- Complete Address -->
            <Fieldset legend="Complete Address">
                <div class="grid gap-6 md:grid-cols-4">
                    <div>
                        <FloatLabel>
                            <InputText v-model="individual_form.i_region" class="w-full" :disabled="true" />
                        </FloatLabel>
                        <InputError :message="individual_form.errors.i_region" />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select filter v-model="individual_form.i_province" optionValue="id" :options="prov_name"
                                optionLabel="name" placeholder="Province" class="w-full" />
                        </FloatLabel>
                        <InputError :message="individual_form.errors.i_province" />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select filter v-model="individual_form.i_city_mun" :options="city_mun_opts"
                                optionValue="id" optionLabel="name" placeholder="Municipality" class="w-full" />
                        </FloatLabel>
                        <InputError :message="individual_form.errors.i_city_mun" />
                    </div>
                    <div>
                        <FloatLabel>
                            <Select filter v-model="individual_form.i_barangay" :options="barangay_opts"
                                optionValue="id" optionLabel="name" placeholder="Barangay" class="w-full" />
                        </FloatLabel>
                        <InputError :message="individual_form.errors.i_barangay" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Complete
                            Address</label>
                        <Textarea id="address" v-model="individual_form.i_complete_address" rows="6"
                            placeholder="Complete Address (Street, Purok, etc.)"
                            class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                        <InputError :message="individual_form.errors.i_complete_address" />
                    </div>
                </div>
            </Fieldset>

            <!-- <Chainsaw_operationField :form="individual_form" /> -->
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
            <!-- <Button v-if="currentStep < 3" class="ml-auto" @click="nextStep">Next</Button> -->
            <Button v-if="currentStep < 3" class="ml-auto flex items-center justify-center gap-2" @click="nextStep"
                :disabled="isLoading">
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                <span>Next</span>
            </Button>

            <Button v-if="currentStep === 4" class="ml-auto" @click="submitForm" :disabled="form.processing"> Submit
                Application </Button>
        </div>
    </div>
</template>
