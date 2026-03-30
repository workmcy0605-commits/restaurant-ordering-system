<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue';
import { NTag } from 'naive-ui';
import type { DataTableColumns, TagProps } from 'naive-ui';
import { useRouter } from 'vue-router';
import {
  fetchDashboardOverview,
  type DashboardOverview,
  type DashboardRecentOrderItem,
  type DashboardStatusBreakdownItem,
  type DashboardTopMenuItem
} from '@/service/api';
import { getRoutePath } from '@/router/elegant/transform';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({
  name: 'Home'
});

interface SummaryCard {
  key: string;
  title: string;
  value: string;
  description: string;
  icon: string;
  accent: string;
}

interface QuickLinkItem {
  key: string;
  title: string;
  description: string;
  route: string;
  icon: string;
}

const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const loadFailed = ref(false);
const dashboard = ref<DashboardOverview>(createEmptyDashboard());

const quickLinks: QuickLinkItem[] = [
  {
    key: 'restaurants',
    title: 'Restaurants',
    description: 'Review restaurant records, status, and onboarding details.',
    route: getRoutePath('account-management_restaurants'),
    icon: 'material-symbols:storefront-rounded'
  },
  {
    key: 'menuCategories',
    title: 'Menu Categories',
    description: 'Check meal windows and category setup for restaurant menus.',
    route: getRoutePath('menu-management_menu-categories'),
    icon: 'material-symbols:grid-view-rounded'
  },
  {
    key: 'menuItems',
    title: 'Menu Items',
    description: 'Open the menu catalog and maintain restaurant item availability.',
    route: getRoutePath('menu-management_menu-items'),
    icon: 'material-symbols:restaurant-menu-rounded'
  },
  {
    key: 'users',
    title: 'Users',
    description: 'Inspect client users, operators, and account assignments.',
    route: getRoutePath('account-management_users'),
    icon: 'material-symbols:groups-2-rounded'
  }
];

const summaryCards = computed<SummaryCard[]>(() => {
  const { scope, summary } = dashboard.value;
  const scopeTitle = scope.level === 'restaurant' ? 'Restaurant' : 'Restaurants';

  return [
    {
      key: 'orders',
      title: 'Customer Orders',
      value: formatNumber(summary.totalOrders),
      description: 'Distinct customer orders currently visible in your dashboard scope.',
      icon: 'material-symbols:shopping-bag-rounded',
      accent: '#2563eb'
    },
    {
      key: 'orderItems',
      title: 'Ordered Meals',
      value: formatNumber(summary.totalOrderItems),
      description: 'Total meal or item quantity generated from restaurant orders.',
      icon: 'material-symbols:receipt-long-rounded',
      accent: '#d97706'
    },
    {
      key: 'orderValue',
      title: 'Order Value',
      value: formatDecimal(summary.totalOrderValue),
      description: 'Combined order-item value excluding cancelled or failed rows.',
      icon: 'material-symbols:payments-rounded',
      accent: '#0f766e'
    },
    {
      key: 'pending',
      title: 'Kitchen Queue',
      value: formatNumber(summary.pendingOrderItems),
      description: 'Open items still moving through creation, approval, or fulfillment.',
      icon: 'material-symbols:kitchen-rounded',
      accent: '#dc2626'
    },
    {
      key: 'restaurants',
      title: scopeTitle,
      value: formatNumber(summary.restaurantCount),
      description: 'Restaurants included in the current company, branch, or restaurant scope.',
      icon: 'material-symbols:storefront-rounded',
      accent: '#7c3aed'
    },
    {
      key: 'menuItems',
      title: 'Menu Items',
      value: formatNumber(summary.menuItemCount),
      description: 'Menu catalog records currently available inside this operational scope.',
      icon: 'material-symbols:menu-book-rounded',
      accent: '#0891b2'
    }
  ];
});

const performanceMax = computed(() => Math.max(...dashboard.value.restaurantPerformance.map(item => item.itemQuantity), 1));
const statusMax = computed(() => Math.max(...dashboard.value.statusBreakdown.map(item => item.total), 1));

const topMenuItemColumns = computed<DataTableColumns<DashboardTopMenuItem>>(() => [
  {
    key: 'item',
    title: 'Item',
    minWidth: 220,
    ellipsis: { tooltip: true },
    render: row =>
      h('div', { class: 'flex-col gap-2px' }, [
        h('span', { class: 'font-600 text-#0f172a' }, row.itemName),
        h('span', { class: 'text-12px text-#64748b' }, row.restaurantName)
      ])
  },
  {
    key: 'quantity',
    title: 'Qty',
    width: 90,
    render: row => h('span', { class: 'font-600 text-#0f172a' }, formatNumber(row.quantity))
  },
  {
    key: 'revenue',
    title: 'Value',
    width: 110,
    render: row => h('span', formatDecimal(row.revenue))
  }
]);

const recentOrderColumns = computed<DataTableColumns<DashboardRecentOrderItem>>(() => [
  {
    key: 'orderId',
    title: 'Order',
    width: 100,
    render: row => h('span', { class: 'font-600 text-#0f172a' }, `#${row.orderId}`)
  },
  {
    key: 'itemName',
    title: 'Item',
    minWidth: 210,
    ellipsis: { tooltip: true },
    render: row =>
      h('div', { class: 'flex-col gap-2px' }, [
        h('span', { class: 'font-600 text-#0f172a' }, row.itemName),
        h('span', { class: 'text-12px text-#64748b' }, row.restaurantName)
      ])
  },
  {
    key: 'status',
    title: 'Status',
    width: 140,
    render: row => renderStatusTag(row.status)
  },
  {
    key: 'price',
    title: 'Value',
    width: 110,
    render: row => h('span', formatDecimal(row.price))
  },
  {
    key: 'createdAt',
    title: 'Created',
    minWidth: 170,
    render: row => h('span', formatDateTime(row.createdAt))
  }
]);

async function loadDashboard() {
  loading.value = true;
  loadFailed.value = false;

  try {
    const { data, error } = await fetchDashboardOverview();

    if (!error && data) {
      dashboard.value = data;
    } else {
      loadFailed.value = true;
    }
  } finally {
    loading.value = false;
  }
}

function createEmptyDashboard(): DashboardOverview {
  return {
    scope: {
      level: 'global',
      label: 'Client Operations',
      companyId: null,
      branchId: null,
      restaurantId: null,
      contextNote: 'Dashboard data will appear here after the first successful load.',
      latestOrderDate: null
    },
    latestActivity: null,
    summary: {
      restaurantCount: 0,
      menuItemCount: 0,
      totalOrders: 0,
      totalOrderItems: 0,
      pendingOrderItems: 0,
      completedOrderItems: 0,
      totalOrderValue: 0
    },
    statusBreakdown: [],
    restaurantPerformance: [],
    topMenuItems: [],
    recentOrderItems: []
  };
}

function openRoute(path: string) {
  router.push(path);
}

function formatNumber(value: number) {
  return new Intl.NumberFormat().format(value);
}

function formatDecimal(value: number) {
  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value);
}

function formatDateTime(value: string | null) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString();
}

function formatScopeLevel(level: DashboardOverview['scope']['level']) {
  return level.charAt(0).toUpperCase() + level.slice(1);
}

function getStatusTagType(status: string): TagProps['type'] {
  const normalizedStatus = status.toUpperCase();

  if (normalizedStatus === 'COMPLETED') {
    return 'success';
  }

  if (['CANCELLED', 'CANCEL', 'FAIL', 'EXPIRED', 'REJECTED'].includes(normalizedStatus)) {
    return 'error';
  }

  if (['READY', 'ON_DELIVERY', 'COOKING', 'APPROVED'].includes(normalizedStatus)) {
    return 'warning';
  }

  if (['CREATED', 'PENDING', 'PENDING_VERIFICATION', 'PROCESSING'].includes(normalizedStatus)) {
    return 'info';
  }

  return 'default';
}

function renderStatusTag(status: string) {
  return h(
    NTag,
    {
      size: 'small',
      round: true,
      type: getStatusTagType(status)
    },
    {
      default: () => status
    }
  );
}

function statusDescription(item: DashboardStatusBreakdownItem) {
  if (item.status === 'COMPLETED') {
    return 'completed';
  }

  if (['CANCELLED', 'CANCEL', 'FAIL', 'EXPIRED', 'REJECTED'].includes(item.status)) {
    return 'ended';
  }

  return 'active';
}

onMounted(async () => {
  await loadDashboard();
});
</script>

<template>
  <NSpin :show="loading">
    <div class="dashboard-shell flex-col gap-16px">
      <NAlert type="info" title="Business Dashboard">
        {{ dashboard.scope.contextNote }}
      </NAlert>

      <NAlert v-if="loadFailed" type="warning" title="Dashboard refresh failed">
        The portal could not refresh the business summary just now. Try the refresh button again.
      </NAlert>

      <NCard :bordered="false" class="dashboard-hero card-wrapper">
        <div class="flex-col gap-18px xl:flex-row xl:items-center xl:justify-between">
          <div class="flex-col gap-12px">
            <div class="flex items-center gap-12px">
              <div class="hero-icon">
                <SvgIcon icon="material-symbols:monitoring-rounded" class="text-28px text-white" />
              </div>
              <div>
                <div class="text-24px font-700 text-#0f172a">
                  {{ dashboard.scope.label }} Dashboard
                </div>
                <div class="text-14px text-#475569">
                  {{ authStore.userInfo.userName || 'Admin' }} is viewing a {{ formatScopeLevel(dashboard.scope.level).toLowerCase() }}
                  operations summary for client-facing business activity.
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-8px">
              <NTag round type="success">Scope: {{ formatScopeLevel(dashboard.scope.level) }}</NTag>
              <NTag round type="info">Completed Items: {{ formatNumber(dashboard.summary.completedOrderItems) }}</NTag>
              <NTag round type="warning">
                Latest Service Day: {{ dashboard.scope.latestOrderDate || 'No orders yet' }}
              </NTag>
            </div>
          </div>

          <div class="grid gap-12px sm:grid-cols-3">
            <div class="hero-stat">
              <div class="text-12px uppercase tracking-1px text-#64748b">Open Queue</div>
              <div class="mt-6px text-24px font-700 text-#0f172a">
                {{ formatNumber(dashboard.summary.pendingOrderItems) }}
              </div>
            </div>
            <div class="hero-stat">
              <div class="text-12px uppercase tracking-1px text-#64748b">Order Value</div>
              <div class="mt-6px text-24px font-700 text-#0f172a">
                {{ formatDecimal(dashboard.summary.totalOrderValue) }}
              </div>
            </div>
            <div class="hero-stat">
              <div class="text-12px uppercase tracking-1px text-#64748b">Action</div>
              <NButton class="mt-6px" type="primary" @click="loadDashboard">
                Refresh Dashboard
              </NButton>
            </div>
          </div>
        </div>
      </NCard>

      <NGrid cols="1 s:2 l:3 xl:6" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi v-for="card in summaryCards" :key="card.key">
          <NCard :bordered="false" class="metric-card card-wrapper">
            <div class="flex items-start justify-between gap-12px">
              <div class="flex-col gap-8px">
                <div class="metric-chip" :style="{ backgroundColor: `${card.accent}18` }">
                  <SvgIcon :icon="card.icon" class="text-22px" :style="{ color: card.accent }" />
                </div>
                <div class="text-14px font-600 text-#334155">{{ card.title }}</div>
                <div class="text-30px font-700 text-#0f172a">{{ card.value }}</div>
              </div>
            </div>

            <p class="mt-12px mb-0 text-13px leading-6 text-#64748b">
              {{ card.description }}
            </p>
          </NCard>
        </NGi>
      </NGrid>

      <NCard v-if="dashboard.latestActivity" :bordered="false" title="Latest Service Day" class="card-wrapper">
        <div class="grid gap-12px md:grid-cols-4">
          <div class="mini-stat">
            <div class="text-12px uppercase tracking-1px text-#64748b">Date</div>
            <div class="mt-6px text-18px font-700 text-#0f172a">{{ dashboard.latestActivity.date }}</div>
          </div>
          <div class="mini-stat">
            <div class="text-12px uppercase tracking-1px text-#64748b">Orders</div>
            <div class="mt-6px text-18px font-700 text-#0f172a">{{ formatNumber(dashboard.latestActivity.orders) }}</div>
          </div>
          <div class="mini-stat">
            <div class="text-12px uppercase tracking-1px text-#64748b">Items</div>
            <div class="mt-6px text-18px font-700 text-#0f172a">{{ formatNumber(dashboard.latestActivity.items) }}</div>
          </div>
          <div class="mini-stat">
            <div class="text-12px uppercase tracking-1px text-#64748b">Value</div>
            <div class="mt-6px text-18px font-700 text-#0f172a">{{ formatDecimal(dashboard.latestActivity.revenue) }}</div>
          </div>
        </div>
      </NCard>

      <NGrid cols="1 xl:2" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi>
          <NCard :bordered="false" title="Restaurant Order Quantity" class="card-wrapper">
            <div v-if="dashboard.restaurantPerformance.length" class="flex-col gap-14px">
              <div v-for="item in dashboard.restaurantPerformance" :key="`${item.restaurantId}-${item.restaurantName}`" class="resource-row">
                <div class="flex items-center justify-between gap-12px">
                  <div>
                    <div class="text-14px font-600 text-#0f172a">{{ item.restaurantName }}</div>
                    <div class="text-12px text-#64748b">
                      {{ formatNumber(item.orderCount) }} orders · {{ formatDecimal(item.revenue) }} value
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-18px font-700 text-#0f172a">{{ formatNumber(item.itemQuantity) }}</div>
                    <div class="text-11px uppercase tracking-1px text-#94a3b8">items</div>
                  </div>
                </div>

                <NProgress
                  class="mt-10px"
                  :show-indicator="false"
                  :percentage="Math.round((item.itemQuantity / performanceMax) * 100)"
                  :height="10"
                  color="#2563eb"
                />
              </div>
            </div>

            <NEmpty v-else description="No restaurant order data available." />
          </NCard>
        </NGi>

        <NGi>
          <NCard :bordered="false" title="Order Status Mix" class="card-wrapper">
            <div v-if="dashboard.statusBreakdown.length" class="flex-col gap-14px">
              <div v-for="item in dashboard.statusBreakdown" :key="item.status" class="resource-row">
                <div class="flex items-center justify-between gap-12px">
                  <div class="flex items-center gap-10px">
                    <NTag round size="small" :type="getStatusTagType(item.status)">
                      {{ item.status }}
                    </NTag>
                    <div class="text-12px text-#64748b">
                      {{ statusDescription(item) }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-18px font-700 text-#0f172a">{{ formatNumber(item.total) }}</div>
                    <div class="text-11px uppercase tracking-1px text-#94a3b8">rows</div>
                  </div>
                </div>

                <NProgress
                  class="mt-10px"
                  :show-indicator="false"
                  :percentage="Math.round((item.total / statusMax) * 100)"
                  :height="10"
                  :color="item.status === 'COMPLETED' ? '#16a34a' : ['CANCELLED', 'CANCEL', 'FAIL', 'EXPIRED', 'REJECTED'].includes(item.status) ? '#dc2626' : '#f59e0b'"
                />
              </div>
            </div>

            <NEmpty v-else description="No order status data available." />
          </NCard>
        </NGi>
      </NGrid>

      <NGrid cols="1 xl:2" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi>
          <NCard :bordered="false" title="Top Ordered Menu Items" class="card-wrapper">
            <NDataTable
              :columns="topMenuItemColumns"
              :data="dashboard.topMenuItems"
              :pagination="false"
              :bordered="false"
              :single-line="false"
              size="small"
            />

            <NEmpty v-if="!dashboard.topMenuItems.length" description="No menu demand data available." class="pt-20px" />
          </NCard>
        </NGi>

        <NGi>
          <NCard :bordered="false" title="Recent Order Flow" class="card-wrapper">
            <NDataTable
              :columns="recentOrderColumns"
              :data="dashboard.recentOrderItems"
              :pagination="false"
              :bordered="false"
              :single-line="false"
              size="small"
            />

            <NEmpty v-if="!dashboard.recentOrderItems.length" description="No recent order items available." class="pt-20px" />
          </NCard>
        </NGi>
      </NGrid>

      <NCard :bordered="false" title="Quick Access" class="card-wrapper">
        <div class="grid gap-12px sm:grid-cols-2 xl:grid-cols-4">
          <button
            v-for="item in quickLinks"
            :key="item.key"
            type="button"
            class="quick-link"
            @click="openRoute(item.route)"
          >
            <div class="quick-link-icon">
              <SvgIcon :icon="item.icon" class="text-22px text-#0f172a" />
            </div>
            <div class="flex-1 text-left">
              <div class="text-15px font-600 text-#0f172a">{{ item.title }}</div>
              <div class="mt-4px text-13px leading-6 text-#64748b">{{ item.description }}</div>
            </div>
          </button>
        </div>
      </NCard>
    </div>
  </NSpin>
</template>

<style scoped>
.dashboard-shell {
  display: flex;
}

.dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.12), transparent 30%),
    radial-gradient(circle at left center, rgba(249, 115, 22, 0.14), transparent 26%),
    linear-gradient(135deg, #fffef7 0%, #f8fbff 48%, #ffffff 100%);
}

.hero-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 18px;
  background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
  box-shadow: 0 18px 30px rgba(37, 99, 235, 0.18);
}

.hero-stat,
.mini-stat {
  min-width: 148px;
  padding: 14px 16px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(10px);
}

.metric-card {
  border: 1px solid rgba(226, 232, 240, 0.78);
}

.metric-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 14px;
}

.resource-row {
  padding: 14px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
}

.quick-link {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  width: 100%;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.92) 0%, rgba(255, 255, 255, 1) 100%);
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease,
    border-color 0.18s ease;
}

.quick-link:hover {
  transform: translateY(-2px);
  border-color: rgba(14, 165, 233, 0.28);
  box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
}

.quick-link-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: #eef6ff;
}
</style>
