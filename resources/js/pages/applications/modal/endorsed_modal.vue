<template>
    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="bg-surface-0 flex w-[28rem] flex-col rounded p-8">
                <img src="../../../../images/denr_logo.png" class="-mt-20 h-24 w-24 self-center" style="border: 5px solid #fff; border-radius: 70%" />

                <span class="mt-6 mb-2 text-center text-2xl font-bold">{{ message.header }}</span>

                <p class="mb-4 text-center">{{ message.message }}</p>

                <!-- DROPDOWN -->
                <Select
                    v-if="message.showDropdown"
                    v-model="returnTo"
                    :options="message.offices"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select office to return"
                    class="mb-4 w-full"
                />

                <!-- TEXTAREA -->
                <Textarea v-if="message.showTextarea" v-model="remarks" rows="4" class="mb-4 w-full" placeholder="Enter reason here..." autoResize />

                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        label="Confirm"
                        :disabled="(message.showTextarea && !remarks) || (message.showDropdown && !returnTo)"
                        @click="acceptCallback({ remarks: remarks, returnTo: returnTo })"
                        style="background-color: #004d40"
                    />

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

const remarks = ref('');
const returnTo = ref<string | null>(null);

const open = (options: {
  header: string;
  message: string;
  showTextarea?: boolean;
  showDropdown?: boolean;
  offices?: { label: string; value: string }[];
  onConfirm: (data?: { remarks?: string; returnTo?: string | number }) => void;
}) => {
  remarks.value = '';
  returnTo.value = null;

  confirm.require({
    group: 'headless',
    header: options.header,
    message: options.message,
    showTextarea: options.showTextarea ?? false,
    showDropdown: options.showDropdown ?? false,
    offices: options.offices ?? [],
    accept: () => {
      // ✅ Pass both remarks and returnTo to your openDialog
      options.onConfirm({
        remarks: remarks.value,
        returnTo: returnTo.value,
      });
    },
  });
};


defineExpose({ open });
</script>
