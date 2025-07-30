import { reactive } from 'vue';

interface CompanyForm {
    application_no: string;
    application_type: string;
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
    p_place_of_operation_address: string;
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
        p_place_of_operation_address: string;
    };
}

interface IndividualForm {
    application_no: string;
    application_type: string;
    date_applied: string;
    type_of_transaction: string;
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
        application_type: string;
        date_applied: string;
        type_of_transaction: string;
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
        i_complete_address: string;
        p_place_of_operation_address: string;
        p_region: string;
        p_province: string;
        p_city_mun: string;
        p_barangay: string;

        encoded_by: string;
    };
}

interface ChainsawForm {
    application_id: number;
    application_attachment_id: number;
    permit_chainsaw_no: string;
    brand: string;
    model: string;
    quantity: string;
    supplier_name: string;
    supplier_address:string;
    purpose: string;
    permit_validity: string;
    others_details: string;
    mayorDTI: File | null;
    affidavit: File | null;
    otherDocs: File | null;
    permit: File | null;
    updated_at: number | null;
    created_at: number;
}

export function useAppForm() {

    const company_form = reactive<CompanyForm>({
        application_no: '',
        application_type: 'Company',
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
        p_place_of_operation_address: '',
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
            p_place_of_operation_address: '',
        },
    });

    const chainsaw_form = reactive<ChainsawForm>({
        application_id: 0,
        application_attachment_id: 0,
        permit_validity: new Date().toISOString().slice(0, 10), // auto-set to today
        permit_chainsaw_no: '',
        brand: '',
        model: '',
        quantity: '',
        supplier_name: '',
        supplier_address: '',
        purpose: '',
        others_details: '',
        mayorDTI:null,
        affidavit:null,
        otherDocs:null,
        permit:null,
        errors: {},
    });

    const individual_form = reactive<IndividualForm>({
        application_no: '',
        application_type: 'Individual',
        date_applied: new Date().toISOString().slice(0, 10), // auto-set to today
        type_of_transaction: '',
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
            application_type: '',
            date_applied: '',
            type_of_transaction: '',
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

    return { company_form, chainsaw_form, individual_form };
}
