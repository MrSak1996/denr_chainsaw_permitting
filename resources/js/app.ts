import '../css/app.css';
import 'primeicons/primeicons.css';

import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primeuix/themes/aura';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';

import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import RadioButton from 'primevue/radiobutton';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Card from 'primevue/card';
import FileUpload from 'primevue/fileupload';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Rating from 'primevue/rating';
import Tag from 'primevue/tag';
import ToggleButton from 'primevue/togglebutton';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Password from 'primevue/password';
import ConfirmDialog from 'primevue/confirmdialog';
import ConfirmationService from 'primevue/confirmationservice'
import DialogService from 'primevue/dialogservice'

import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { lettersOnlyUppercase, lettersNumbersDashUppercase } from './composables/useUppercaseLettersOnly'


const appName = import.meta.env.VITE_APP_NAME || 'DENR Chainsaw Permitting';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });


        app.directive('letters-only-uppercase', lettersOnlyUppercase);
        app.directive('letters-numbers-dash-uppercase', lettersNumbersDashUppercase);

        app.use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: false || 'none',
                    },
                },
            })
            .use(ZiggyVue)
            .use(ToastService)
            .use(DialogService)
            .use(ConfirmationService)
            .component('Toast', Toast)
            .component('FloatLabel', FloatLabel)
            .component('InputText', InputText)
            .component('Textarea', Textarea)
            .component('InputNumber', InputNumber)
            .component('RadioButton', RadioButton)
            .component('Dialog', Dialog)
            .component('Button', Button)
            .component('Card', Card)
            .component('FileUpload', FileUpload)
            .component('Toolbar', Toolbar)
            .component('IconField', IconField)
            .component('InputIcon', InputIcon)
            .component('DataTable', DataTable)
            .component('Column', Column)
            .component('Rating', Rating)
            .component('Tag', Tag)
            .component('ToggleButton', ToggleButton)
            .component('Select', Select)
            .component('DatePicker', DatePicker)
            .component('Password', Password)
            .component('ConfirmDialog', ConfirmDialog)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Light/dark mode setup
initializeTheme();
