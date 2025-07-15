<script lang="ts" setup>
import Fieldset from 'primevue/fieldset';
import { ShieldAlert } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

const props = defineProps({
    form: Object,
    application_no: String,
});
</script>
<template>
    <div>
        <Fieldset legend="Chainsaw Application">
            <!-- Alert Info -->


            <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                <span> Please complete all fields to proceed with your application for a Permit to Purchase
                    Chainsaw. </span>
            </div>
            <!-- Application No -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <div>
                    <FloatLabel>
                        <InputText id="application_no" v-model="props.form.application_no" class="w-full font-bold"
                            :disabled="true" />
                        <label for="application_no">Application No.</label>
                    </FloatLabel>
                    <InputError />
                </div>
            </div>
            <div class="mb-6 grid gap-6 md:grid-cols-3">

                <!-- Date Applied -->
                <div>
                    <FloatLabel>
                        <InputText id="date_applied" v-model="props.form.date_applied" type="date" class="w-full" />
                        <label for="date_applied">Date Applied</label>
                    </FloatLabel>
                    <InputError />
                </div>
                <div>
                    <FloatLabel>
                        <Select v-model="props.form.type_of_transaction" :options="['G2C', 'G2B', 'G2G']"
                            class="w-full" />
                        <label>Type of Transaction</label>
                    </FloatLabel>
                </div>
            </div>

            <!-- Main Fields -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <!-- Company Name -->
                <div class="md:col-span-2">
                    <FloatLabel>
                        <InputText id="surname" v-model="props.form.company_name" class="w-full" />
                        <label for="surname">Company / Corporation / Cooperative Name</label>
                    </FloatLabel>
                    <InputError />
                </div>

                <!-- Authorized Representative -->
                <div class="md:col-span-1">
                    <FloatLabel>
                        <InputText id="first_name" v-model="props.form.authorized_representative" class="w-full" />
                        <label for="first_name">Name of Authorized Representative</label>
                    </FloatLabel>
                    <InputError />
                </div>
            </div>

            <!-- Additional Fields -->

            <div class="grid gap-6 md:grid-cols-1">
                <!-- Mobile Number -->
                <!-- File Upload -->
                <!-- File Upload -->
                <div class="flex flex-col md:col-span-2">
                    <label for="letterRequest" class="mb-2 text-sm font-medium text-gray-700"> Upload Application
                        Letter / Request Letter </label>
                    <input id="letterRequest" type="file" accept=".jpg,.jpeg,.pdf"
                        class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-1">
                <!-- Mobile Number -->
                <!-- File Upload -->
                <!-- File Upload -->
                <div class="mt-4 flex flex-col md:col-span-2">
                    <label for="letterRequest" class="mb-2 text-sm font-medium text-gray-700">
                        Upload Soc. Certificate / Business Registration
                    </label>
                    <input id="letterRequest" type="file" accept=".jpg,.jpeg,.pdf"
                        class="w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-white p-3 text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-blue-700 hover:bg-gray-50" />
                </div>
            </div>
        </Fieldset>
    </div>
</template>