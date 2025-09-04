<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Folder, Info, ShieldAlert } from 'lucide-vue-next';
import Fieldset from 'primevue/fieldset';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import LoadingSpinner from '../../LoadingSpinner.vue';

/* ================================
 * Types
 * ================================ */
interface ProvinceOption {
  id: string | number;
  name: string;
}

interface CityOption {
  id: string;
  name: string;
  code: string;
}

interface BarangayOption {
  id: string;
  name: string;
}

/* ================================
 * Composables & States
 * ================================ */
const { prov_name, getProvinceCode } = useApi();
const { props } = usePage();
const toast = useToast();

const isLoadingSpinner = ref(false);
const application = props.application;

const cityMunOpts = ref<CityOption[]>([]);
const barangayOpts = ref<BarangayOption[]>([]);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Applications', href: '/applicants/index' }];

const transactionTypeOptions = ref([
  { name: 'G2C', id: '1' },
  { name: 'G2B', id: '2' },
  { name: 'G2G', id: '3' },
]);

/* ================================
 * Utility Functions
 * ================================ */
const fetchCities = async (provinceCode: number | string, targetField: string) => {
  if (!provinceCode) {
    cityMunOpts.value = [];
    return;
  }

  try {
    const { data } = await axios.get(`http://10.201.12.186:8000/api/provinces/${provinceCode}/cities`);

    if (Array.isArray(data)) {
      cityMunOpts.value = data.map((item: any) => ({
        id: item.mun_code,
        name: item.mun_name,
        code: item.geo_code,
      }));

      if (!application[targetField]) {
        application[targetField] = '';
      }
    } else {
      console.error('Unexpected city response:', data);
      cityMunOpts.value = [];
    }
  } catch (error) {
    console.error('Error fetching cities:', error);
    cityMunOpts.value = [];
  }
};

const fetchBarangays = async () => {
  const province = application.operation_province_c;
  const cityMun = application.operation_city_mun_c;
  const region = application.region_code || '04';

  if (!province || !cityMun || !region) {
    barangayOpts.value = [];
    return;
  }

  try {
    const { data } = await axios.get('http://10.201.12.186:8000/api/barangays', {
      params: { reg_code: region, prov_code: province, mun_code: cityMun },
    });

    if (Array.isArray(data)) {
      barangayOpts.value = data.map((item: any) => ({
        id: item.bgy_code,
        name: item.bgy_name,
      }));

      if (!application.operation_brgy_c) {
        application.operation_brgy_c = '';
      }
    } else {
      console.error('Unexpected barangay response:', data);
      barangayOpts.value = [];
    }
  } catch (error) {
    console.error('Error fetching barangays:', error);
    barangayOpts.value = [];
  }
};

const handleFileUpload = (event: Event, field: string) => {
  const target = event.target as HTMLInputElement;
  if (!target.files?.length) return;

  const file = target.files[0];
  application[field] = file;
  application.file_name = file.name;

  console.log(`Uploaded [${field}]:`, file);
};

/* ================================
 * Computed
 * ================================ */
const dateApplied = computed({
  get: () => {
    if (!application.created_at) return '';
    const [day, month, year] = application.created_at.split('/');
    return `${year}-${month}-${day}`;
  },
  set: (value) => {
    if (!value) {
      application.created_at = '';
      return;
    }
    const [year, month, day] = value.split('-');
    application.created_at = `${day}/${month}/${year}`;
  },
});

/* ================================
 * Watchers
 * ================================ */
watch(
  () => application.prov_code,
  (newVal) => fetchCities(newVal, 'city_mun'),
  { immediate: true },
);
watch(
  () => application.operation_province_c,
  (newVal) => fetchCities(newVal, 'operation_city_mun_c'),
  { immediate: true },
);
watch(() => application.operation_city_mun_c, fetchBarangays, { immediate: true });

/* ================================
 * Lifecycle
 * ================================ */
onMounted(async () => {
  await getProvinceCode();
  application.prov_code = Number(application.prov_code);
  application.operation_province_c = Number(application.operation_province_c);
});
</script>

<template>

  <Head title="Chainsaw Permit Application" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center gap-2 text-sm">
        <Folder class="h-5 w-5" />
        <h1 class="text-xl font-semibold">Chainsaw Permit Application Form</h1>
      </div>

      <div class="box">
        <!-- Application Instructions -->
        <h2 class="title flex items-center justify-between gap-2">
          <span class="flex items-center gap-2">
            <Info class="text-primary" />
            Application Instructions
          </span>
        </h2>

        <div class="mt-10 space-y-6">
          <Toast />
          <LoadingSpinner :loading="isLoadingSpinner" />

          <!-- Chainsaw Application -->
          <Fieldset legend="Chainsaw Application">
            <div class="relative">
              <div class="ribbon">DRAFT</div>

              <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                <span>Please complete all fields to proceed with your application for a Permit to Purchase
                  Chainsaw.</span>
              </div>

              <!-- Application Number -->
              <div class="mb-6 grid gap-6 md:grid-cols-3">
                <FloatLabel>
                  <InputText v-model="application.application_no" class="w-full font-bold" disabled />
                  <label>Application No.</label>
                </FloatLabel>
              </div>

              <!-- Date Applied & Transaction Type -->
              <div class="mb-6 grid gap-6 md:grid-cols-3">
                <FloatLabel>
                  <InputText v-model="dateApplied" type="date" class="w-full" />
                  <label>Date Applied</label>
                </FloatLabel>

                <FloatLabel>
                  <Select v-model="application.transaction_type" :options="transactionTypeOptions" optionValue="id"
                    optionLabel="name" class="w-full" />
                  <label>Type of Transaction</label>
                </FloatLabel>
              </div>

              <!-- Company Info -->
              <div class="mb-6 grid gap-6 md:grid-cols-3">
                <FloatLabel class="md:col-span-2">
                  <InputText v-model="application.company_name" class="w-full" />
                  <label>Company / Corporation / Cooperative Name</label>
                </FloatLabel>

                <FloatLabel>
                  <InputText v-model="application.authorized_representative" class="w-full" />
                  <label>Name of Authorized Representative</label>
                </FloatLabel>
              </div>

              <!-- File Uploads -->
              <div class="grid gap-6">
                <div>
                  <label class="mb-2 text-sm font-medium text-gray-700">Upload Application Letter</label>
                  <input type="file" accept=".jpg,.jpeg,.pdf" @change="(e) => handleFileUpload(e, 'request_letter')"
                    class="file-upload" />
                  <div v-if="application.files?.request_letter" class="mt-2 text-sm text-red-600">
                    Selected: <strong>{{ application.files.request_letter }}</strong>
                  </div>
                </div>

                <div>
                  <label class="mb-2 text-sm font-medium text-gray-700">Upload Soc. Certificate / Business
                    Registration</label>
                  <input type="file" accept=".jpg,.jpeg,.pdf" @change="(e) => handleFileUpload(e, 'soc_certificate')"
                    class="file-upload" />
                  <div v-if="application.files?.secretary_certificate" class="mt-2 text-sm text-red-600">
                    Selected: <strong>{{ application.files.secretary_certificate }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </Fieldset>

          <!-- Address Fields -->
          <Fieldset legend="Company Address">
            <div class="grid gap-6 md:grid-cols-4">
              <InputText value="REGION IV-A (CALABARZON)" class="w-full" disabled />

              <Select v-model="application.prov_code" filter :options="prov_name" optionValue="id" optionLabel="name"
                placeholder="Province" class="w-full" />

              <Select v-model="application.city_mun" filter :options="cityMunOpts" optionValue="id" optionLabel="name"
                placeholder="City/Municipality" class="w-full" />

              <Select v-model="application.brgy" filter :options="barangayOpts" optionValue="id" optionLabel="name"
                placeholder="Barangay" class="w-full" />

              <Textarea v-model="application.company_address" rows="6"
                placeholder="Complete Address (Street, Purok, etc.)"
                class="w-full rounded-md border p-2 shadow-sm md:col-span-2" />
            </div>
          </Fieldset>

          <!-- Place of Operation Address -->
          <Fieldset legend="Place of Operation Address">
            <div class="grid gap-6 md:grid-cols-4">
              <InputText value="REGION IV-A (CALABARZON)" class="w-full" disabled />

              <Select v-model="application.operation_province_c" filter :options="prov_name" optionValue="id"
                optionLabel="name" placeholder="Province" class="w-full" />

              <Select v-model="application.operation_city_mun_c" filter :options="cityMunOpts" optionValue="id"
                optionLabel="name" placeholder="City/Municipality" class="w-full" />

              <Select v-model="application.operation_brgy_c" filter :options="barangayOpts" optionValue="id"
                optionLabel="name" placeholder="Barangay" class="w-full" />

              <Textarea v-model="application.operation_address" rows="6"
                placeholder="Complete Address (Street, Purok, etc.)"
                class="w-full rounded-md border p-2 shadow-sm md:col-span-2" />
            </div>
          </Fieldset>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* HTML: */
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
  /* the main color */
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

.box {
  background-color: #fff;
  border-top: 4px solid #00943a;
  margin-bottom: 20px;
  padding: 20px;
  -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
  -o-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
  -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}

.box .title {
  border-bottom: 1px solid #e0e0e0;
  color: #432c0b !important;
  font-weight: bold;
  margin-bottom: 20px;
  margin-top: 0;
  padding-bottom: 10px;
  padding-top: 0;
  text-transform: uppercase;
  font-size: 10pt;
}

/* Base style for ToggleButton - Green (unchecked/default state) */
/* Default state - Green */
/* Base style for ToggleButton - Green (unchecked/default state) */
.p-togglebutton {
  font-weight: 600;
  font-size: 1rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  border: none;
  background-color: #22c55e;
  /* green-500 */
  color: white;
  transition:
    background-color 0.3s ease,
    filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
  filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
  background-color: #15803d !important;
  /* green-700 */
  border-color: #166534;
  color: rgb(0, 0, 0);
}

/* Fix inner white background */
.p-togglebutton.p-togglebutton-checked .p-togglebutton-content {
  background-color: #15803d !important;
  box-shadow: none;
  color: white !important;
}

/* Ensure label and icon are white in all states */
.p-togglebutton .p-togglebutton-icon,
.p-togglebutton .p-togglebutton-label {
  color: white !important;
}
</style>
