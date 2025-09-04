<template>
  <div class="file-card" @click="handleClick">
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

    <div class="file-info">
      <p class="file-name">File Name:{{ props.file.name }}</p>
      <p class="file-meta">Size: {{ file.size || '—' }}</p>
      <p class="file-meta">Uploaded: {{ file.dateUploaded || '—' }}</p>
      <p class="file-meta">You opened: {{ file.dateOpened || '—' }}</p>
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

const emit = defineEmits(['openPreview'])

const handleClick = () => {
  emit('openPreview', props.file)
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
  align-items: center;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 16px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.file-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.file-icon {
  width: 100px;
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
  border-radius: 8px;
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
  margin-left: 16px;
  flex: 1;
}

.file-name {
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 4px;
}

.file-meta {
  font-size: 12px;
  color: #666;
  margin: 2px 0;
}
</style>
