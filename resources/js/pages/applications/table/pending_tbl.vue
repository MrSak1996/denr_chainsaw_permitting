<script setup lang="ts">
import { FilterMatchMode } from '@primevue/core/api';
import { SquarePen, Trash } from 'lucide-vue-next';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { ProductService } from '../service/ProductService';
import { router } from '@inertiajs/vue3'

onMounted(() => {
    ProductService.getProducts().then((data) => (products.value = data));
});

const toast = useToast();
const dt = ref();
const products = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const isloadingSpinner = ref(false);
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

const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
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
// const editProduct = (prod) => {
//     product.value = { ...prod };
//     productDialog.value = true;
// };
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

// =================================
// FETHING APPLICATION DATA
// Author: Mark Kim A. Sacluti
// Date: August 01, 2024
// =================================
const getApplicationDetails = async () => {
    isloadingSpinner.value = true;
    try {
        const response = await axios.get('http://10.201.12.186:8000/api/application-details');
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
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl p-4">
        <Toolbar>
            <template #end>
                <Button label="Search Filter" icon="pi pi-search" class="mr-2"  />
               
            </template>

          
        </Toolbar>

        <!-- 🛠️ Wrapped DataTable in a div with shadow and padding -->
        <div class="rounded-lg bg-white p-4 shadow">
            <DataTable
                ref="dt"
                size="small"
                v-model:selection="selectedProducts"
                :value="products"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                responsiveLayout="scroll"
                class="w-full text-sm"
            >
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

                <Column field="application_no" header="Application No" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <Tag :value="data.application_no" severity="success" class="text-center" /><br />
                    </template>
                </Column>
                <Column field="application_type" header="Application Type" sortable style="min-width: 5rem" />
                <Column field="permit_chainsaw_no" header="Chainsaw No" sortable style="min-width: 4rem" />
               
                <Column field="created_at" header="Date of Application" sortable style="min-width: 4rem" />
                <Column field="date_of_payment" header="Date Paid" sortable style="min-width: 4rem" />
                <Column field="permit_validity" header="Permit Validity" sortable style="min-width: 4rem" />
                <Column header="Action" :exportable="false" style="min-width: 8rem">
                    <template #body="slotProps">
                        <Button outlined rounded class="mr-2" @click="editProduct(slotProps.data)">
                            <SquarePen />
                        </Button>
                        <Button outlined rounded severity="danger" @click="confirmDeleteProduct(slotProps.data)">
                            <Trash />
                        </Button>
                    </template>
                </Column>
                
            </DataTable>
        </div>

        <Dialog v-model:visible="productDialog" :style="{ width: '450px' }" header="Product Details" :modal="true">
            <div class="flex flex-col gap-6">
                <img
                    v-if="product.image"
                    :src="`https://primefaces.org/cdn/primevue/images/product/${product.image}`"
                    :alt="product.image"
                    class="m-auto block pb-4"
                />
                <div>
                    <label for="name" class="mb-3 block font-bold">Name</label>
                    <InputText id="name" v-model.trim="product.name" required="true" autofocus :invalid="submitted && !product.name" fluid />
                    <small v-if="submitted && !product.name" class="text-red-500">Name is required.</small>
                </div>
                <div>
                    <label for="description" class="mb-3 block font-bold">Description</label>
                    <Textarea id="description" v-model="product.description" required="true" rows="3" cols="20" fluid />
                </div>
                <div>
                    <label for="inventoryStatus" class="mb-3 block font-bold">Inventory Status</label>
                    <Select
                        id="inventoryStatus"
                        v-model="product.inventoryStatus"
                        :options="statuses"
                        optionLabel="label"
                        placeholder="Select a Status"
                        fluid
                    ></Select>
                </div>

                <div>
                    <span class="mb-4 block font-bold">Category</span>
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6 flex items-center gap-2">
                            <RadioButton id="category1" v-model="product.category" name="category" value="Accessories" />
                            <label for="category1">Accessories</label>
                        </div>
                        <div class="col-span-6 flex items-center gap-2">
                            <RadioButton id="category2" v-model="product.category" name="category" value="Clothing" />
                            <label for="category2">Clothing</label>
                        </div>
                        <div class="col-span-6 flex items-center gap-2">
                            <RadioButton id="category3" v-model="product.category" name="category" value="Electronics" />
                            <label for="category3">Electronics</label>
                        </div>
                        <div class="col-span-6 flex items-center gap-2">
                            <RadioButton id="category4" v-model="product.category" name="category" value="Fitness" />
                            <label for="category4">Fitness</label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <label for="price" class="mb-3 block font-bold">Price</label>
                        <InputNumber id="price" v-model="product.price" mode="currency" currency="USD" locale="en-US" fluid />
                    </div>
                    <div class="col-span-6">
                        <label for="quantity" class="mb-3 block font-bold">Quantity</label>
                        <InputNumber id="quantity" v-model="product.quantity" integeronly fluid />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" @click="saveProduct" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteProductDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product"
                    >Are you sure you want to delete <b>{{ product.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteProduct" />
                
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteProductsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product">Are you sure you want to delete the selected products?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedProducts" />
            </template>
        </Dialog>
    </div>
</template>
