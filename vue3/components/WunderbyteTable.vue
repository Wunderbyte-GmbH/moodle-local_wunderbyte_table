<template>
  <div>
    <notifications width="100%" />
    <div class="row">
      <div class="col-md-3">
        <WunderbyteTableSidebar :sidebardata="sidebar_data" />
      </div>
      <div class="col-md-9">
        <WunderbyteTableTable :tabledata="table_data" />
      </div>
    </div>
  </div>
</template>

<script setup>
  import { onMounted, ref } from 'vue'
  import { useStore } from 'vuex'
  import WunderbyteTableTable from './MainComponents/WunderbyteTableTable.vue'
  import WunderbyteTableSidebar from './MainComponents/WunderbyteTableSidebar.vue'

  const store = useStore();
  const data = ref(null)
  const table_data = ref(null)
  const sidebar_data = ref(null)
  let args = ref({
    encodedtable: "e77647f86baab1f31a015d50f6edf7b1",
    page: 0,
    searchtext: "",
    tdir: null,
    thide: null,
    treset: null,
    tshow: null,
    tsort: null,
    wbtfilter: ""
  })

  // Trigger web services on mount
  onMounted(async() => {
    data.value = await store.dispatch('fetchData', args.value);
    table_data.value = data.value.content
    sidebar_data.value = data.value.filterjson
    console.log(data.value)
  });
</script>

<style scoped>

</style>