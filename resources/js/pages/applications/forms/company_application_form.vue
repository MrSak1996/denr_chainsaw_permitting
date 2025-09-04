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
import LoadingSpinner from '../../LoadingSpinner.vue';
import Chainsaw_applicationField from './chainsaw_applicationField.vue';
import Chainsaw_companyField from './chainsaw_companyField.vue';
import Chainsaw_operationField from './chainsaw_operationField.vue';
import FileCard from './file_card.vue';
import { Button } from '@/components/ui/button';

// Form Data
const { company_form, chainsaw_form, payment_form } = useAppForm();
const { insertFormData } = useFormHandler();
const { getApplicationNumber } = useApi();

// Refs & Reactives
const chainsaws = reactive<ChainsawForm[]>([{ ...JSON.parse(JSON.stringify(chainsaw_form)) }]);
const toast = useToast();
const page = usePage();
const userId = page.props.auth?.user?.id;
const files = ref([]);
const isLoading = ref(false);
const isloadingSpinner = ref(false);
const applicationData = ref([]);
const currentStep = ref(1);
const errorMessage = ref('');
const selectedFile = ref(null);
const showModal = ref(false);

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

// Utility
const getApplicationIdFromUrl = () => {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('application_id') || urlParams.get('id');
};

const getEmbedUrl = (url) => {
  const match = url.match(/[-\w]{25,}/);
  const fileId = match ? match[0] : null;
  return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

// Step Navigation
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

// File Preview Modal
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

const handleFileUpload = (event, index) => {
  chainsaws[index].letterRequest = event.target.files[0];
};

const handlePurposeFileUpload = (event, fieldName, index) => {
  chainsaws[index][fieldName] = event.target.files[0];
};

const handleORFileUpload = (event, field) => {
  payment_form[field] = event.target.files[0];
};

// Form Submissions
const saveCompanyApplication = async () => {
  isLoading.value = true;
  isloadingSpinner.value = true;
  const formData = new FormData();
  formData.append('request_letter', company_form.request_letter);
  formData.append('soc_certificate', company_form.soc_certificate);

  try {
    const response = await insertFormData('http://10.201.12.186:8000/api/chainsaw/company_apply', {
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

const submitChainsawForm = async () => {
  isloadingSpinner.value = true;
  const applicationId = getApplicationIdFromUrl();

  try {
    for (const chainsaw of chainsaws) {
      const formData = new FormData();

      Object.entries(chainsaw).forEach(([key, value]) => {
        if (value !== null && value !== undefined && !(value instanceof File)) {
          formData.append(key, value);
        }
      });

      formData.append('application_no', chainsaw_form.application_no);
      ['mayorDTI', 'affidavit', 'otherDocs', 'permit'].forEach((fileKey) => {
        if (chainsaw[fileKey]) formData.append(fileKey, chainsaw[fileKey]);
      });

      await axios.post('http://10.201.12.186:8000/api/chainsaw/insertChainsawInfo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }
    router.get(route('applications.index', { application_id: applicationId }));
    return true;
  } catch (error) {
    console.error('Upload failed:', error);
    return false;
  } finally {
    isloadingSpinner.value = false;
  }
};

const submitORPayment = async () => {
  isLoading.value = true;
  isloadingSpinner.value = true;
  const applicationId = getApplicationIdFromUrl();
  const formData = new FormData();

  formData.append('official_receipt', payment_form.official_receipt);
  formData.append('permit_fee', payment_form.permit_fee);
  formData.append('application_no', chainsaw_form.application_no);
  formData.append('or_copy', payment_form.or_copy);

  try {
    await axios.post('http://10.201.12.186:8000/api/chainsaw/insert_payment', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment Details submitted successfully', life: 3000 });
    router.get(route('applications.index', { application_id: applicationId }));
    return true;
  } catch (error) {
    console.error('Failed to save payment details:', error);
    toast.add({ severity: 'error', summary: 'Failed', detail: 'There was an error saving the application.', life: 3000 });
    return false;
  } finally {
    isLoading.value = false;
    isloadingSpinner.value = false;
  }
};

// API Calls
const getApplicationDetails = async () => {
  const applicationId = getApplicationIdFromUrl();
  if (!applicationId) {
    errorMessage.value = 'No application ID found in the query.';
    isLoading.value = false;
    return;
  }

  try {
    const response = await axios.get(`http://10.201.12.186:8000/api/getApplicationDetails/${applicationId}`);
    applicationData.value = response.data.data || [];
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
    const response = await axios.get(`http://10.201.12.186:8000/api/getApplicantFile/${applicationId}`);
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

onMounted(() => {
  getApplicationNumber(company_form, chainsaw_form);
  getApplicationDetails();
  getApplicantFile();
});
</script>
<template>
    <div class="mt-10 space-y-6">
        <Toast />
        <LoadingSpinner :loading="isloadingSpinner" />
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
            <Chainsaw_applicationField :form="company_form" :insertFormData="insertFormData" :app_data="applicationData" />
            <Chainsaw_companyField :form="company_form" :app_data="applicationData" />
            <Chainsaw_operationField :form="company_form" />
        </div>

        <div v-if="currentStep === 2" class="space-y-6">
            <Fieldset legend="Chainsaw Information">
                <div class="relative">
                    <div class="ribbon">DRAFT</div>

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
                                    v-if="
                                        chainsaw.purpose === 'For selling / re-selling' ||
                                        chainsaw.purpose === 'Forestry/landscaping service provider'
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

                                <div v-if="chainsaw.purpose === 'Other Legal Purpose'">
                                    <label class="text-sm font-medium text-gray-700">Upload Notarized Affidavit</label>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.pdf,.docx,.png"
                                        class="mt-1 w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50"
                                        @change="(e) => (chainsaws[index].affidavit = e.target.files[0])"
                                    />
                                </div>

                                <div v-if="chainsaw.purpose === 'Other Supporting Documents'">
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
        <!-- <Button class="ml-auto" @click="sec">Next</Button> -->

        <div class="flex justify-between pt-6">
            <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Back</Button>
            <Button v-if="currentStep <= 3" class="ml-auto" @click="nextStep">Save as Draft</Button>
            <Button v-if="currentStep === 4" class="ml-auto" @click="submitForm"> Submit Application </Button>
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
    --f: 0.5em; /* control the folded part */
    z-index: 10; /* ensure it's on top */
    font-size: 16px; /* or adjust as needed */
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
    background-color: #bd1550; /* the main color  */
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
