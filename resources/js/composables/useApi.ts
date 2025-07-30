import axios from 'axios';
import { ref } from 'vue';
export function useApi() {
    interface Option {
        id: number;
        name: string;
    }
    interface Province {
        id: number;
        prov_name: string;
    }

    const prov_name = ref<Option[]>([]);
    const application_no = ref<string>('');

    const getApplicationNumber = async (form:{ application_no: string}, chainsaw_form:{application_no: string}): Promise<void> => {
        try {
            const response = await axios.get<{ application_no: string }>('http://127.0.0.1:8000/api/generateApplicationNumber');
            if (response.data && response.data.application_no) {
                form.application_no = response.data.application_no;
                chainsaw_form.application_no = response.data.application_no;
                
                // Update the form with the new application number
            }  else {
                console.error('Application number not found in response');
            }

        }catch (error) {
            console.error('Error fetching application number:', error);   
        }
    }

    const getProvinceCode = async (): Promise<void> => {
        try {
            const res = await axios.get<Province[]>('http://127.0.0.1:8000/api/getProvinces');
            prov_name.value = res.data.map((item) => ({
                id: Number(item.prov_code),
                name: item.prov_name,
            }));
        } catch (error) {
            console.error('Error fetching provinces:', error);
        }
    };

    


    return {
        application_no,
        prov_name,
        getApplicationNumber,
        getProvinceCode,
    };
}
