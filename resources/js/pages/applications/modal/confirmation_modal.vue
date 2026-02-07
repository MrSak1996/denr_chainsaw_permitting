<script lang="ts" setup>
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from "vue";
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';


const confirm = useConfirm();
const toast = useToast();
const isLoading = ref(false);

const props = defineProps({
    applicationId: {
        type: Number,
        required: true
    }
});

const requireConfirmation = () => {
    confirm.require({
        group: 'headless',
        header: 'Are you sure?',
        message: 'Please confirm to proceed.',
        accept: () => {
            updateApplicationStatus(4); // update to status 1
        },
        reject: () => {
            toast.add({
                severity: 'error',
                summary: 'Cancelled',
                detail: 'Action cancelled.',
                life: 3000
            });
        }
    }); 
};
const updateApplicationStatus = async (status) => {
    isLoading.value = true;

    try {
        const response = await axios.post(route('applications.updateStatus'), {
            id: props.applicationId,
            status
        });

        toast.add({
            severity: 'success',
            summary: 'Status Updated',
            detail: response.data.message || 'Application status has been updated.',
            life: 3000
        });

          router.get(route('applications.pending_application'));

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center p-8 bg-surface-0 dark:bg-surface-900 rounded">
                <div
                    class="rounded-full bg-[#fff] text-primary-contrast inline-flex justify-center items-center h-25 w-25 -mt-20">
                    <img src="../../../../images/denr_logo.png" class="w-25 h-25" />
                </div>
                <span class="font-bold text-2xl block mb-2 mt-6">{{ message.header }}</span>
                <p class="mb-0">{{ message.message }}</p>
                <div class="flex items-center gap-2 mt-6">
                    <Button label="Save" @click="acceptCallback" style="background-color: #004D40;"></Button>
                    <Button label="Cancel" variant="outlined" @click="rejectCallback"></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
    <div class="card flex justify-center">

        <Button @click="requireConfirmation()"
            class="ml-auto px-2 py-0.5 text-[11px] h-9 flex items-center justify-center gap-1 rounded-md"
            :disabled="isLoading" style="background-color: #004D40; border:#004D40">
            <LoaderCircle v-if="isLoading" class="h-3 w-3 animate-spin" />
            <span>Submit Application</span>
        </Button>

    </div>
    <Toast />
</template>