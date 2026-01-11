<template>
    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="bg-surface-0 flex flex-col items-center rounded p-8">
                <img src="../../../../images/denr_logo.png" class="-mt-20 h-25 w-25" />

                <span class="mt-6 mb-2 text-2xl font-bold">{{ message.header }}</span>
                <p class="mb-0">{{ message.message }}</p>
                <Textarea v-model="textareaModel" rows="4" class="w-full" />
                <div class="mt-6 flex gap-2">
                    <Button label="Confirm" @click="acceptCallback" style="background-color: #004d40" />
                    <Button label="Cancel" variant="outlined" @click="rejectCallback" />
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <Toast />
</template>

<script lang="ts" setup>
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { defineExpose, ref } from 'vue';

const confirm = useConfirm();
const toast = useToast();
const textareaModel = ref('');
const isLoading = ref(false);

const open = (options: { header: string; message: string; onConfirm: () => void }) => {
    confirm.require({
        group: 'headless',
        header: options.header,
        message: options.message,
        accept: () => {
            isLoading.value = true;
            try {
                options.onConfirm();
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Something went wrong',
                    life: 3000,
                });
            } finally {
                isLoading.value = false;
            }
        },
        reject: () => {},
    });
};

defineExpose({ open });
</script>
