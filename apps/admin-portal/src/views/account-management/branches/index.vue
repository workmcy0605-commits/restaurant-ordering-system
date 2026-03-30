<script setup lang="ts">
import ApiBrowserPage from '@/components/ifos/api-browser-page.vue';
import { ifosPortalPages } from '@/constants/ifos-portal';
</script>

<template>
  <ApiBrowserPage v-bind="ifosPortalPages.accountManagementBranches" />
</template>
