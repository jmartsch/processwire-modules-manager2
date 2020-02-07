// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import App from './App';
import vuetify from './plugins/vuetify';
import axios from 'axios';
// import VueAxios from 'vue-axios';
// Vue.use(VueAxios, axios);

Vue.prototype.$http = axios;

Vue.config.productionTip = false;
/* eslint-disable no-new */


const vm = new Vue({
    el: '#app',
    components: { App },
    vuetify,
    template: '<App/>'
});

window.vm = vm;



// window.loadData = function() {
//    vm.loadData();
// };

