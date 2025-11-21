<template>
  <div class="p-4 bg-gray-50 min-h-screen">
    <!-- List of MOVs -->
    <div
      v-for="file in files"
      :key="file.id"
      class="bg-white shadow-md rounded-xl p-4 mb-4"
    >
      <!-- Header Section -->
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-semibold text-gray-800">
          {{ file.filename }}
        </h2>
        <span
          class="px-3 py-1 rounded-full text-xs font-medium"
          :class="{
            'bg-yellow-100 text-yellow-800': file.status === 'Pending',
            'bg-green-100 text-green-800': file.status === 'Endorsed',
            'bg-red-100 text-red-800': file.status === 'Returned'
          }"
        >
          {{ file.status }}
        </span>
      </div>

      <!-- File Details -->
      <p class="text-sm text-gray-600">
        <strong>Uploaded by:</strong> {{ file.uploaded_by }}
      </p>
      <p class="text-sm text-gray-600 mb-3">
        <strong>Uploaded on:</strong> {{ file.uploaded_at }}
      </p>

      <!-- Remarks Field -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks / Comments:</label>
        <textarea
          v-model="remarks[file.id]"
          placeholder="Enter your remarks here..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-green-200"
          rows="2"
        ></textarea>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between space-x-2">
        <button
          @click="handleAction(file.id, 'Endorsed')"
          class="flex items-center justify-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          <i class="ri-check-line mr-2"></i> Endorse
        </button>

        <button
          @click="handleAction(file.id, 'Returned')"
          class="flex items-center justify-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
        >
          <i class="ri-close-line mr-2"></i> Return
        </button>

        <button
          @click="handleAction(file.id, 'Updated')"
          class="flex items-center justify-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          <i class="ri-refresh-line mr-2"></i> Update
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="files.length === 0" class="text-center text-gray-600 mt-10">
      No files to validate.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// Props
const props = defineProps({
  files: {
    type: Array,
    default: () => []
  }
})

// Emit
const emit = defineEmits(['onAction'])

// State for remarks per file
const remarks = ref({})

// Handle action click
const handleAction = (fileId, actionType) => {
  const comment = remarks.value[fileId] || ''
  emit('onAction', {
    id: fileId,
    action: actionType,
    remarks: comment
  })

  // Optionally clear the remarks after action
  remarks.value[fileId] = ''
}
</script>

<style>
/* Optional icons using Remix Icon or Font Awesome */
</style>
