// composables/useFormHandler.ts
import axios from 'axios';

export function useFormHandler() {
    const insertFormData = async (url: string, form: any) => {
        const formData = new FormData();

        for (const key in form) {
            if (form[key] !== null && form[key] !== undefined) {
                if (form[key] instanceof File) {
                    formData.append(key, form[key]);
                } else {
                    formData.append(key, form[key]);
                }
            }
        }

        try {
            const response = await axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            return response.data;
        } catch (error) {
            console.error('Form submission error:', error);
            throw error;
        }
    };

    return { insertFormData };
}
