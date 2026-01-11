// useOfficeTitle.js
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Office ID to Office Title mapping
const officeMap = {
  1: 'PENRO CAVITE',
  2: 'PENRO LAGUNA',
  3: 'PENRO BATANGAS',
  4: 'PENRO RIZAL',
  5: 'PENRO QUEZON',
  6: 'CENRO Sta. Cruz',
  7: 'CENRO Lipa City',
  8: 'CENRO Calaca',
  9: 'CENRO Calauag',
  10: 'CENRO Catanauan',
  11: 'CENRO Tayabas',
  12: 'CENRO Real',
  13: 'Regional Office',
};

export function useOfficeTitle() {
  const page = usePage();
  const auth = computed(() => page.props.auth);

  // Computed property that returns the office title based on the logged-in user’s office_id
  const officeTitle = computed(() => {
    const office_id = auth.value?.user?.office_id;
    return officeMap[office_id] ?? 'Unknown Office';
  });

  return {
    officeTitle,
  };
}
