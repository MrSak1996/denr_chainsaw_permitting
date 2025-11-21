<script setup lang="ts">
import Fieldset from 'primevue/fieldset'
import FloatLabel from 'primevue/floatlabel'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { ref, computed, watch, onMounted } from 'vue'
import { ShieldAlert } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue'
import axios from 'axios'
import { usePage  } from '@inertiajs/vue3';


const props = defineProps<{
    form: any,
    city_mun: any,
    app_data: Object,
    insertFormData: Function,
    getProvinceCode: Function,
    getApplicationNumber: Function,
    prov_name: Array
}>()

const page = usePage();

// Extract your application data
const application = page.props.application

const govIdOptions = [
    { label: "Philippine Identification (PhilID / ePhilID)", value: "philid" },
    { label: "Passport", value: "passport" },
    { label: "Driver's License (LTO)", value: "drivers_license" },
    { label: "Unified Multi-Purpose ID (UMID)", value: "umid" },
    { label: "Professional Regulation Commission (PRC) ID", value: "prc_id" },
    { label: "Social Security System (SSS) ID", value: "sss_id" },
    { label: "GSIS eCard", value: "gsis_ecard" },
    { label: "Voter's ID / COMELEC Certificate", value: "voters_id" },
    { label: "Postal ID", value: "postal_id" },
    { label: "Senior Citizen ID", value: "senior_id" },
    { label: "Persons with Disability (PWD) ID", value: "pwd_id" },
    { label: "Integrated Bar of the Philippines (IBP) ID", value: "ibp_id" },
    { label: "OWWA / iDOLE Card", value: "owwa_idole" },
];

// Detect edit mode
const getApplicationIdFromUrl = () => {
    const params = new URLSearchParams(window.location.search)
    return params.get('application_id') || params.get('id')
}
const application_id = getApplicationIdFromUrl()

// Main source of data (form or app_data)
const formData = computed(() => application_id ? props.app_data : props.form)
///how can i add application data in this if else

// Dropdown options
const city_mun_opts = ref([])
const barangay_opts = ref([])
const PREFIX = "DENR-IV-A-";

const applicationNo = computed({
    get: () => formData.value.permit_no ?? PREFIX,
    set: (value: string) => {
        if (!value.startsWith(PREFIX)) {
            value = PREFIX + value.replace(PREFIX, "");
        }
        formData.value.permit_no = value;
    },
});


// ------------------------------
// Load initial values for EDIT MODE
// ------------------------------
onMounted(async () => {

    // Load municipality list if province exists
    if (formData.value.i_province) {
        formData.value.i_province = Number(formData.value.i_province)

        const { data: cities } = await axios.get(
            `http://10.201.13.78:8000/api/provinces/${formData.value.i_province}/cities`
        )

        city_mun_opts.value = cities.map(c => ({
            id: c.mun_code,
            name: c.mun_name,
            code: c.geo_code
        }))
    }

    // Auto-select municipality (if given)
    if (formData.value.i_city_mun) {
        formData.value.i_city_mun = Number(formData.value.i_city_mun)

        const { data: barangays } = await axios.get(
            `http://10.201.13.78:8000/api/barangays`,
            {
                params: {
                    reg_code: formData.value.i_region,
                    prov_code: formData.value.i_province,
                    mun_code: formData.value.i_city_mun
                }
            }
        )

        barangay_opts.value = barangays.map(b => ({
            id: b.bgy_code,
            name: b.bgy_name
        }))
    }

    // Auto-select barangay (if given)
    if (formData.value.i_barangay) {
        formData.value.i_barangay = Number(formData.value.i_barangay)
    }
})

// ------------------------------
// WATCH — Province → Load Cities
// ------------------------------
watch(() => formData.value.i_province, async (newProvince) => {
    if (!newProvince) {
        city_mun_opts.value = []
        formData.value.i_city_mun = ''
        barangay_opts.value = []
        formData.value.i_barangay = ''
        return
    }

    const { data } = await axios.get(
        `http://10.201.13.78:8000/api/provinces/${newProvince}/cities`
    )

    city_mun_opts.value = data.map(c => ({
        id: c.mun_code,
        name: c.mun_name,
        code: c.geo_code
    }))

    formData.value.i_city_mun = ''
    barangay_opts.value = []
    formData.value.i_barangay = ''
})

// ------------------------------
// WATCH — City → Load Barangays
// ------------------------------
watch(() => formData.value.i_city_mun, async (newCity) => {
    if (!newCity) {
        barangay_opts.value = []
        formData.value.i_barangay = ''
        return
    }

    const { data } = await axios.get(
        `http://10.201.13.78:8000/api/barangays`,
        {
            params: {
                reg_code: formData.value.i_region,
                prov_code: formData.value.i_province,
                mun_code: newCity
            }
        }
    )

    barangay_opts.value = data.map(b => ({
        id: b.bgy_code,
        name: b.bgy_name
    }))

    formData.value.i_barangay = ''
})

// ------------------------------
// WATCH — Update Geo-Code
// ------------------------------
watch(() => formData.value.i_city_mun, (val) => {
    const selected = city_mun_opts.value.find(x => x.id === val)
    formData.value.geo_code = selected?.code ?? ''
})
</script>



<template>
    <Fieldset legend="Chainsaw Application">
        <div class="relative">
            <div class="ribbon">
                {{ formData.status_title || 'DRAFT'}}
            </div>
            <div class="mb-6 flex items-start gap-2 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                <ShieldAlert class="mt-1 h-5 w-5 text-blue-600" />
                <span> Please complete all fields to proceed with your application. </span>
            </div>

            <!-- Application Number -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <FloatLabel>
                    <InputText id="application_no" v-model="formData.application_no" class="w-full font-bold" disabled
                         />
                    <label for="application_no">Application No.</label>
                </FloatLabel>
                <FloatLabel>
                    <InputText id="permit_no" v-model="applicationNo" class="w-full font-bold" />
                    <label for="permit_no">Permit No.</label>
                </FloatLabel>

            </div>

            <!-- Date & Transaction -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <FloatLabel>
                    <DatePicker id="date_applied" type="date" v-model="formData.date_applied" class="w-full" />
                    <label for="date_applied">Date Applied</label>
                </FloatLabel>

                <FloatLabel>
                    <Select id="type_of_transaction" v-model="formData.type_of_transaction"
                        :options="['G2C', 'G2B', 'G2G']" class="w-full" />
                    <label for="type_of_transaction">Type of Transaction</label>
                </FloatLabel>

                <FloatLabel>
                    <Select id="classification" v-model="formData.classification"
                        :options="['Simple', 'Complex', 'Highly Technical']" class="w-full" />
                    <label for="classification">Classification</label>
                </FloatLabel>

            </div>

            <!-- NAME FIELDS -->
            <div class="grid gap-6 md:grid-cols-3">
                <FloatLabel>
                    <InputText id="surname" v-model="formData.last_name" class="w-full" />
                    <label for="surname">Last Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText id="first_name" v-model="formData.first_name" class="w-full" />
                    <label for="first_name">First Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText id="middlename" v-model="formData.middle_name" class="w-full" />
                    <label for="middlename">Middle Name</label>
                </FloatLabel>

                <FloatLabel>
                    <Select id="sex" v-model="formData.sex" :options="[
                        { label: 'Male', value: 'male' },
                        { label: 'Female', value: 'female' },
                        { label: 'Prefer not to say', value: 'prefer_not_to_say' }
                    ]" optionLabel="label" optionValue="value" class="w-full" />
                    <label for="sex">Sex</label>
                </FloatLabel>

                <FloatLabel>
                    <Select id="gov_id_type" v-model="formData.gov_id_type" :options="govIdOptions" optionLabel="label"
                        optionValue="value" class="w-full" placeholder="Select Government ID" />
                    <label for="gov_id_type">Government ID</label>
                </FloatLabel>


                <FloatLabel>
                    <InputText id="gov_id_number" v-model="formData.gov_id_number" class="w-full" />
                    <label for="gov_id_number">ID Number</label>
                </FloatLabel>
            </div>
        </div>
    </Fieldset>

    <!-- CONTACT INFO -->
    <Fieldset legend="Contact Information">
        <div class="mt-4 grid gap-6 md:grid-cols-4">
            <div>
                <FloatLabel>
                    <InputText id="mobile" v-model="formData.mobile_no" class="w-full" />
                    <label for="mobile">Mobile Number</label>
                </FloatLabel>
                <InputError :message="props.form.errors?.mobile_no" />
            </div>

            <div>
                <FloatLabel>
                    <InputText id="telephone" v-model="formData.telephone_no" class="w-full" />
                    <label for="telephone">Telephone Number</label>
                </FloatLabel>
                <InputError :message="props.form.errors?.telephone_no" />
            </div>

            <div class="md:col-span-2">
                <FloatLabel>
                    <InputText id="email_address" v-model="formData.email_address" class="w-full" />
                    <label for="email_address">Email Address</label>
                </FloatLabel>
                <InputError :message="props.form.errors?.email_address" />
            </div>
        </div>
    </Fieldset>

    <!-- COMPLETE ADDRESS -->
    <Fieldset legend="Complete Address">
        <div class="grid gap-6 md:grid-cols-4">
            <FloatLabel>
                <InputText v-model="formData.i_province" value="Region IV-A (CALABARZON)" class="w-full" disabled />
            </FloatLabel>

            <FloatLabel>
                <Select v-model="formData.i_province" :options="props.prov_name" optionValue="id" optionLabel="name"
                    class="w-full" />


            </FloatLabel>

            <FloatLabel>
                <Select filter v-model="formData.i_city_mun" :options="city_mun_opts" optionValue="id"
                    optionLabel="name" placeholder="Municipality" class="w-full" />

            </FloatLabel>

            <FloatLabel>
                <Select filter v-model="formData.i_barangay" :options="barangay_opts" optionValue="id"
                    optionLabel="name" placeholder="Barangay" class="w-full" />

            </FloatLabel>

            <div class="md:col-span-2">
                <label for="address" class="mb-1 block text-sm font-medium text-gray-700">
                    Complete Address
                </label>
                <Textarea id="address" rows="6" v-model="formData.i_complete_address"
                    placeholder="Complete Address (Street, Purok, etc.)"
                    class="w-[73rem] rounded-md border border-gray-300 p-2 text-sm" />
            </div>
        </div>
    </Fieldset>
</template>
