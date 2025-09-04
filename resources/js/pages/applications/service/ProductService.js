import axios from 'axios';

export const ProductService = {
    async getProducts() {
        const response = await axios.get('http://10.201.12.186:8000/api/application-details');
        return response.data.data; // data array from Laravel
    }
};
