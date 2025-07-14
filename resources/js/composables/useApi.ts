import { ref } from 'vue';
import axios from 'axios';
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
        prov_name,
        getProvinceCode,
    };
}
