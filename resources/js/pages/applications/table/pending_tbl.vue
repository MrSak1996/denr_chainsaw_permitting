<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import { Send, SquarePen, EyeIcon, Trash, Undo2, Edit2, Info, PrinterCheck } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';
import FileCard from '../forms/file_card.vue';
import { ProductService } from '../service/ProductService';
import { Link, usePage } from '@inertiajs/vue3';
import ReusableConfirmDialog from '../modal/endorsed_modal.vue';

const page = usePage();
const userId = page.props.auth.user.id;
const officeId = page.props.auth.user.office_id;

onMounted(() => {

    ProductService.getProducts(userId).then((data) => (products.value = data));
    // getSignatories();
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

const toast = useToast();
const dt = ref();
const products = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const isloadingSpinner = ref(false);
const showModal = ref(false);
const showCommentsModal = ref(false);
const commentsHistory = ref(false);
const loadingRouting = ref(false);
const loadingComment = ref(false);
const confirmDialogRef = ref<any>(null);


const showFileModal = ref(false);
const selectedFile = ref(null);
const selectedFileToUpdate = ref(null)
const updateFileInput = ref(null)
const expandedRows = ref<Record<number, boolean>>({}) // fix null assignment error

const product = ref({});
const signatories_data = ref({});
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
const applicationDetails = ref(null);
const files = ref([]);
const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};
const openNew = () => {
    product.value = {};
    submitted.value = false;
    productDialog.value = true;
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
// FETCHING APPLICATION DATA
// Author: Mark Kim A. Sacluti
// Date: August 01, 2025
// =================================

const getApplicantFile = async (id) => {
    try {
        const response = await axios.get(`http://10.201.12.154:8000/api/getApplicantFile/${id}`);
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
        const response = await axios.get(`http://10.201.12.154:8000/api/getApplicationDetails/${id}`);
        applicationDetails.value = response.data.data;
        await getApplicantFile(id);
        return response.data.data;
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

const getSignatories = async () => {
    isloadingSpinner.value = true;
    try {
        const response = await axios.get(`http://10.201.12.154:8000/api/getSignatories`);
        signatories_data.value = response.data;
        return response.data.data;
    } catch (error) {
        console.error(error);
    } finally {
        isloadingSpinner.value = false;
    }
};

const viewApplication = (data, type) => {
    router.visit(route('applications.index', {
        application_id: data.id,
        type: data.application_type.toLowerCase()
    }))
}

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

        const response = await axios.put(`http://10.201.12.154:8000/api/updateApplicantDetails/${applicationDetails.value.id}`, editableApplicant);

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

        const response = await axios.put(`http://10.201.12.154:8000/api/updateChainsawInformation/${applicationDetails.value.id}`, editableChainsaw);

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
const endorseApplication = (id: number) => {
    confirmDialogRef.value?.open({
        header: 'Endorse Application?',
        message: 'Please confirm that you want to endorse this application.',
        onConfirm: async () => {
            try {
                await axios.post(route('applications.technical.endorse'), { id }); //endorsed to TSD chief
                toast.add({ severity: 'success', summary: 'Endorsed', detail: 'Application endorsed' });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to endorse' });
            }
        },
    });
};
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
        const response = await axios.put(`http://10.201.12.154:8000/api/updateApplicationStatus/${applicationDetails.value.id}`, {
            status: 4, //ENDORSED Only update the status field
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
    selectedFileToUpdate.value = file
    updateFileInput.value.click()
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

        const response = await axios.post('http://10.201.12.154:8000/api/files/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        // Update file list
        const updatedIndex = files.value.findIndex(f => f.id === selectedFileToUpdate.value.id)
        if (updatedIndex !== -1) {
            files.value[updatedIndex] = response.data.updatedFile
        }

        toast.add({ severity: 'success', summary: 'Successful', detail: 'File updated successfully', life: 3000 });

    } catch (error) {
        console.error(error)
        toast.add({ severity: 'error', summary: 'Successful', detail: 'Failed to update the file.', life: 3000 });

    } finally {
        updateFileInput.value.value = '' // reset file input
        selectedFileToUpdate.value = null
    }
}

// -------------------------
// Row expand/collapse handlers
// -------------------------
// -------------------------
// Row expand/collapse handlers
// -------------------------
const onRowExpand = (event: { originalEvent: Event; data: Customer }) => {
    // Toggle single row without collapsing others
    expandedRows.value = {
        ...expandedRows.value,
        [event.data.id]: true,
    }
}

const onRowCollapse = (event?: { originalEvent: Event; data: Customer }) => {
    if (event) {
        const { [event.data.id]: _, ...rest } = expandedRows.value
        expandedRows.value = rest
    } else {
        expandedRows.value = {}
    }
}

// const generatePdf = async (data) => {


//     const response = await axios.post(`/api/${data.id}/generate-table-pdf`, {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/json' },
//     });

//     const json = await response.json(); // <-- use .json() for debugging
//     console.log(json); // check your Laravel response
// };
const generatePdf = (data) => {
    window.open(`/permit/${data.id}/preview`, '_blank');
};




</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">
        <Toast />
        <div class="rounded-lg bg-white p-4 shadow">

            <DataTable ref="dt" size="small" v-model:expandedRows="expandedRows" :value="products" dataKey="id"
                stripedRows showGridlines :paginator="true" :rows="10" :filters="filters" filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                responsiveLayout="scroll" class="w-full text-sm" @rowExpand="onRowExpand" @rowCollapse="onRowCollapse"
                tableStyle="min-width: 60rem">

                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <!-- <h4 class="m-0 font-semibold">Manage Products</h4> -->
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Search..." />
                        </IconField>
                    </div>
                </template>
                <Column expander style="width: 5rem" />
                <Column header="Action" :exportable="false" style="min-width: 10rem">
                    <template #body="{ data }">
                        <Button class="mr-2" @click="openFileModal(data)" style="background-color: #004D40;">
                            <EyeIcon :size="15" />
                        </Button>


                        <Link
                            class="mr-2 inline-flex items-center justify-center bg-orange-700 hover:bg-orange-600 text-white rounded-md px-3 py-2"
                            :href="route('applications.edit', { id: data.id, type: data.application_type })">
                            <SquarePen :size="16" />
                        </Link>


                        <Button v-if="data.application_status == STATUS_APPROVED_BY_RED" @click="generatePdf(data)"
                            style="background-color: #0D47A1;">
                            <PrinterCheck :size="15" />
                        </Button>

                    </template>
                </Column>
                <Column field="status_title" header="Status" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <div class="flex flex-col items-center">
                            <Tag :value="data.status_title"
                                :severity="data.application_status >= 17 ? 'danger' : 'success'"
                                class="text-center mb-2" />

                            <button v-if="data.status_title === 'Returned to Technical Staff'"
                                class="px-3 py-1 rounded bg-blue-600 text-white text-xs"
                                @click="openCommentModal(data)">
                                View Comments
                            </button>
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



                <template #expansion="slotProps">
                    <div class="p-4">
                        <h5 class="font-semibold mb-2 flex items-center gap-2">
                            <Info />
                            Chainsaw Information
                        </h5>
                        <DataTable size="small" showGridlines :value="[slotProps.data]">
                            <Column field="date_endorsed_tsd_chief" header="Date Endorsed by CENRO" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">dd
                            </Column>
                            <Column field="date_received_penro_chief" header="Date Received by PENRO" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">d
                            </Column>
                            <Column header="Date Received by RO" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column header="Date Received by LPDD" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column field="date_received_fus" header="Date Received by FUS" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column field="application_type" header="Application Type" sortable style="min-width: 5rem"
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }" />


                            <Column header="Applicant Name"
                                :headerStyle="{ backgroundColor: '', color: '#000', fontWeight: 'bold' }">

                                <template #body="slotProps">
                                    {{ slotProps.data.authorized_representative || slotProps.data.applicant_name }}
                                </template>

                            </Column>

                            <Column field="sex" header="Sex" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column v-if="slotProps.data.application_type === 'Individual'"
                                field="applicant_complete_address" header="Address" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column v-else field="company_address" header="Company Address" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>

                            <Column field="permit_no" header="Permit Number" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column field="date_received_red" header="Date Approved/Signed" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column field="permit_validity" header="Date of Expiration" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>
                            <Column field="permit_fee" header="Transaction Fee" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">

                            </Column>

                            <Column field="date_of_payment" header="Date Paid" sortable style="min-width: 4rem"
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }" />
                            <Column header="Remarks" sortable
                                :headerStyle="{ backgroundcolor: '#B0BEC5', color: '#000', fontWeight: 'bold' }">
                            </Column>

                        </DataTable>
                    </div>

                </template>




            </DataTable>
        </div>
        <ReusableConfirmDialog ref="confirmDialogRef"/>

        <Dialog  v-model:visible="showModal" modal header="Application Preview" :style="{ width: '60vw' }">
            <div v-if="isloadingSpinner" class="flex h-40 items-center justify-center">
                <span>Loading...</span>
            </div>

            <div v-else-if="applicationDetails">
                <!-- Action Buttons -->
                <Button class="mr-2 !border-green-600 !bg-green-900 !text-white hover:!bg-green-700"
                    @click="endorseApplication(applicationDetails.id)">
                    <Send class="mr-1" /> Endorsed
                </Button>

                <!-- <Button class="mr-2 !border-red-600 !bg-red-900 !text-white hover:!bg-red-700">
                    <Undo2 class="mr-1" /> Returned
                </Button> -->

                <!-- Applicant Details -->
                <Fieldset legend="Applicant Details" :toggleable="true">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Applicant Details</h3>
                        <Button size="small" class="!border-green-600 !bg-green-900 !text-white hover:!bg-green-700"
                            @click="toggleApplicantEdit">
                            <Edit2 />
                            {{ editState.applicant ? 'Save' : 'Edit' }}
                        </Button>

                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <!-- Application Number -->
                        <div class="flex items-center">
                            <span class="w-32 font-semibold">Application No:</span>
                            <Tag v-if="!editState.applicant" :value="applicationDetails.application_no"
                                severity="success" class="text-center" />
                            <InputText v-else v-model="editableApplicant.application_no" class="w-full" disabled />
                        </div>

                        <!-- Date Applied -->
                        <div class="flex items-center">
                            <span class="w-24 font-semibold">Date Applied:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.date_applied }}</span>
                            <DatePicker v-else v-model="editableApplicant.date_applied" class="w-full" />
                        </div>

                        <!-- Type of Transaction -->
                        <div class="flex items-center">
                            <span class="w-24 font-semibold">Type of Transaction:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.application_type }}</span>
                            <InputText v-else v-model="editableApplicant.application_type" class="w-full" />
                        </div>
                        <div class="flex items-center">
                            <span class="w-24 font-semibold">Classification:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.classification }}</span>
                            <InputText v-else v-model="editableApplicant.classification" class="w-full" />
                        </div>

                        <!-- Company Name -->
                        <div class="flex items-center" v-if="applicationDetails.application_type === 'Individual'">
                            <span class="w-24 font-semibold">Applicant Name:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.first_name }} {{
                                applicationDetails.middle_name }} {{ applicationDetails.last_name }}</span>

                        </div>
                        <div class="flex items-center" v-else>
                            <span class="w-24 font-semibold">Company Name:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.company_name }}</span>
                            <InputText v-else v-model="editableApplicant.company_name" class="w-full" />
                        </div>

                        <div class="flex items-center">
                            <span class="w-24 font-semibold">Email Address:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.email_address }}</span>
                            <InputText v-else v-model="editableApplicant.email_address" class="w-full" />
                        </div>

                        <!-- Authorized Representative -->
                        <div class="flex items-center" v-if="applicationDetails.applicant_type === 'Company'">
                            <span class="w-24 font-semibold">Authorized Representative:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.authorized_representative }}</span>
                            <InputText v-else v-model="editableApplicant.authorized_representative" class="w-full" />
                        </div>

                        <!-- Region (Read-only) -->
                        <div class="flex items-center">
                            <span class="w-24 font-semibold">Region:</span>
                            <span class="w-full text-gray-700"> REGION IV-A (CALABARZON) </span>
                        </div>

                        <!-- Complete Address -->
                        <div class="flex items-center" v-if="applicationDetails.application_type === 'Company'">
                            <span class="w-24 font-semibold">Complete Address:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.company_address }}</span>
                            <Textarea v-else v-model="editableApplicant.company_address" class="w-full" />
                        </div>
                        <div class="flex items-center" v-else>
                            <span class="w-24 font-semibold">Complete Address:</span>
                            <span v-if="!editState.applicant">{{ applicationDetails.applicant_complete_address }}</span>
                            <Textarea v-else v-model="editableApplicant.applicant_complete_address" class="w-full" />
                        </div>
                    </div>
                </Fieldset>

                <!-- Chainsaw Information -->
                <Fieldset legend="Chainsaw Information" :toggleable="true">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Chainsaw Information</h3>
                        <Button size="small" class="!border-green-600 !bg-green-900 !text-white hover:!bg-green-700"
                            @click="toggleChainsawEdit">
                            <Edit2 />
                            {{ editState.chainsaw ? 'Save' : 'Edit' }}
                        </Button>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-24 font-semibold">Chainsaw Permit No:</span>
                            <Tag v-if="!editState.chainsaw" :value="applicationDetails.permit_chainsaw_no"
                                severity="success" class="text-center" />
                            <InputText v-else v-model="editableChainsaw.permit_chainsaw_no" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-32 font-semibold">Permit Validity:</span>
                            <Tag v-if="!editState.chainsaw" :value="applicationDetails.permit_validity"
                                severity="danger" class="text-center" />
                            <DatePicker v-else v-model="editableChainsaw.permit_validity" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Brand:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.brand }}</span>
                            <InputText v-else v-model="editableChainsaw.brand" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Model:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.model }}</span>
                            <InputText v-else v-model="editableChainsaw.model" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Quantity:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.quantity }}</span>
                            <InputText v-else v-model="editableChainsaw.quantity" class="w-full" />
                        </div>
                        <div class="flex">
                            <span class="w-24 font-semibold">Supplier:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.supplier_name }}</span>
                            <InputText v-else v-model="editableChainsaw.supplier_name" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-48 font-semibold">Purpose of Purchase:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.purpose }}</span>
                            <InputText v-else v-model="editableChainsaw.purpose" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Other Details:</span>
                            <span v-if="!editState.chainsaw">{{ applicationDetails.other_details }}</span>
                            <InputText v-else v-model="editableChainsaw.other_details" class="w-full" />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Official Receipt:</span>
                            <Tag :value="applicationDetails.official_receipt" severity="success" class="text-center" />
                            <br />
                        </div>

                        <div class="flex">
                            <span class="w-24 font-semibold">Permit Fee:</span>
                            <span>₱{{ applicationDetails.permit_fee }}.00</span>
                        </div>


                    </div>
                </Fieldset>

                <Fieldset legend="Registration Information">
                    
                    <div class="mt-6 grid grid-cols-1 gap-x-12 gap-y-4 text-sm text-gray-800 md:grid-cols-2">
                        <div class="flex">
                            <span class="w-64 font-semibold">Encoded By:</span>
                            <Tag severity="success">{{ applicationDetails.registered_by }}</Tag>
                            {{ applicationDetails.office_title }} - {{ applicationDetails.role_title }}
                        </div>
                         <div class="flex">
                            <span class="w-64 font-semibold">Registered Date & Time</span>
                            {{ applicationDetails.created_at }}
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



        <Dialog v-model:visible="showCommentsModal" modal header="Comments" :style="{ width: '50vw' }">
            <div class="overflow-x-auto">
                <!-- Loading state -->
                <div v-if="loadingRouting" class="p-4 text-center text-gray-500">Loading comments...</div>
                <table v-else class="min-w-full rounded-lg border border-gray-300 bg-white text-[12px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 text-left">Action Officer</th>
                            <th class="border px-4 py-2 text-left">Comments</th>
                            <th class="border px-4 py-2 text-left">Date Return</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in commentsHistory" :key="index" class="hover:bg-gray-50">
                            <td class="border px-4" style="width: 10rem">
                                <b>{{ item.action_officer }}</b><br />
                                <i>{{ item.sender_role }}</i><br />
                            </td>
                            <td class="border px-4">{{ item.comments }}</td>
                            <td class="border px-4">
                                {{
                                    new Date(item.created_at).toLocaleString('en-PH', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        second: '2-digit',
                                        hour12: true,
                                    })
                                }}
                            </td>

                        </tr>

                    </tbody>
                </table>
                <!-- Table -->

            </div>
        </Dialog>



    </div>
</template>
