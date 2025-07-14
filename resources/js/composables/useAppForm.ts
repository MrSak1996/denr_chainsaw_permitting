import { reactive } from 'vue';

interface CompanyForm {
    application_no: string;
    date_applied: string;
    geo_code: string;
    type_of_transaction: string;
    company_name: string;
    company_address: string;
    authorized_representative: string;
    request_letter: File | null;
    soc_certificate: File | null;
    c_region: string;
    c_province: null | string;
    c_city_mun: null | string;
    c_barangay: null | string;
    place_of_operation_address: string;
    p_region: string;
    p_province: string;
    p_city_mun: string;
    p_barangay: string;
    encoded_by: number | null;
    errors: {
        region: string;
        c_province: string;
        c_city_mun: string;
        c_barangay: string;
        address: string;
        p_region: string;
        p_province: string;
        p_city_mun: string;
        p_barangay: string;
    };
}

interface IndividualForm {
    application_no: string;
    date_applied: string;
    geo_code: string;
    last_name: string;
    first_name: string;
    middle_name: string;
    sex: string;
    mobile_no: string;
    telephone_no: string;
    email_address: string;
    gov_id_type: string;
    gov_id_number:string;

    i_region: string;
    i_province: string | null;
    i_city_mun: string | null;
    i_barangay: string | null;
    i_complete_address: string;

    p_place_of_operation_address: string;
    p_region: string;
    p_province: string;
    p_city_mun: string;
    p_barangay: string;

    encoded_by: number | null;

    errors: {
        application_no: string;
        date_applied: string;
        geo_code: string;
        last_name: string;
        first_name: string;
        middle_name: string;
        sex: string;
        mobile_no: string;
        telephone_no: string;
        email_address: string;
        gov_id_type: string;
        gov_id_number: string;
        i_region: string;
        i_province: string;
        i_city_mun: string;
        i_barangay: string;
        i_complete_address: string
        p_place_of_operation_address: string;
        p_region: string;
        p_province: string;
        p_city_mun: string;
        p_barangay: string;

        encoded_by: string;
    };
}

// interface ChainsawForm {
//     chainsaw_brand: string;
//     chainsaw_model: string;
//     quantity: number;
//     supplier_name: string;
//     transaction_type: string;
//     permit_chainsaw_no: string;
//     permit_validity: string;
//     classification: string;
//     purchase_price: number;
//     endorsed_date: string;
//     endorsed_by: number | null;
//     purpose_of_use: number;
//     permit_file: File | null;
// }

export function useAppForm() {
    const company_form = reactive<CompanyForm>({
        application_no: 'DENR-R4A-2023-0001',
        date_applied: new Date().toISOString().slice(0, 10), // auto-set to today
        geo_code: '',
        type_of_transaction: '',
        company_name: '',
        company_address: '',
        authorized_representative: '',
        request_letter: null,
        soc_certificate: null,
        c_region: 'REGION IV-A (CALABARZON)',
        c_province: null,
        c_city_mun: null,
        c_barangay: null,
        place_of_operation_address: '',
        p_region: 'REGION IV-A (CALABARZON)',
        p_province: '',
        p_city_mun: '',
        p_barangay: '',
        encoded_by: null,
        errors: {
            region: '',
            c_province: '',
            c_city_mun: '',
            c_barangay: '',
            address: '',
        },
    });

    const individual_form = reactive<IndividualForm>({
        application_no: 'DENR-R4A-2023-0001',
        date_applied: new Date().toISOString().slice(0, 10), // auto-set to today
        geo_code: '',
        last_name: '',
        first_name: '',
        middle_name: '',
        sex: '',
        mobile_no: '',
        telephone_no: '',
        email_address: '',
        gov_id_type: '',
        gov_id_number: '',
        i_region: 'REGION IV-A (CALABARZON)',
        i_province: null,
        i_city_mun: null,
        i_barangay: null,
        i_complete_address: '',
        p_place_of_operation_address: '',
        p_region: 'REGION IV-A (CALABARZON)',
        p_province: '',
        p_city_mun: '',
        p_barangay: '',
        encoded_by: null,
        errors: {
            application_no: '',
            date_applied: '',
            geo_code: '',
            last_name: '',
            first_name: '',
            middle_name: '',
            mobile_no: '',
            telephone_no: '',
            email_address: '',
            gov_id_type: '',
            gov_id_number: '',
            i_region: '',
            i_province: '',
            i_city_mun: '',
            i_barangay: '',
            i_complete_address: '',
            p_place_of_operation_address: '',
            p_region: '',
            p_province: '',
            p_city_mun: '',
            p_barangay: '',
            encoded_by: '',
        },
    });

    return { company_form, individual_form };
}
