<script setup lang="ts">
import { router, usePage, Link } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import { SaveAll, Eye, BadgeCheck, SendIcon, History, Import } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref, computed } from 'vue';
import FileCard from '../forms/file_card.vue';
import Badge from 'primevue/badge';
import { Text } from 'vue';
import Timeline from 'primevue/timeline';
import { ProductService } from '../service/ProductService';
import OverlayBadge from 'primevue/overlaybadge';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';

const page = usePage();
const officeId = page.props.auth.user.office_id;
onMounted(() => {
    applicantsTable(officeId);

});
const auth = computed(() => page.props.auth);
const roleId = auth.value.user?.role_id;


const toast = useToast();
const dt = ref();
const totalCount = ref(0);
const returnedTotalCount = ref(0);
const endorsedTotalCount = ref(0);
const approvedTotalCount = ref(0);

const products = ref();
const returned_application = ref();
const approved_application = ref();
const endorsed_application = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const isloadingSpinner = ref(false);
const showModal = ref(false);
const showProgressModal = ref(false)
const showFileModal = ref(false);
const selectedFile = ref(null);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const showReturnFieldset = ref(false);
const returnReason = ref(''); // Stores the user's input for the return reason
const isSubmitting = ref(false);
const product = ref({});
const selectedProducts = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const submitted = ref(false);
const statuses = ref([
    { label: 'INSTOCK', value: 'instock' },
    { label: 'LOWSTOCK', value: 'lowstock' },
    { label: 'OUTOFSTOCK', value: 'outofstock' },
]);

// Define steps
const events = [
    'Return for Compliance',
    'For Review / Evaluation',
    'Endorsed to CENRO',
    'Endorsed to PENRO',
    'Endorsed to R.O',
    'Approved'

];

const timelineItems = ref(events);
const eventsToDisplay = ref([]);
const confirmDialogRef = ref<any>(null);

// Example: Current step (0-based index)
const currentStep = ref(0); // "Endorsed to PENRO"
const openProgressTracker = async (data) => {
    showProgressModal.value = true;

    // If status = 0 → include "Return for Compliance"
    if (data.application_status === 0) {
        eventsToDisplay.value = [
            'Return for Compliance',
            'For Review / Evaluation',
            'Endorsed to CENRO',
            'Endorsed to PENRO',
            'Endorsed to R.O',
            'Approved'
        ];

        // Status 0 matches perfectly, no offset needed
        currentStep.value = data.application_status;
    } else {
        // Status > 0 → exclude "Return for Compliance"
        eventsToDisplay.value = [
            'For Review / Evaluation',
            'Endorsed to CENRO',
            'Endorsed to PENRO',
            'Endorsed to R.O',
            'Approved'
        ];

        // Adjust the index by subtracting 1 because we removed "Return for Compliance"
        currentStep.value = data.application_status - 1;
    }
};

const receiveApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: "Receive Application?",
        message: "Please confirm that you want to receive this application.",
        onConfirm: async () => {
            try {
                await axios.post(route("applications.penro.receive"), { id });
                toast.add({ severity: "success", summary: "Received", detail: "Application received" });
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to receive" });
            }
        }
    });
};

const endorseApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: "Endorse Application?",
        message: "Please confirm that you want to endorse this application.",
        onConfirm: async () => {
            try {
                await axios.post(route("applications.endorseToFUS"), { id,status:4});
                toast.add({ severity: "success", summary: "Endorsed", detail: "Application endorsed" });
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to endorse" });
            }
        }
    });
};


// Computed properties
const currentStepLabel = computed(() => events[currentStep.value]);
const progressPercentage = computed(() => Math.round(((currentStep.value + 1) / events.length) * 100));

const badgeSeverity = computed(() => {
    switch (currentStepLabel.value) {
        case 'Approved':
            return 'success';
        case 'Return for Compliance':
            return 'danger';
        default:
            return 'info';
    }
});

const activeTab = ref<'re' | 'ea' | 'rc' | 'cpr' | 'aa'>('re');

const applicationDetails = ref(null);
const files = ref([]);

const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};

const applicantsTable = async (id) => {
    try {
        const { applications: endorsedApplications, count: endorsedCount } =
            await ProductService.getApplicationsByStatus(10, id);
        endorsed_application.value = endorsedApplications;
        endorsedTotalCount.value = endorsedCount;

       
    } catch (error) {
        console.error('Error fetching applications:', error);
    }
};

;

const handleReturnReasonClick = async () => {
    if (!returnReason.value.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Return Application',
            detail: 'Please enter a reason for returning.',
            life: 3000,
        });
        return;
    }

    try {
        isSubmitting.value = true;

        const response = await axios.post(`/applications/return`, {
            application_id: applicationDetails.value.id,
            reason: returnReason.value,
        });

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Return Application',
                detail: 'Reason saved successfully!',
                life: 3000,
            });
            showReturnFieldset.value = false; // hide fieldset after saving
            returnReason.value = ''; // reset input
        } else {
            toast.add({
                severity: 'error',
                summary: 'Return Application',
                detail: response.data.message || 'Something went wrong.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Return Application',
            detail: 'Failed to save return reason.',
            life: 3000,
        });
    } finally {
        isSubmitting.value = false;
    }
};



const hideDialog = () => {
    productDialog.value = false;
    submitted.value = false;
};

const saveProduct = () => {
    submitted.value = true;

    if (product?.value.name?.trim()) {
        if (product.value.id) {
            product.value.inventoryStatus = product.value.inventoryStatus.value ? product.value.inventoryStatus.value : product.value.inventoryStatus;
            products.value[findIndexById(product.value.id)] = product.value;
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Updated', life: 3000 });
        } else {
            product.value.id = createId();
            product.value.code = createId();
            product.value.image = 'product-placeholder.svg';
            product.value.inventoryStatus = product.value.inventoryStatus ? product.value.inventoryStatus.value : 'INSTOCK';
            products.value.push(product.value);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Created', life: 3000 });
        }

        productDialog.value = false;
        product.value = {};
    }
};


const deleteProduct = () => {
    products.value = products.value.filter((val) => val.id !== product.value.id);
    deleteProductDialog.value = false;
    product.value = {};
    toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
};

const findIndexById = (id) => {
    let index = -1;
    for (let i = 0; i < products.value.length; i++) {
        if (products.value[i].id === id) {
            index = i;
            break;
        }
    }

    return index;
};

const createId = () => {
    let id = '';
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (var i = 0; i < 5; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return id;
};

const openFile = (file) => {
    selectedFile.value = file;
    showFileModal.value = true;
};

const editState = reactive({
    applicant: false,
    chainsaw: false,
});

const editableDetails = reactive({ ...applicationDetails.value });

const getEmbedUrl = (url) => {
    const match = url.match(/[-\w]{25,}/);
    const fileId = match ? match[0] : null;
    return fileId ? `https://drive.google.com/file/d/${fileId}/preview` : '';
};

const editableApplicant = reactive({});
const editableChainsaw = reactive({});

// =================================
// APPLICATION DATA
// Author: Mark Kim A. Sacluti
// Date: August 01, 2024
// =================================

const getApplicantFile = async (id) => {
    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicantFile/${id}`);
        if (response.data.status && Array.isArray(response.data.data)) {
            files.value = response.data.data.map((file) => ({
                attachment_id: file.id,
                application_id: file.application_id,
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

const getApplicationDetails = async (id) => {
    isloadingSpinner.value = true;
    try {
        const response = await axios.get(`http://192.168.2.106:8000/api/getApplicationDetails/${id}`);
        applicationDetails.value = response.data.data;
        await getApplicantFile(id);
        return response.data.data;
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

const editProduct = (product) => {
    // Example: go to /applications/123/edit
    router.visit(`/applications/${product.id}/edit`);
};

// =============================
// Separate Update Functions
// =============================

// Update only Applicant Details
const saveApplicantDetails = async () => {
    try {
        isloadingSpinner.value = true;

        const response = await axios.put(`http://192.168.2.106:8000/api/updateApplicantDetails/${applicationDetails.value.id}`, editableApplicant);

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Applicant details updated successfully.',
                life: 3000,
            });
            applicationDetails.value = { ...applicationDetails.value, ...editableApplicant };
            editState.applicant = false;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to save applicant details.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while saving applicant details.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

// Update only Chainsaw Information
const saveChainsawDetails = async () => {
    try {
        isloadingSpinner.value = true;

        const response = await axios.put(`http://192.168.2.106:8000/api/updateChainsawInformation/${applicationDetails.value.id}`, editableChainsaw);

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Chainsaw information updated successfully.',
                life: 3000,
            });
            applicationDetails.value = { ...applicationDetails.value, ...editableChainsaw };
            editState.chainsaw = false;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to save chainsaw details.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while saving chainsaw details.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

// =============================
// Toggle Edit States
// =============================
const toggleApplicantEdit = () => {
    if (editState.applicant) {
        saveApplicantDetails();
    } else {
        Object.assign(editableApplicant, {
            application_no: applicationDetails.value.application_no,
            date_applied: applicationDetails.value.date_applied,
            application_type: applicationDetails.value.application_type,
            company_name: applicationDetails.value.company_name,
            authorized_representative: applicationDetails.value.authorized_representative,
            company_address: applicationDetails.value.company_address,
        });
        editState.applicant = true;
    }
};

const toggleChainsawEdit = () => {
    if (editState.chainsaw) {
        saveChainsawDetails();
    } else {
        Object.assign(editableChainsaw, {
            permit_chainsaw_no: applicationDetails.value.permit_chainsaw_no,
            permit_validity: applicationDetails.value.permit_validity,
            brand: applicationDetails.value.brand,
            model: applicationDetails.value.model,
            quantity: applicationDetails.value.quantity,
        });
        editState.chainsaw = true;
    }
};

const handleEndorseApplicationStatus = async () => {
    try {
        isloadingSpinner.value = true;

        // Send PUT request to update the application status to 'endorsed'
        const response = await axios.put(`http://192.168.2.106:8000/api/updateApplicationStatus/${applicationDetails.value.id}`, {
            status: 2, //ENDORSED Only update the status field
        });

        if (response.data.status === 'success') {
            toast.add({
                severity: 'success',
                summary: 'Application Endorsed',
                detail: 'The application status has been updated to Endorsed.',
                life: 3000,
            });

            // Update the local application details to reflect the change
            applicationDetails.value.status = 'endorsed';
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update the application status.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while updating the status.',
            life: 3000,
        });
    } finally {
        isloadingSpinner.value = false;
    }
};

const triggerUpdateFile = (file) => {
    selectedFileToUpdate.value = file;
    updateFileInput.value.click();
};

const handleFileUpdate = async (event) => {
    const newFile = event.target.files[0];
    if (!newFile || !selectedFileToUpdate.value) return;

    try {
        const formData = new FormData();
        formData.append('application_id', selectedFileToUpdate.value.application_id);
        formData.append('file', newFile);
        formData.append('attachment_id', selectedFileToUpdate.value.attachment_id);
        formData.append('name', selectedFileToUpdate.value.name);

        const response = await axios.post('http://192.168.2.106:8000/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        // Update file list
        const updatedIndex = files.value.findIndex((f) => f.id === selectedFileToUpdate.value.id);
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile;
        }

        alert('File updated successfully!');
    } catch (error) {
        console.error(error);
        alert('Failed to update the file.');
    } finally {
        updateFileInput.value.value = ''; // reset file input
        selectedFileToUpdate.value = null;
    }
};
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">
        <Toast />

        <div
            class="box border-sidebar-border/70 dark:border-sidebar-border col-span-4 flex h-[590px] flex-col overflow-hidden rounded-xl border p-4">
            <!-- Tabs -->
            <div class="mb-4 flex border-b border-gray-200">
                <!-- For Review / Evaluation Tab -->
                <button @click="activeTab = 're'" :class="[
                    'border-b-2 px-4 py-2 text-sm font-medium transition flex items-center space-x-2',
                    activeTab === 're'
                        ? 'border-green-600 text-green-700'
                        : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600'
                ]">
                    <!-- Tab Title -->
                    <span>For Endorsement </span>

                    <!-- PrimeVue OverlayBadge with Icon -->
                    <OverlayBadge :value="endorsedTotalCount" severity="danger" size="small">
                        <i class="pi pi-list" style="font-size: 25px" />
                    </OverlayBadge>
                </button>
               



            </div>

            <!-- Content -->
            <div class="flex-1 space-y-4 overflow-y-auto">
                <!-- For Review / Evaluation Table -->
                <div v-if="activeTab === 're'" class="space-y-2 text-sm text-gray-700">
                    <div class="h-auto w-full">
                        <DataTable ref="dt" size="small" v-model:selection="selectedProducts" :value="endorsed_application"
                            dataKey="id" :paginator="true" :rows="4" :filters="filters" filterDisplay="menu"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                            responsiveLayout="scroll" class="w-full text-sm">
                            <!-- Table Header with Search -->
                            <template #header>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                                    </IconField>
                                </div>
                            </template>

                            <!-- Application No Column -->
                         <Column header="Action" :exportable="false" style="min-width: 2rem">
                                <template #body="slotProps">
                                    <div class="flex space-x-2">
                                        <div class="mt-2 flex gap-2">
                                            <!-- History Button (only enabled if is_rps_chief_received = 1) -->
                                            <Button @click="receiveApplication(slotProps.data.id)"
                                                style="background-color: #0f766e"
                                                class="p-2 text-white">
                                                <BadgeCheck :size="15" />
                                            </Button>

                                            <Button type="button" @click="openProgressTracker(slotProps.data)"
                                                
                                                style="background-color: #0f766e"
                                                class="rounded bg-teal-800 p-2 text-white hover:bg-teal-900 disabled:cursor-not-allowed disabled:opacity-50">
                                                <History :size="15" />
                                            </Button>

                                            <Button  type="button"
                                                style="background-color: #0f766e"
                                                class="rounded bg-teal-800 p-2 text-white hover:bg-teal-900">
                                                <Link :href="route('applications.edit', { id: slotProps.data.id, type: slotProps.data.application_type })
                                                    ">
                                                <Eye :size="15" />
                                                </Link>
                                            </Button>

                                            <Button :disabled="slotProps.data.is_penro_chief_received != 1" @click="endorseApplication(slotProps.data.id)"
                                                style="background-color: #0f766e"
                                               
                                            class="p-2 text-white">
                                                <SendIcon :size="15" />
                                            </Button>

                                        </div>



                                    </div>
                                </template>
                            </Column>
                            <Column field="application_no" header="Application No" sortable
                                style="text-align: center; width: 10rem">
                                <template #body="{ data }">
                                    <div class="relative flex flex-col items-center space-y-2">
                                        <!-- Ribbon if status is Approved -->
                                        <!-- <div v-if="data.status_title === 'Approved'">
                                            <Tag><i class="pi pi-check-circle"></i> {{ data.status_title }}</Tag>
                                        </div> -->

                                        <!-- <div v-else>
                                            <Tag> <i class="pi pi-check-circle"></i> {{ data.status_title }}</Tag>
                                        </div> -->

                                        <span class="ml-1 font-bold text-gray-800">
                                            {{ data.application_no }}
                                        </span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="purpose" header="Purpose" sortable style="text-align: center">
                                <template #body="{ data }">
                                    <div class="relative flex flex-col items-center space-y-2">
                                        <span class="ml-1 text-gray-800">
                                            {{ data.purpose }}
                                        </span>
                                    </div>
                                </template>
                            </Column>

                            <!-- Other Columns -->
                            <Column field="application_type" header="Application Type" sortable
                                style="min-width: 5rem" />
                            <Column field="date_of_payment" header="Date Paid" sortable style="min-width: 4rem" />

                            <!-- <Column field="permit_chainsaw_no" header="Chainsaw No" sortable style="min-width: 4rem" />

                            <Column header="Progress Tracker" sortable style="min-width: 4rem">
                                <template #body="slotProps">
                                    <Button type="button" @click="openProgressTracker(slotProps.data)"
                                        style="background-color: #0f766e;"
                                        class="mt-2 bg-teal-800 hover:bg-teal-900 text-white p-2 rounded">
                                        <History :size="15" />
                                    </Button>
                                </template>
                            </Column>

                            <Column field="date_of_payment" header="Date Paid" sortable style="min-width: 4rem" />
                            <Column field="permit_validity" header="Permit Validity" sortable style="min-width: 4rem" />
                            <Column header="Comments" :exportable="false" style="min-width: 8rem">
                                <template #body="{ data }">

                                    {{ data.return_reason }}
                                </template>
                            </Column>
                            <Column header="Action" :exportable="false" style="min-width: 2rem">
                                <template #body="slotProps">
                                    <div class="flex space-x-2">
                                        <Button class="bg-teal-800 hover:bg-teal-900 text-white p-2 rounded"
                                            style="background-color: #0f766e;" @click="openFileModal(slotProps.data)">
                                            <SquarePen :size="15" />
                                        </Button>
                                    </div>
                                </template>
                            </Column> -->
                        </DataTable>
                    </div>
                </div>
             

            </div>
        </div>


        <ReusableConfirmDialog ref="confirmDialogRef" />

        <Dialog v-model:visible="showModal" modal header="Application Preview" :style="{ width: '50vw' }">
            <div v-if="isloadingSpinner" class="flex h-40 items-center justify-center">
                <span>Loading...</span>
            </div>

            <div v-else-if="applicationDetails">
                <div class="flex items-center justify-between space-x-4">
                    <!-- Left Section: Buttons -->
                    <div class="flex items-center space-x-2">

                        <Button class="!border-blue-600 !bg-blue-900 !text-white hover:!bg-blue-700">
                            <Import />Submit Application
                        </Button>
                    </div>

                    <!-- Right Section: Status Message -->

                </div>

                <!-- Applicant Details -->
                <Fieldset v-show="showReturnFieldset" legend="Reason for Returning" :toggleable="true"
                    :collapsed="false" class="mt-4">
                    <textarea v-model="returnReason" class="w-full rounded border p-2"
                        placeholder="Enter reason for returning..."></textarea>
                    <div class="mt-2">
                        <Button class="!border-blue-600 !bg-blue-900 !text-white hover:!bg-blue-700"
                            @click="handleReturnReasonClick">
                            <SaveAll class="mr-1" />
                            Submit Reason
                        </Button>
                    </div>
                </Fieldset>
                <Fieldset v-if="applicationDetails.application_status == 4" legend="Reason for Returning"
                    :toggleable="true" :collapsed="false" class="mt-4">
                    <textarea v-model="applicationDetails.return_reason" class="w-full rounded border p-2"
                        placeholder="Enter reason for returning..."></textarea>
                    <div class="mt-2">
                        <Button class="!border-blue-600 !bg-blue-900 !text-white hover:!bg-blue-700"
                            @click="handleReturnReasonClick">
                            <SaveAll class="mr-1" />
                            Submit Reason
                        </Button>
                    </div>
                </Fieldset>
                <div class="relative">
                    <div class="ribbon">{{ applicationDetails.status_title }}</div>

                    <Fieldset legend="Review Details" :toggleable="true" v-if="(applicationDetails.return_reason)">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-300 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-4 py-2 text-left font-semibold w-1/3">
                                            REVIEWED BY:
                                        </th>
                                        <th class="border border-gray-300 px-4 py-2 text-left font-semibold w-1/2">
                                            REMARKS</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left font-semibold w-1/6">DATE
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            <div class="font-bold">JUAN DELA CRUZ
                                            </div>
                                            <div class="text-gray-700 text-sm">RPS Chief</div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            {{ applicationDetails.return_reason }}
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            09/23/2025
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            <div class="font-bold">NAOMI Z. PERILLA</div>
                                            <div class="text-gray-700 text-sm">Ass’t. Div. Chief, Licenses, Patents and
                                                Deeds
                                                Division</div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            ~
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            ~
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            <div class="font-bold">~
                                            </div>
                                            <div class="text-gray-700 text-sm"> Chief or Section Head</div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            ~
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 align-top">
                                            ~
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Fieldset>



                    <Fieldset legend="Applicant Details" :toggleable="true">

                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Applicant Details</h3>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                            <!-- Application Number -->
                            <div class="flex items-center">
                                <span class="w-32 font-semibold">Application No:</span>
                                <span class="w-[250] font-semibold">{{ applicationDetails.application_no }}</span>
                            </div>

                            <!-- Date Applied -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Date Applied:</span>
                                <span v-if="!editState.applicant">{{ applicationDetails.date_applied }}</span>
                                <DatePicker v-else v-model="editableApplicant.date_applied" class="w-full" />
                            </div>

                            <!-- Type of Transaction -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Type of Transaction:</span>
                                <span v-if="!editState.applicant">{{ applicationDetails.application_type }}</span>
                                <InputText v-else v-model="editableApplicant.application_type" class="w-full" />
                            </div>

                            <!-- Company Name -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Company Name:</span>
                                <span v-if="!editState.applicant">{{ applicationDetails.company_name }}</span>
                                <InputText v-else v-model="editableApplicant.company_name" class="w-full" />
                            </div>

                            <!-- Authorized Representative -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Authorized Representative:</span>
                                <span v-if="!editState.applicant">{{ applicationDetails.authorized_representative
                                }}</span>
                                <InputText v-else v-model="editableApplicant.authorized_representative"
                                    class="w-full" />
                            </div>

                            <!-- Region (Read-only) -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Region:</span>
                                <span class="w-full text-gray-700"> REGION IV-A (CALABARZON) </span>
                            </div>

                            <!-- Complete Address -->
                            <div class="flex items-center">
                                <span class="w-48 font-semibold">Complete Address:</span>
                                <span v-if="!editState.applicant">{{ applicationDetails.company_address }}</span>
                                <Textarea v-else v-model="editableApplicant.company_address" class="w-full" />
                            </div>
                        </div>
                    </Fieldset>
                </div>

                <!-- Chainsaw Information -->
                <Fieldset legend="Chainsaw Information" :toggleable="true">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Chainsaw Information</h3>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Chainsaw No:</span>
                            <span class="w-48"> {{ applicationDetails.permit_chainsaw_no }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-32 font-semibold">Permit Validity:</span>

                            <span class="w-64">{{ applicationDetails.permit_validity }}</span>
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Brand:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.brand }}</span>
                            <InputText v-else v-model="editableChainsaw.brand" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Model:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.model }}</span>
                            <InputText v-else v-model="editableChainsaw.model" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Quantity:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.quantity }}</span>
                            <InputText v-else v-model="editableChainsaw.quantity" class="w-full" />
                        </div>
                    </div>
                </Fieldset>

                <!-- Uploaded Files Section -->
                <Fieldset legend="Uploaded Files" :toggleable="true">
                    <div class="container">
                        <div class="file-list grid grid-cols-1 gap-2 md:grid-cols-2">
                            <FileCard v-for="(file, index) in files" :key="index" :file="file" @openPreview="openFile"
                                @updateFile="triggerUpdateFile" />
                        </div>
                    </div>
                </Fieldset>

                <!-- Hidden Input for File Update -->
                <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                <!-- File Preview Modal -->
                <Dialog v-model:visible="showFileModal" modal header="File Preview" :style="{ width: '70vw' }">
                    <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500"
                        allow="autoplay"></iframe>
                </Dialog>
            </div>
        </Dialog>

        <Dialog v-model:visible="showProgressModal" modal header="Progress Tracker" :style="{ width: '50vw' }">
            <div class="p-6">
                <!-- Title Section -->


                <!-- Clean Progress Bar -->
                <!-- <ProgressBar :value="progressPercentage" class="h-2 mb-8 rounded-full" /> -->

                <!-- Stepper Timeline -->
                <div class="flex justify-between items-center relative">
                    <div v-for="(step, index) in eventsToDisplay" :key="index"
                        class="flex flex-col items-center text-center w-1/5">

                        <!-- Step Circle with Icons -->
                        <div class="flex items-center justify-center w-12 h-12 rounded-full text-white text-lg font-semibold transition-all duration-300"
                            :class="{
                                'bg-red-500': index === 0 && step === 'Return for Compliance', // Special red step
                                'bg-green-500': index < currentStep,    // Completed
                                'bg-blue-500': index === currentStep,   // Current step
                                'bg-gray-300': index > currentStep      // Upcoming
                            }">
                            <template v-if="index < currentStep">
                                <i class="pi pi-check text-white"></i>
                            </template>
                            <template v-else>
                                {{ index + 1 }}
                            </template>
                        </div>

                        <!-- Step Label -->
                        <span class="mt-2 text-sm font-medium" :class="{
                            'text-gray-800': index <= currentStep,
                            'text-gray-400': index > currentStep
                        }">
                            {{ step }}
                        </span>
                    </div>



                </div>

                <!-- Footer -->
                <div class="mt-8 flex justify-end">
                    <Button label="Close" icon="pi pi-times" class="p-button-success px-5"
                        @click="showProgressModal = false" />
                </div>
            </div>
        </Dialog>

    </div>
</template>
<style scoped>
@media screen and (max-width: 960px) {
    ::v-deep(.customized-timeline) {
        .p-timeline-event:nth-child(even) {
            flex-direction: row;

            .p-timeline-event-content {
                text-align: left;
            }
        }

        .p-timeline-event-opposite {
            flex: 0;
        }
    }
}

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




/* HTML: <div class="ribbon">Your text content</div> */
/* HTML: <div class="ribbon">Your text content</div> */
.approved_ribbon {
    font-size: 18px;
    font-weight: bold;
    color: #fff;

}

.approved_ribbon {
    --r: .8em;
    /* control the ribbon shape */

    padding-inline: calc(var(--r) + .3em);
    line-height: 1.8;
    clip-path: polygon(0 0, 100% 0, calc(100% - var(--r)) 50%, 100% 100%, 0 100%, var(--r) 50%);
    background: #E65100;
    /* the main color */
    width: fit-content;
}

.comment-wrap {
    white-space: normal;
    /* ✅ Allow wrapping */
    word-break: break-word;
    /* ✅ Break long words */
    overflow-wrap: break-word;
    /* ✅ Support for various browsers */
    max-width: 200px;
    /* Optional: Limit the width */
}
</style>
