<template>
    <Fieldset :legend="title" toggleable>
        <div class="overflow-x-auto mt-4">
            <!-- Assessment Table -->
            <table class="min-w-full border border-gray-300 rounded-lg bg-white">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-3 py-2 border">#</th>
                        <th class="px-3 py-2 border">Requirement</th>
                        <th class="px-3 py-2 border">MOV</th>
                        <th class="px-3 py-2 border">File Name</th>
                        <th class="px-3 py-2 border">Comments</th>
                        <th class="px-3 py-2 border">Uploaded Date</th>
                        <th class="px-3 py-2 border">Assessment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in rows" :key="row.checklist_entry_id" class="hover:bg-gray-50">
                        <td class="px-3 py-2 border">{{ index + 1 }}</td>
                        <td class="px-3 py-2 border font-medium">{{ row.requirement }}</td>
                        <td class="px-3 py-2 border">
                            <button v-if="row.file_url" class="px-3 py-1 rounded bg-yellow-500 text-white text-xs"
                                @click="$emit('view-file', row)">
                                <i class="fa fa-eye mr-1"></i> View
                            </button>
                            <span v-else class="text-gray-400 text-xs">No file</span>
                        </td>
                        <td class="px-3 py-2 border">{{ row.file_name || '—' }}</td>
                        <td class="px-2 py-2 border">
                            <Textarea :modelValue="row.remarks" rows="4" class="text-xs w-full"
                                @update:modelValue="$emit('update-remarks', row.checklist_entry_id, $event)" />
                        </td>
                        <td class="px-3 py-2 border">{{ row.uploaded_at || '—' }}</td>
                        <td class="px-3 py-2 border">
                            <div class="flex gap-2">
                                <button @click="$emit('update-assessment', row.checklist_entry_id, 'passed')"
                                    :disabled="row.is_saved" :class="[
                                        'flex items-center px-3 py-1 text-xs rounded',
                                        row.assessment === 'passed' ? 'bg-green-900 text-white' : 'bg-gray-300 text-gray-700'
                                    ]">
                                    <i class="fa fa-check mr-1"></i>
                                    {{ row.assessment === 'passed' ? 'Passed' : 'Pass' }}
                                </button>
                                <button @click="$emit('update-assessment', row.checklist_entry_id, 'failed')"
                                    :disabled="row.is_saved" :class="[
                                        'flex items-center px-3 py-1 text-xs rounded',
                                        row.assessment === 'failed' ? 'bg-red-900 text-white' : 'bg-gray-300 text-gray-700'
                                    ]">
                                    <i class="fa fa-times mr-1"></i>
                                    {{ row.assessment === 'failed' ? 'Failed' : 'Fail' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="text-center py-4 text-gray-500">
                            No requirements found.
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="mt-4 min-w-full border border-gray-300 rounded-lg bg-white">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-3 py-2 border" colspan="2">FOR ONSITE VALIDATION / INSPECTION</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td class="px-3 py-2 border font-medium">Findings</td>
                        <td class="px-3 py-2 border">
                           <textarea
                            :value="onsite.findings"
                            class="form-control w-full"
                            rows="3"
                            placeholder="Enter findings..."
                            @input="$emit('update-onsite', { field: 'findings', value: $event.target.value })"
                            />
                        </td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 border font-medium">Recommendations</td>
                        <td class="px-3 py-2 border">
                            <textarea
                                :value="onsite.recommendations"
                                class="form-control w-full"
                                rows="3"
                                placeholder="Enter recommendations..."
                                @input="$emit('update-onsite', { field: 'recommendations', value: $event.target.value })"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Onsite Validation Card -->

    </Fieldset>

</template>

<script setup>
import Fieldset from 'primevue/fieldset';
defineProps({
    title: String,
    rows: Array,
    onsite: {
        type: Object,
        default: () => ({
            findings: '',
            recommendations: ''
        })
    }
});


defineEmits([
    'view-file',
    'update-remarks',
    'update-assessment',
    'update-onsite'
]);

</script>
