<script setup>
import { RouterView } from 'vue-router';
import Sidebar from '@/components/Sidebar.vue';
</script>

<template>
   <Sidebar v-if="$route.meta.showSidebar" />
  <RouterView />
</template>
