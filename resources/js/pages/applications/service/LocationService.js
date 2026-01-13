// src/services/LocationService.js
import axios from 'axios';

const API_BASE_URL = 'http://10.201.13.88:8000/api'; // Replace with your Laravel backend URL

export const LocationService = {
  // Fetch list of provinces
  async getProvinces() {
    const response = await axios.get(`${API_BASE_URL}/getProvinces`);
    return response.data.data; // Assuming response has { data: [...] }
  },

  // Fetch municipalities by province ID
  async getMunicipalities(provinceId) {
    const response = await axios.get(`${API_BASE_URL}/municipalities`, {
      params: { province_id: provinceId },
    });
    return response.data.data;
  },

  // Fetch barangays by municipality ID
  async getBarangays(municipalityId) {
    const response = await axios.get(`${API_BASE_URL}/barangays`, {
      params: { municipality_id: municipalityId },
    });
    return response.data.data;
  },
};
