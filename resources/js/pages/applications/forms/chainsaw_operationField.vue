<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import Fieldset from 'primevue/fieldset';
import InputError from '@/components/InputError.vue';
import { useApi } from '@/composables/useApi';

// ─────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────
const props = defineProps<{
    form: {
        p_region: string;
        p_province: string;
        p_city_mun: string;
        p_barangay: string;
        geo_code: string;
    };
}>();

// API
const { getProvinceCode,prov_name } = useApi();

// Options
const city_mun_opts = ref<{ id: string; name: string; code: string }[]>([]);
const barangay_opts = ref<{ id: string; name: string }[]>([]);

// ─────────────────────────────────────────────
// Watchers
// ─────────────────────────────────────────────

// Update geo_code based on city/municipality
watch(() => props.form.p_city_mun, (newCityMun) => {
    const selected = city_mun_opts.value.find(item => item.id === newCityMun);
    props.form.geo_code = selected?.code ?? '';
});

// Fetch municipalities when province changes
watch(() => props.form.p_province, async (province) => {
    if (!province) return city_mun_opts.value = [];

    try {
        const { data } = await axios.get(`http://10.201.12.186:8000/api/provinces/${province}/cities`);
        if (Array.isArray(data)) {
            city_mun_opts.value = data.map(({ mun_code, mun_name, geo_code }) => ({
                id: mun_code,
                name: mun_name,
                code: geo_code,
            }));
            props.form.p_city_mun = '';
        } else {
            console.error('Unexpected city response format:', data);
            city_mun_opts.value = [];
        }
    } catch (err) {
        console.error('Error fetching municipalities:', err);
        city_mun_opts.value = [];
    }
});

// Fetch barangays when city changes
watch(() => props.form.p_city_mun, async (cityMun) => {
    if (!cityMun) return barangay_opts.value = [];

    try {
        const { data } = await axios.get(`http://10.201.12.186:8000/api/barangays`, {
            params: {
                reg_code: props.form.p_region,
                prov_code: props.form.p_province,
                mun_code: cityMun,
            },
        });

        if (Array.isArray(data)) {
            barangay_opts.value = data.map(({ bgy_code, bgy_name }) => ({
                id: bgy_code,
                name: bgy_name,
            }));
            props.form.p_barangay = '';
        } else {
            console.error('Unexpected barangay response format:', data);
            barangay_opts.value = [];
        }
    } catch (err) {
        console.error('Error fetching barangays:', err);
        barangay_opts.value = [];
    }
});

// ─────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────
onMounted(() => {
    getProvinceCode();
});
</script>




<template>
    <div>
        <Fieldset legend="Place of Operation Address">
            <!-- <div class="mb-4 flex items-center gap-2 text-sm text-blue-600">
                <ShieldAlert class="h-4 w-4" />
                <span>Provide complete and reachable contact details to avoid delays.</span>
            </div> -->

            <div class="grid gap-6 md:grid-cols-4">
                <!-- Region -->
                <div>
                    <FloatLabel>
                        <InputText v-model="props.form.p_region" class="w-full" :disabled="true" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.region" />
                </div>

                <!-- Province -->
                <div>
                    <FloatLabel>
                        <Select filter v-model="props.form.p_province" optionValue="id" :options="prov_name"
                            optionLabel="name" placeholder="Province" class="w-full" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.p_province" />
                </div>

                <!-- Municipality -->
                <div>
                    <FloatLabel>
                        <Select filter v-model="props.form.p_city_mun" :options="city_mun_opts" optionValue="id"
                            optionLabel="name" placeholder="Municipality" class="w-full" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.p_city_mun" />
                </div>

                <!-- Barangay -->
                <div>
                    <FloatLabel>
                        <Select filter v-model="props.form.p_barangay" :options="barangay_opts" optionValue="id"
                            optionLabel="name" placeholder="Barangay" class="w-full" />
                    </FloatLabel>
                    <InputError :message="props.form.errors.p_barangay" />
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Complete Address</label>
                    <Textarea id="address" v-model="props.form.p_place_of_operation_address" rows="6"
                        placeholder="Complete Address (Street, Purok, etc.)"
                        class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                    <InputError :message="props.form.errors.p_place_of_operation_address" />
                </div>
            </div>
        </Fieldset>
    </div>
</template>
