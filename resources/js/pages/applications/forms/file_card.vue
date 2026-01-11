<template>
  <div class="file-card">
    <!-- File Thumbnail / Icon -->
    <div class="file-icon">
      <img
        v-if="file.thumbnail"
        :src="file.thumbnail"
        alt="file thumbnail"
        class="file-thumbnail"
      />
      <div v-else class="file-placeholder">
        <span class="file-icon-text">{{ getIcon(getFileExtension(file.name)) }}</span>
      </div>
    </div>

    <!-- File Details -->
    <div class="file-info">
      <p class="file-name">{{ file.name }}</p>
      <p class="file-meta">Size: {{ file.size || '—' }}</p>
      <p class="file-meta">Uploaded: {{ file.dateUploaded || '—' }}</p>
      <p class="file-meta">You opened: {{ file.dateOpened || '—' }}</p>

      <div class="flex gap-2">
      <!-- Preview Button -->
      <button
        class="px-3 py-1 rounded bg-blue-600 text-white text-xs"
        @click="openFile"
      >
        Preview
      </button>

      <!-- Update Button -->
      <button
        class="px-3 py-1 rounded bg-green-600 text-white text-xs"
        @click="updateFile"
      >
        Update
      </button>
    </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  file: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['openPreview', 'updateFile'])

const openFile = () => {
  emit('openPreview', props.file)
}

const updateFile = () => {
  emit('updateFile', props.file)
}
// Extract extension from filename
const getFileExtension = (filename) => {
  if (!filename) return ''
  return filename.split('.').pop().toLowerCase()
}

// Return emoji icon based on file type
const getIcon = (ext) => {
  switch (ext) {
    case 'xls':
    case 'xlsx':
    case 'csv':
      return '📊'
    case 'pdf':
      return '📄'
    case 'doc':
    case 'docx':
      return '📝'
    case 'png':
    case 'jpg':
    case 'jpeg':
    case 'jfif':
    case 'gif':
      return '🖼️'
    case 'zip':
    case 'rar':
      return '🗜️'
    case 'ppt':
    case 'pptx':
      return '📽️'
    default:
      return '📁'
  }
}
</script>

<style scoped>
.file-card {
  display: flex;
  align-items: flex-start;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 1px rgba(0, 0, 0, 0.1);
  padding: 10px;
  transition: all 0.3s ease;
  gap: 16px;
}

.file-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.file-icon {
  width: 70px;
  height: 100px;
  background-color: #f3f3f3;
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
  overflow: hidden;
}

.file-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.file-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.file-icon-text {
  font-size: 40px;
}

.file-info {
  flex: 1;
}

.file-name {
  font-size: 14px;
  font-weight: bold;
}

.file-meta {
  font-size: 12px;
  color: #666;
  margin: 2px 0;
}
</style>
