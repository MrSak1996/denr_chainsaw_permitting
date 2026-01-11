<script lang="ts" setup>
import axios from 'axios';
import { BadgeCheck, LoaderCircle } from 'lucide-vue-next';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const confirm = useConfirm();
const toast = useToast();
const isLoading = ref(false);

const applicationId = ref<number | null>(null);
const open = (id: number) => {
    applicationId.value = id;

    confirm.require({
        group: "headless",
        header: 'Receive Application?',
        message: 'Please confirm if this application has been officially received.',
        accept: () => updateApplicationStatus(2),
        reject: () => {},
    });
};
defineExpose({ open });



const updateApplicationStatus = async (status: number) => {
    isLoading.value = true;

    try {
        const response = await axios.post(route('applications.receivedApplication'), {
            id: applicationId.value,
            status: status,
        });

        toast.add({
            severity: 'success',
            summary: 'Application Received',
            detail: response.data.message || 'Application has been marked as received.',
            life: 3000,
        });
        location.reload();
    } catch (error) {
        console.error(error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update application status.',
            life: 3000,
        });
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="bg-surface-0 dark:bg-surface-900 flex flex-col items-center rounded p-8">
                <div class="text-primary-contrast -mt-20 inline-flex h-25 w-25 items-center justify-center rounded-full bg-[#fff]">
                    <img src="../../../../images/denr_logo.png" class="h-25 w-25" />
                </div>

                <span class="mt-6 mb-2 block text-2xl font-bold">{{ message.header }}</span>
                <p class="mb-0">{{ message.message }}</p>

                <div class="mt-6 flex items-center gap-2">
                    <Button label="Confirm" @click="acceptCallback" style="background-color: #004d40"></Button>
                    <Button label="Cancel" variant="outlined" @click="rejectCallback"></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
    <Toast />
</template>
