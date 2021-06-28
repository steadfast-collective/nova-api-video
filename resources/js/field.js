Nova.booting((Vue, router, store) => {
  Vue.component('index-nova-api-video', require('./components/IndexField'))
  Vue.component('detail-nova-api-video', require('./components/DetailField'))
  Vue.component('form-nova-api-video', require('./components/FormField'))
})
