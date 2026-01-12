<script lang="ts" setup>
import { useApi } from '@/composables/useApi';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import Select from 'primevue/dropdown';
import Fieldset from 'primevue/fieldset';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

const props = defineProps({
    form: Object,
    app_data: Object,
});

const { prov_name, getProvinceCode } = useApi();
let city_mun_opts = ref<{ id: any; name: any; code: any }[]>([]);
let barangay_opts = ref<{ id: any; name: any }[]>([]);
let geo_code      = ref([]);

// Set geo_code based on selected municipality
watch(
    () => props.form.c_city_mun,
    (newCityMun) => {
        const selectedMunicipality = city_mun_opts.value.find((item) => item.id === newCityMun);
        props.form.geo_code = selectedMunicipality?.code ?? '';
    },
);

// When province changes, fetch its municipalities
watch(
    () => props.form.c_province,
    async (newProvince) => {
        if (newProvince) {
            try {
                const response = await axios.get(`http://192.168.0.117:8000/api/provinces/${newProvince}/cities`);
                if (response.data && Array.isArray(response.data)) {
                    city_mun_opts.value = response.data.map((item) => ({
                        id: item.mun_code,
                        name: item.mun_name,
                        code: item.geo_code,
                    }));
                    props.form.c_city_mun = ''; // Let user select city manually
                } else {
                    console.error('Unexpected response structure:', response);
                    city_mun_opts.value = [];
                }
            } catch (error) {
                console.error('Error fetching cities:', error);
                city_mun_opts.value = [];
            }
        } else {
            city_mun_opts.value = [];
        }
    },
);

// When city/municipality changes, fetch barangays
watch(
    () => props.form.c_city_mun,
    async (newCityMun) => {
        const province = props.form.c_province;
        const region = props.form.c_region;
        if (newCityMun) {
            try {
                const response = await axios.get(`http://192.168.0.117:8000/api/barangays`, {
                    params: {
                        reg_code: region,
                        prov_code: province,
                        mun_code: newCityMun,
                    },
                });
                if (response.data && Array.isArray(response.data)) {
                    barangay_opts.value = response.data.map((item) => ({
                        id: item.bgy_code,
                        name: item.bgy_name,
                    }));
                    props.form.c_barangay = '';
                    
                } else {
                    console.error('Unexpected response structure:', response);
                    barangay_opts.value = [];
                }
            } catch (error) {
                console.error('Error fetching barangays:', error);
                barangay_opts.value = [];
            }
        } else {
            barangay_opts.value = [];
        }
    },
);
const getApplicationIdFromUrl = () => {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('application_id') || urlParams.get('id');
};

const application_id = getApplicationIdFromUrl();
onMounted(() => {
    getProvinceCode();
});
</script>

<template>
    <div>
        <Fieldset legend="Company Address" v-if="(!application_id)">
            <!-- <div class="mb-4 flex items-center gap-2 text-sm text-blue-600">
                <ShieldAlert class="h-4 w-4" />
                <span>Provide complete and reachable contact details to avoid delays.</span>
            </div> -->

            <div class="grid gap-6 md:grid-cols-4">
                <!-- Region -->
                <div>
                    <FloatLabel>
                        <InputText v-model="props.form.c_region" class="w-full" :disabled="true" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Province -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.c_province"
                            optionValue="id"
                            :options="prov_name"
                            optionLabel="name"
                            placeholder="Province"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Municipality -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.c_city_mun"
                            :options="city_mun_opts"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Municipality"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Barangay -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.c_barangay"
                            :options="barangay_opts"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Barangay"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Complete Address</label>
                    <Textarea
                        id="address"
                        v-model="props.form.company_address"
                        rows="6"
                        placeholder="Complete Address (Street, Purok, etc.)"
                        class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"

                    />
                    <InputError :message="props.form.errors.company_address" />
                </div>
            </div>
        </Fieldset>
        <Fieldset legend="Company Address" v-else>
            <!-- <div class="mb-4 flex items-center gap-2 text-sm text-blue-600">
                <ShieldAlert class="h-4 w-4" />
                <span>Provide complete and reachable contact details to avoid delays.</span>
            </div> -->

            <div class="grid gap-6 md:grid-cols-4">
                <!-- Region -->
                <div>
                    <FloatLabel>
                        <InputText v-model="props.form.c_region" class="w-full" :disabled="true" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Province -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.company_c_province" 
                            optionValue="id"
                            :options="prov_name"
                            optionLabel="name"
                            placeholder="Province"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Municipality -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.c_city_mun"
                            :options="city_mun_opts"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Municipality"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Barangay -->
                <div>
                    <FloatLabel>
                        <Select
                            filter
                            v-model="props.form.c_barangay"
                            :options="barangay_opts"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Barangay"
                            class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl"
                        />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Complete Address</label>
                    <Textarea
                        id="address"
                        v-model="props.form.company_address"
                        rows="6"
                        placeholder="Complete Address (Street, Purok, etc.)"
                        class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                    />
                    <InputError :message="props.form.errors.company_address" />
                </div>
            </div>
        </Fieldset>
    </div>
</template>
