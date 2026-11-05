import Alpine from 'alpinejs';
import { orderForm } from './order-form';

Alpine.data('orderForm', orderForm);

window.Alpine = Alpine;
Alpine.start();
