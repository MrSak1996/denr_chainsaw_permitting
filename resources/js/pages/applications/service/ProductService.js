import axios from 'axios';

export const ProductService = {
  // Fetch all application details
  
  async getProducts() {
    const response = await axios.get('http://10.201.13.78:8000/api/application-details');
    return response.data.data; // data array from Laravel
  },

 async getApplicationsByStatus(status) {
    const response = await axios.get('http://10.201.13.78:8000/api/applicationStatus', {
      params: { status }
    });
      return {
    applications: response.data.data,
    count: response.data.total_count
  };
  },



  async updateStatus(applicationId, status) {
    try {
      const response = await axios.put(
        `http://10.201.13.78:8000/api/applications/${applicationId}/status`,
        {
          status: status,
        }
      );

      return response.data; // Laravel response
    } catch (error) {
      console.error('Error updating application status:', error);
      throw error;
    }
  },
};
