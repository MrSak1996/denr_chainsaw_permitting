import axios from 'axios';

export const ProductService = {
  async getProducts(userId) {
    const response = await axios.get('http://192.168.2.106:8000/api/application-details', {
      params: { id: userId }
    });
    return response.data.data;
  },



 async getApplicationsByStatus(status,id) {
    const response = await axios.get('http://192.168.2.106:8000/api/applicationStatus', {
      params: { status:status,office_id:id }
    });
      return {
    applications: response.data.data,
    count: response.data.total_count
  };
  },



  async updateStatus(applicationId, status) {
    try {
      const response = await axios.put(
        `http://192.168.2.106:8000/api/applications/${applicationId}/status`,
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
