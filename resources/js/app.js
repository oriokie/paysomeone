import Alpine from 'alpinejs';
import { orderForm, paymentDesk } from './order-form';

Alpine.data('orderForm', orderForm);
Alpine.data('paymentDesk', paymentDesk);

window.Alpine = Alpine;
Alpine.start();
