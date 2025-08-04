<template>
  <div class="file-card">
    <div class="file-icon">
      <img v-if="file.thumbnail" :src="file.thumbnail" alt="file thumbnail" class="file-thumbnail" />
      <div v-else class="file-placeholder">
        <span>{{ getIcon(file.icon) }}</span>
      </div>
    </div>
    <div class="file-info">
      <p class="file-name">{{ file.name }}</p>
      <span class="file-date">You opened: {{ file.dateOpened }}</span>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  file: {
    type: Object,
    required: true,
  },
})

const getIcon = (type) => {
  switch (type) {
    case 'spreadsheet':
      return '📊';
    case 'app':
      return '🗂️';
    case 'pdf':
      return '📄';
    default:
      return '📁';
  }
}
</script>

<style scoped>
.file-card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  padding: 16px;
  text-align: center;
  transition: all 0.3s ease;
}

.file-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.file-icon {
  background-color: #f3f3f3;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.file-thumbnail {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 8px;
}

.file-placeholder {
  font-size: 30px;
}

.file-info {
  margin-top: 12px;
}

.file-name {
  font-weight: bold;
  margin-bottom: 4px;
}

.file-date {
  font-size: 12px;
  color: #888;
}
</style>
