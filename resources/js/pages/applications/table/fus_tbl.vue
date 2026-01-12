<script setup lang="ts">
import { Link, router,usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import { BadgeCheck, Eye, History, SaveAll, Send, SendIcon, ShieldCheck, Undo2 } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import OverlayBadge from 'primevue/overlaybadge';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import FileCard from '../forms/file_card.vue';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';
import { ProductService } from '../service/ProductService';

onMounted(() => {
    applicantsTable();
});

const STATUS_DRAFT = 1;
const STATUS_FOR_REVIEW_EVALUATION = 2;

const STATUS_ENDORSED_CENRO_CHIEF = 3;
const STATUS_ENDORSED_RPS_CHIEF = 4;
const STATUS_ENDORSED_TSD_CHIEF = 5;
const STATUS_ENDORSED_PENRO = 6;
const STATUS_ENDORSED_LPDD_FUS = 7;
const STATUS_ENDORSED_ARDTS = 8;
const STATUS_APPROVED_BY_RED = 9;

const STATUS_RECEIVED_CENRO_CHIEF = 10;
const STATUS_RECEIVED_CHIEF_RPS = 11;
const STATUS_RECEIVED_TSD_CHIEF = 12;
const STATUS_RECEIVED_PENRO_CHIEF = 13;
const STATUS_RECEIVED_FUS = 14;
const STATUS_RECEIVED_ARDTS = 15;
const STATUS_RECEIVED_RED = 16;

const STATUS_RETURN_TO_CENRO_CHIEF = 17;
const STATUS_RETURN_TO_RPS_CHIEF = 18;
const STATUS_RETURN_TO_TSD_CHIEF = 19;
const STATUS_RETURN_TO_PENRO = 20;
const STATUS_RETURN_TO_LPDD_FUS = 21;
const STATUS_RETURN_TO_ARDTS = 22;
const STATUS_RETURN_TO_RED = 23;
const STATUS_RETURN_TO_TECHNICAL_STAFF = 24;

const page = usePage();
const toast = useToast();
const dt = ref();
const totalCount = ref(0);
const returnedTotalCount = ref(0);
const endorsedTotalCount = ref(0);
const approvedTotalCount = ref(0);
const confirm = useConfirm();
const isLoading = ref(false);
const products = ref();
const signatories_data = ref();
const returned_application = ref();
const approved_application = ref();
const endorsed_applications = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const isloadingSpinner = ref(false);
const showModal = ref(false);
const confirmDialogRef = ref<any>(null);

const showProgressModal = ref(false);
const showCommentsModal = ref(false);
const routingHistory = ref([]);
const commentsHistory = ref([]);
const loadingRouting = ref(false);
const loadingComment = ref(false);
const showFileModal = ref(false);
const selectedFile = ref(null);
const selectedFileToUpdate = ref(null);
const updateFileInput = ref(null);
const selectedApplicationId = ref(null);
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
const events = ['Return for Compliance', 'For Review / Evaluation', 'Endorsed to CENRO', 'Endorsed to PENRO', 'Endorsed to R.O', 'Approved'];
const currentStep = ref(0); // "Endorsed to PENRO"
const progress_tracker_data = ref([]);

const timelineComputed = computed(() => {
    return eventsToDisplay.value.map((label, index) => {
        // find matching record by status title
        const record = progress_tracker_data.value.find((item) => item.status_title === label);

        const isCompleted = !!record;
        const isCurrent = !record && index === progress_tracker_data.value.length;
        const isUpcoming = !record && index > progress_tracker_data.value.length;

        return {
            index,
            label,
            record, // 👈 store entire record here
            date: record ? record.created_at : null,
            isCompleted,
            isCurrent,
            isUpcoming,
            icon: isCompleted ? 'pi pi-check' : isCurrent ? 'pi pi-spinner' : 'pi pi-clock',
            color: isCompleted ? '#4CAF50' : isCurrent ? '#2196F3' : '#BDBDBD',
        };
    });
});

const timelineItems = ref(events);
const eventsToDisplay = ref([]);

// Example: Current step (0-based index)

const openProgressTracker = async (data) => {
    getSignatories(data.id);
    showProgressModal.value = true;
    loadingRouting.value = true;
    routingHistory.value = [];

    try {
        const res = await axios.get(`/api/application-routing/${data.id}`);
        routingHistory.value = res.data;
    } catch (error) {
        console.error(error);
    } finally {
        loadingRouting.value = false;
    }
    // // Always assign these once only

    if (data.application_status === 0) {
        // FULL TIMELINE (6 steps)
        eventsToDisplay.value = [
            'Return for Compliance',
            'For Review / Evaluation',
            'Endorsed to CENRO',
            'Endorsed to PENRO',
            'Endorsed to R.O',
            'Approved',
        ];

        // currentStep matches 1:1
        currentStep.value = 0;
    } else {
        // SHORT TIMELINE (removed Return for Compliance)
        eventsToDisplay.value = ['For Review / Evaluation', 'Endorsed to CENRO', 'Endorsed to PENRO', 'Endorsed to R.O', 'Approved'];

        // Adjust index because we removed index 0
        currentStep.value = data.application_status;
    }
};

const endorseApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: 'Endorse Application?',
        message: 'Please confirm that you want to endorse this application.',
        onConfirm: async () => {
            try {
                await axios.post(route('applications.fus.endorse'), { id, status: 10 });
                toast.add({ severity: 'success', summary: 'Endorsed', detail: 'Application endorsed' });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to endorse' });
            }
        },
    });
};

const receiveApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: 'Receive Application?',
        message: 'Please confirm that you want to receive this application.',
        onConfirm: async () => {
            try {
                await axios.post(route('applications.fus.receive'), { id });
                toast.add({ severity: 'success', summary: 'Received', detail: 'Application received' });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to receive' });
            }
        },
    });
};

// Computed properties
// const currentStepLabel = computed(() => events[currentStep.value]);
// const progressPercentage = computed(() => Math.round(((currentStep.value + 1) / events.length) * 100));

// const badgeSeverity = computed(() => {
//     switch (currentStepLabel.value) {
//         case 'Approved':
//             return 'success';
//         case 'Return for Compliance':
//             return 'danger';
//         default:
//             return 'info';
//     }
// });

const activeTab = ref<'re' | 'ea' | 'rc' | 'cpr' | 'aa'>('re');

const applicationDetails = ref(null);
const files = ref([]);

const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};

const applicantsTable = async () => {
    try {
        const officeId = page.props.auth.user.office_id;

        const { applications: endorsedApplications, count: endorsedCount } = await ProductService.getApplicationsByStatus(STATUS_ENDORSED_LPDD_FUS, officeId);

        endorsed_applications.value = endorsedApplications;
        totalCount.value = endorsedCount;

    } catch (error) {
        console.error('Error fetching applications:', error);
    }
};



const getSignatories = async (id) => {
    isloadingSpinner.value = true;
    try {
        const response = await axios.get(`http://192.168.0.117:8000/api/getSignatories/${id}`);
        progress_tracker_data.value = response.data; // 👈 store data directly
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

const handleReturnClick = () => {
    showReturnFieldset.value = !showReturnFieldset.value; // Toggle on click
};

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

const openNew = () => {
    product.value = {};
    submitted.value = false;
    productDialog.value = true;
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

const confirmDeleteProduct = (prod) => {
    product.value = prod;
    deleteProductDialog.value = true;
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

const exportCSV = () => {
    dt.value.exportCSV();
};

const confirmDeleteSelected = () => {
    deleteProductsDialog.value = true;
};

const deleteSelectedProducts = () => {
    products.value = products.value.filter((val) => !selectedProducts.value.includes(val));
    deleteProductsDialog.value = false;
    selectedProducts.value = null;
    toast.add({ severity: 'success', summary: 'Successful', detail: 'Products Deleted', life: 3000 });
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'INSTOCK':
            return 'success';

        case 'LOWSTOCK':
            return 'warn';

        case 'OUTOFSTOCK':
            return 'danger';

        default:
            return null;
    }
};

const openFileModal = async (data) => {
    await getApplicationDetails(data.id);
    showModal.value = true;
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
        const response = await axios.get(`http://192.168.0.117:8000/api/getApplicantFile/${id}`);
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
        const response = await axios.get(`http://192.168.0.117:8000/api/getApplicationDetails/${id}`);
        applicationDetails.value = response.data.data;
        await getApplicantFile(id);
        return response.data.data;
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

// =============================
// Separate Update Functions
// =============================

const saveApplicantDetails = async () => {
    try {
        isloadingSpinner.value = true;

        const response = await axios.put(`http://192.168.0.117:8000/api/updateApplicantDetails/${applicationDetails.value.id}`, editableApplicant);

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

        const response = await axios.put(`http://192.168.0.117:8000/api/updateChainsawInformation/${applicationDetails.value.id}`, editableChainsaw);

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

const users = reactive([
    { name: 'John Doe', email: 'john@example.com', role: 'Admin' },
    { name: 'Jane Smith', email: 'jane@example.com', role: 'Editor' },
]);

const title = ref('User Report');
const content = ref('This is a dynamically generated user table.');
const generatePdf = async () => {
    const applicationData = {
        permit_number: 'DENR-IV-A-2025-0004',
        name: 'Mark Kim',
        address: 'Brgy. Mayapa, Calamba City, Laguna',
        quantity: 2,
        brand: 'ewfeff',
        model: 'efeefe',
        engine_serial: '232E-XYZ',
        supplier_name: 'Chainsaw Supplier Inc.',
        supplier_address: 'Calamba City',
        ps_number: 'PSC-160',
        purchase_price: '500',
        purpose: 'For cutting of trees with legal permit',
        others: '',
        issued_date: 'November 27, 2025',
        expiry_date: 'November 27, 2025',
        or_number: '2025-11-25',
        or_date: 'November 25, 2025',
    };

    const response = await fetch('/api/generate-table-pdf', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(applicationData),
    });

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'permit.pdf';
    a.click();
    window.URL.revokeObjectURL(url);
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
        const response = await axios.put(`http://192.168.0.117:8000/api/updateApplicationStatus/${applicationDetails.value.id}`, {
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

        const response = await axios.post('http://192.168.0.117:8000/api/files/update', formData, {
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
const openDialog = (type: 'endorse' | 'return' | 'receive', id: number) => {
    const config = {
        endorse: {
            header: 'Endorse this application to ARDTS?',
            message: 'Please confirm that you want to endorse this application.',
            api: 'applications.fus.endorse',
            payload: { id },
            showTextarea: false,
            showDropdown: false,
            toastMessage: 'Application endorsed',
        },
        return: {
            header: 'Return Application?',
            message: 'Please indicate the reason and office to return this application.',
            api: 'applications.fus.return',
            payload: { id },
            showTextarea: true,
            showDropdown: true,
            toastMessage: 'Application returned',
            offices: [
                { label: 'Technical Staff', value: 1 },
                { label: 'Chief, RPS', value: 8 },
                { label: 'Chief, TSD', value: 10 },
                { label: 'Chief, PENRO', value: 3 },
            ],
        },
        receive: {
            header: 'Receive Application?',
            message: 'Please confirm that you want to receive this application.',
            api: 'applications.fus.receive',
            payload: { id },
            showTextarea: false,
            showDropdown: false,
            toastMessage: 'Application received',
        },
    };

    const c = config[type];
    confirmDialogRef.value?.open({
        header: c.header,
        message: c.message,
        showTextarea: c.showTextarea,
        showDropdown: c.showDropdown,
        offices: c.offices,
        onConfirm: async (data?: { remarks?: string; returnTo?: string | number }) => {
            try {
                // ✅ send remarks and returnTo along with payload
                await axios.post(route(c.api), {
                    ...c.payload,
                    remarks: data?.remarks,
                    returnTo: data?.returnTo,
                });

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: c.toastMessage,
                    life: 3000,
                });
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Something went wrong',
                    life: 3000,
                });
            }
        },
    });
};

const openCommentModal = async (data) => {
    showCommentsModal.value = true;
    loadingComment.value = true;
    try {
        const res = await axios.get(`/api/getCommentsByID/${data.id}`);
        commentsHistory.value = res.data;
    } catch (error) {
        console.error(error);
    } finally {
        loadingComment.value = false;
    }
};
const buttonState = (row: any) => {
    const isReceived = !!row.is_fus_received;
    const isEndorsedToFUS =
        row.application_status === STATUS_ENDORSED_LPDD_FUS;

    const isEndorsedToARDTS =
        row.application_status === STATUS_ENDORSED_ARDTS || !STATUS_ENDORSED_LPDD_FUS;

    return {
        // 🔵 Receive is ENABLED when endorsed to TSD and not yet received
        receiveDisabled: !isEndorsedToFUS,

        // 🔵 Endorse is ENABLED only while still at TSD level
        endorseDisabled: isEndorsedToARDTS || isReceived,

        // 🔵 adjust if you later add rules
        returnDisabled: false
    };
};
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">
        <Toast />
        <div class="">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <!-- For Review / Evaluation Tab -->
                <button
                    @click="activeTab = 're'"
                    :class="[
                        'flex items-center space-x-2 border-b-2 px-4 py-2 text-sm font-medium transition',
                        activeTab === 're'
                            ? 'border-green-600 text-green-700'
                            : 'border-transparent text-gray-500 hover:border-green-500 hover:text-green-600',
                    ]"
                >
                    <!-- Tab Title -->
                    <span>List of Permit Application</span>

                    <div class="relative inline-block">
                        <OverlayBadge v-if="totalCount > 0" :value="totalCount" severity="danger" size="small" class="absolute top-0 right-0" />
                        <i class="pi pi-list" style="font-size: 25px" />
                    </div>
                </button>
            </div>
            <!-- Content -->
            <div class="flex-1 space-y-4 overflow-y-auto">
                <!-- For Review / Evaluation Table -->
                <div v-if="activeTab === 're'" class="space-y-2 text-sm text-gray-700">
                    <div class="h-auto w-full">
                        <DataTable ref="dt" size="small" v-model:selection="selectedProducts" :value="endorsed_applications"
                        dataKey="id" :paginator="true" :rows="20" :filters="filters" filterDisplay="menu"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                        responsiveLayout="scroll" class="w-full text-sm">
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
                        <Column header="Action" :exportable="false" style="min-width: 2rem">
                            <template #body="slotProps">
                                <div class="mt-2 flex gap-2">

                                    <!-- ✅ RECEIVE (disabled if endorsed) -->
                                    <Button :disabled="buttonState(slotProps.data).receiveDisabled"
                                        @click="openDialog('receive', slotProps.data.id)"
                                        style="background-color: #0f766e" class="p-2 text-white">
                                        <BadgeCheck :size="15" />
                                    </Button>

                                    <!-- ✅ ROUTING / HISTORY (ALWAYS ENABLED) -->
                                    <Button type="button" @click="openProgressTracker(slotProps.data)"
                                        style="background-color: #0f766e; border: 1px solid #0f766e !important"
                                        class="rounded p-2 text-white hover:bg-teal-900">
                                        <History :size="15" />
                                    </Button>

                                    <!-- ✅ VIEW (ALWAYS ENABLED) -->
                                    <Button type="button" style="background-color: #0f766e"
                                        class="rounded p-2 text-white hover:bg-teal-900">
                                        <Link :href="route('applications.edit', {
                                            id: slotProps.data.id,
                                            type: slotProps.data.application_type
                                        })">
                                            <Eye :size="15" />
                                        </Link>
                                    </Button>

                                    <!-- ❌ ENDORSE (disabled if endorsed) -->
                                    <Button :disabled="buttonState(slotProps.data).endorseDisabled"
                                        @click="openDialog('endorse', slotProps.data.id)"
                                        style="background-color: #0f766e" class="p-2 text-white">
                                        <SendIcon :size="15" />
                                    </Button>

                                    <!-- ❌ RETURN (disabled if endorsed) -->
                                    <Button :disabled="buttonState(slotProps.data).returnDisabled"
                                        @click="openDialog('return', slotProps.data.id)"
                                        style="background-color: #bd081c; border: 1px solid #cd201f !important"
                                        class="p-2 text-white">
                                        <Undo2 :size="15" />
                                    </Button>

                                </div>
                            </template>
                        </Column>
                        <Column field="status_title" header="Status" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex flex-col items-center">
                                    <Tag :value="data.status_title" :severity="data.status_title === 'Returned to RPS Chief' ? 'danger' :
                                        data.status_title === 'Endorsed to TSD Chief' ? 'info' :
                                            'success'
                                        " class="text-center" />


                                    <Button
                                        style="display: inline; padding: .2em .6em .3em; font-size: 75%; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em;"
                                        severity="info" v-if="data.status_title === 'Returned to RPS Chief'"
                                        class="rounded bg-blue-900 px-1 py-1 mt-1 text-xs text-white"
                                        @click="openCommentModal(data)" size="small">
                                        View Comments
                                    </Button>
                                </div>
                            </template>
                        </Column>
                        <Column field="application_no" header="Application No" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <b>{{ data.application_no }}</b>
                            </template>
                        </Column>
                        <Column field="application_type" header="Application Type" sortable />
                        <Column header="Type of Transaction" field="transaction_type" sortable></Column>
                        <Column header="Classification" field="classification" sortable></Column>
                        <Column field="date_applied" header="Date of Application" sortable style="min-width: 4rem" />
                    </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <ReusableConfirmDialog ref="confirmDialogRef" />

        <Dialog v-model:visible="showModal" modal header="Application Preview" :style="{ width: '55vw' }">
            <div v-if="isloadingSpinner" class="flex h-40 items-center justify-center">
                <span>Loading...</span>
            </div>

            <div v-else-if="applicationDetails">
                <!-- Action Buttons -->
                <div class="flex items-center justify-between space-x-4">
                    <!-- Left Section: Buttons -->
                    <div class="flex items-center space-x-2">
                        <Button
                            class="!border-green-600 !bg-green-900 !text-white hover:!bg-green-700"
                            @click="handleEndorseApplicationStatus"
                            size="small"
                        >
                            <Send class="mr-1" /> Endorsed
                        </Button>

                        <Button class="!border-green-600 !bg-green-900 !text-white hover:!bg-green-700" size="small" @click="handleReturnClick">
                            <Undo2 class="mr-1" /> Returned
                        </Button>
                        <Button class="!border-green-600 !bg-green-900 !text-white hover:!bg-green-700" size="small">
                            <ShieldCheck class="mr-1" /> Approved
                        </Button>
                    </div>

                    <!-- Right Section: Status Message -->
                </div>

                <!-- Applicant Details -->
                <Fieldset v-show="showReturnFieldset" legend="Reason for Returning" :toggleable="true" :collapsed="false" class="mt-4">
                    <textarea v-model="returnReason" class="w-full rounded border p-2" placeholder="Enter reason for returning..."></textarea>
                    <div class="mt-2">
                        <Button class="!border-blue-600 !bg-blue-900 !text-white hover:!bg-blue-700" @click="handleReturnReasonClick">
                            <SaveAll class="mr-1" />
                            Submit Reason
                        </Button>
                    </div>
                </Fieldset>

                <Fieldset
                    v-if="applicationDetails.application_status == 4"
                    legend="Reason for Returning"
                    :toggleable="true"
                    :collapsed="false"
                    class="mt-4"
                >
                    <textarea
                        v-model="applicationDetails.return_reason"
                        class="w-full rounded border p-2"
                        placeholder="Enter reason for returning..."
                    ></textarea>
                    <div class="mt-2">
                        <Button class="!border-blue-600 !bg-blue-900 !text-white hover:!bg-blue-700" @click="handleReturnReasonClick">
                            <SaveAll class="mr-1" />
                            Submit Reason
                        </Button>
                    </div>
                </Fieldset>

                <Fieldset legend="Review Details" :toggleable="true" v-if="applicationDetails.return_reason">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-300 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="w-1/3 border border-gray-300 px-4 py-2 text-left font-semibold">REVIEWED BY:</th>
                                    <th class="w-1/2 border border-gray-300 px-4 py-2 text-left font-semibold">REMARKS</th>
                                    <th class="w-1/6 border border-gray-300 px-4 py-2 text-left font-semibold">DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="border border-gray-300 px-4 py-3 align-top">
                                        <div class="font-bold">VIRGILIO C. ANDRES, JR., <span class="italic">RPF</span></div>
                                        <div class="text-sm text-gray-700">
                                            Assistant Division Chief, Licenses, Patents and Deeds Division in concurrent capacity as Chief, Forest
                                            Utilization Section
                                        </div>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">
                                        {{ applicationDetails.return_reason }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">6/24</td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-300 px-4 py-3 align-top">
                                        <div class="font-bold">NAOMI Z. PERILLA</div>
                                        <div class="text-sm text-gray-700">Ass’t. Div. Chief, Licenses, Patents and Deeds Division</div>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">Approval of PPC to recommend</td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">6/24/2024</td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-300 px-4 py-3 align-top">
                                        <div class="font-bold">ERIBERTO B. SAÑOS, <span class="italic">CESE</span></div>
                                        <div class="text-sm text-gray-700">OIC-Assistant Regional Director for Technical Services</div>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">Recommended for approval</td>
                                    <td class="border border-gray-300 px-4 py-3 align-top">6/25</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Fieldset>

                <Fieldset legend="Applicant Details" :toggleable="true">
                    <!-- Applicant Info (non-file fields) -->
                    <!-- <h1 class="font-xl">Below is the checklist of requirements currently pending approval.</h1> -->
                    <div class="relative">
                        <div class="ribbon">
                            {{ applicationDetails.status_title || 'DRAFT' }}
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                            <div class="flex">
                                <span class="w-48 font-semibold">Application No:</span>
                                <Tag :value="applicationDetails.application_no" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Application Type:</span>
                                <Tag :value="applicationDetails.application_type" severity="success" class="text-center" />
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Date Applied:</span>
                                <span>{{ applicationDetails.date_applied }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Type of Transaction:</span>
                                <span>{{ applicationDetails.type_of_transaction }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Company Name:</span>
                                <span>{{ applicationDetails.company_name }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Authorized Representative:</span>
                                <span>{{ applicationDetails.authorized_representative }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-48 font-semibold">Region:</span>
                                <span>REGION IV-A (CALABARZON)</span>
                            </div>
                            <!-- <div class="flex">
                        <span class="w-48 font-semibold">Province:</span>
                        <span>{{ applicationDetails.prov_name }}</span>
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
                                <span>{{ applicationDetails.company_address }}</span>
                            </div>
                        </div>
                    </div>
                </Fieldset>

                <!-- Chainsaw Information -->
                <Fieldset legend="Chainsaw Information" :toggleable="true">
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-48 font-semibold">Chainsaw No:</span>
                            <Tag :value="applicationDetails.permit_chainsaw_no" severity="success" class="text-center" /><br />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Permit Validity:</span>
                            <Tag :value="applicationDetails.permit_validity" severity="danger" class="text-center" />
                            <br />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Brand:</span>
                            <span>{{ applicationDetails.brand }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Model:</span>
                            <span>{{ applicationDetails.model }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Quantity:</span>
                            <span>{{ applicationDetails.quantity }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Supplier Name:</span>
                            <span>{{ applicationDetails.supplier_name }}</span>
                        </div>
                        <!-- <div class="flex">
                        <span class="w-48 font-semibold">Supplier Address:</span>
                        <span>123 Supplier St., Calabarzon</span>
                    </div> -->
                        <div class="flex">
                            <span class="w-48 font-semibold">Purpose of Purchase:</span>
                            <span>{{ applicationDetails.purpose }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Other Details:</span>
                            <span>{{ applicationDetails.other_details }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Official Receipt:</span>
                            <Tag :value="applicationDetails.official_receipt" severity="success" class="text-center" />
                            <br />
                        </div>
                        <div class="flex">
                            <span class="w-48 font-semibold">Permit Fee:</span>
                            <span>₱ {{ applicationDetails.permit_fee }}</span>
                        </div>
                    </div>
                </Fieldset>

                <!-- Uploaded Files Section -->
                <Fieldset legend="Uploaded Requirements" :toggleable="true">
                    <div class="container">
                        <div class="file-list grid grid-cols-1 gap-2 md:grid-cols-2">
                            <FileCard
                                v-for="(file, index) in files"
                                :key="index"
                                :file="file"
                                @openPreview="openFile"
                                @updateFile="triggerUpdateFile"
                            />
                        </div>
                    </div>
                </Fieldset>

                <!-- Hidden Input for File Update -->
                <input type="file" ref="updateFileInput" class="hidden" @change="handleFileUpdate" />

                <!-- File Preview Modal -->
                <Dialog v-model:visible="showFileModal" modal header="File Preview" :style="{ width: '70vw' }">
                    <iframe v-if="selectedFile" :src="getEmbedUrl(selectedFile.url)" width="100%" height="500" allow="autoplay"></iframe>
                </Dialog>
            </div>
        </Dialog>

        <Dialog v-model:visible="showProgressModal" modal header="Routing History" :style="{ width: '50vw' }">
            <div class="overflow-x-auto">
                <!-- Loading state -->
                <div v-if="loadingRouting" class="p-4 text-center text-gray-500">Loading routing history...</div>

                <!-- Table -->
                <table v-else class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 text-left">#</th>
                            <th class="border px-4 py-2 text-left">Sender</th>
                            <th class="border px-4 py-2 text-left">Route Details</th>
                            <th class="border px-4 py-2 text-left">Receiver</th>
                            <th class="border px-4 py-2 text-left">Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in routingHistory" :key="index" class="hover:bg-gray-50">
                            <!-- # -->
                            <td class="border px-4">
                                {{ index + 1 }}
                            </td>

                            <!-- Sender -->
                            <td class="border px-4" style="width: 10rem">
                                <b>{{ item.sender_role }}</b
                                ><br />
                                <i>{{ item.sender }}</i
                                ><br />
                                {{ new Date(item.created_at).toLocaleDateString() }}
                            </td>

                            <!-- Route details -->
                            <td class="border px-4">
                                <b>Route No. {{ item.route_order }}</b
                                ><br />
                                {{ item.action }}
                            </td>

                            <!-- Receiver -->
                            <td class="border px-4" style="width: 20rem">
                                <b>{{ item.receiver_role }}</b
                                ><br />

                                <Tag v-if="item.action === 'Received'" severity="success" size="small"> Received </Tag>

                                <Tag v-else-if="item.action === 'Endorsed'" severity="info" size="small"> Endorsed </Tag>

                                <Tag v-else severity="warning" size="small">
                                    {{ item.action }}
                                </Tag>

                                <br />
                                <span v-if="item.route_order == 2">
                                    {{ new Date(item.date_received_rps_chief).toLocaleDateString() }}
                                </span>
                                <span v-else-if="item.route_order == 4">
                                    {{ new Date(item.date_received_tsd_chief).toLocaleDateString() }}
                                </span>
                                <span v-else-if="item.route_order == 6">
                                    {{ new Date(item.date_received_penro_chief).toLocaleDateString() }}
                                </span>
                                <span v-else-if="item.route_order == 8">
                                    {{ new Date(item.date_received_fus).toLocaleDateString() }}
                                </span>
                                <span v-else-if="item.route_order == 10">
                                    {{ new Date(item.date_received_ardts).toLocaleDateString() }}
                                </span>
                            </td>

                            <!-- Remarks -->
                            <td class="border px-4">
                                {{ item.remarks ?? '-' }}
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="routingHistory.length === 0">
                            <td colspan="5" class="p-4 text-center text-gray-500">No routing history found</td>
                        </tr>
                    </tbody>
                </table>
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
/* HTML: <div class="ribbon">Your text content</div> */
.ribbon {
    font-size: 15px;
    font-weight: bold;
    color: #fff;
}

.ribbon {
    --r: 0.4em;
    /* control the ribbon shape (the radius) */
    --c: #7e0606;

    position: absolute;
    top: -60px;
    right: calc(-3.4 * var(--r));
    line-height: 1.8;
    padding: 0 0.5em calc(2 * var(--r));
    border-radius: 0 var(--r) var(--r) 0;
    background:
        radial-gradient(100% 50% at right, var(--c) 98%, #0000 101%) 0 0/0.5lh calc(100% - 2 * var(--r)),
        radial-gradient(100% 50% at left, #0005 98%, #0000 101%) 100% 100% / var(--r) calc(2 * var(--r)),
        conic-gradient(from 180deg at calc(100% - var(--r)) calc(100% - 2 * var(--r)), #0000 25%, var(--c) 0) 100% 0 / calc(101% - 0.5lh) 100%;
    background-repeat: no-repeat;
}

/* HTML: <div class="ribbon">Your text content</div> */
/* HTML: <div class="ribbon">Your text content</div> */
.approved_ribbon {
    font-size: 18px;
    font-weight: bold;
    color: #fff;
}

.approved_ribbon {
    --r: 0.8em;
    /* control the ribbon shape */

    padding-inline: calc(var(--r) + 0.3em);
    line-height: 1.8;
    clip-path: polygon(0 0, 100% 0, calc(100% - var(--r)) 50%, 100% 100%, 0 100%, var(--r) 50%);
    background: #e65100;
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
