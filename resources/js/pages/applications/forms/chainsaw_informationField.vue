<script setup lang="ts">
import Fieldset from 'primevue/fieldset';
import { ShieldAlert } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import InputError from '@/components/InputError.vue';

import Select from 'primevue/dropdown';
import DatePicker from 'primevue/calendar';
import { PropType } from 'vue';

defineProps<{
  chainsaws: Array<any>;
      purposeOptions: Array<string>;

}>();

const emit = defineEmits<{
  (e: 'copyAllFields', index: number): void;
  (e: 'handleFileUpload', event: Event, index: number): void;
  (e: 'handlePurposeFileUpload', event: Event, field: string): void;
  (e: 'addChainsaw'): void;
  (e: 'removeChainsaw', index: number): void;
}>();
</script>
<template>
    <div>
        <Fieldset legend="Chainsaw Information">
            <!-- Alert Info -->
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
                        <input type="file" accept=".jpg,.jpeg,.pdf" @change="(event) => handleFileUpload(event, index)"
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
</template>