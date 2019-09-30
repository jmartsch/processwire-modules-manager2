// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import App from './App';
import vuetify from './plugins/vuetify';
import axios from 'axios';
Vue.prototype.$http = axios;

Vue.config.productionTip = false;
// Vue.component('v-select', vSelect);
// Vue.component('multiselect', Multiselect);
// if (process.env["NODE_ENV"] === "development") {
//         import {allmodules, categories} from "./data";
//     }

/* eslint-disable no-new */
new Vue({
    el: '#app',
    components: { App },
    vuetify,
    template: '<App/>'
});