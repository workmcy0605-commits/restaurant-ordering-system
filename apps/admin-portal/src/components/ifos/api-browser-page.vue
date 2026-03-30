<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { fetchIfosCollection } from '@/service/api';
import type { IfosApiAction } from '@/constants/ifos-portal';

interface Props {
  title: string;
  description: string;
  endpoint?: string;
  optionsEndpoint?: string;
  defaultParams?: Record<string, string | number | boolean>;
  actions?: IfosApiAction[];
  notes?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  endpoint: undefined,
  optionsEndpoint: undefined,
  defaultParams: () => ({}),
  actions: () => [],
  notes: () => []
});

type ResourceRow = Record<string, unknown>;

const loading = ref(false);
const rows = ref<ResourceRow[]>([]);
const lastLoadedAt = ref('');

const DEFAULT_COLUMN_ORDER = [
  'id',
  'code',
  'name',
  'nickname',
  'status',
  'role_id',
  'company_id',
  'branch_id',
  'restaurant_id',
  'created_at',
  'updated_at'
];

const OMITTED_COLUMNS = new Set(['password', 'remember_token', 'two_factor_secret']);

const resourceColumns = computed<DataTableColumns<ResourceRow>>(() => {
  if (rows.value.length === 0) {
    return [];
  }

  const sampleRows = rows.value.slice(0, 5);
  const discoveredKeys = new Set<string>();

  sampleRows.forEach(row => {
    Object.keys(row).forEach(key => {
      if (!OMITTED_COLUMNS.has(key)) {
        discoveredKeys.add(key);
      }
    });
  });

  const orderedKeys = [
    ...DEFAULT_COLUMN_ORDER.filter(key => discoveredKeys.has(key)),
    ...Array.from(discoveredKeys).filter(key => !DEFAULT_COLUMN_ORDER.includes(key))
  ].slice(0, 8);

  return orderedKeys.map(key => ({
    key,
    title: formatLabel(key),
    minWidth: 160,
    ellipsis: {
      tooltip: true
    },
    render: row => h('span', formatValue(row[key]))
  }));
});

const tableScrollX = computed(() => Math.max(resourceColumns.value.length * 180, 920));

const hasOperationalOnlyContent = computed(() => !props.endpoint && props.actions.length > 0);

async function loadPage() {
  loading.value = true;

  try {
    if (props.endpoint) {
      const { data, error } = await fetchIfosCollection(props.endpoint, props.defaultParams);

      if (!error && Array.isArray(data)) {
        rows.value = data;
      } else {
        rows.value = [];
      }
    }

    lastLoadedAt.value = new Date().toLocaleString();
  } finally {
    loading.value = false;
  }
}

function formatLabel(key: string) {
  return key
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }

  if (Array.isArray(value)) {
    if (value.every(item => typeof item === 'string' || typeof item === 'number')) {
      return value.join(', ');
    }

    return `${value.length} item(s)`;
  }

  if (typeof value === 'object') {
    const objectValue = value as Record<string, unknown>;

    if (typeof objectValue.name === 'string') {
      return objectValue.name;
    }

    if (typeof objectValue.label === 'string') {
      return objectValue.label;
    }

    const compactJson = JSON.stringify(objectValue);

    if (!compactJson) {
      return '-';
    }

    return compactJson.length > 80 ? `${compactJson.slice(0, 77)}...` : compactJson;
  }

  return String(value);
}

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <div class="flex-col gap-16px">
    <NCard>
      <div class="flex-col gap-12px md:flex-row md:items-start md:justify-between">
        <div class="flex-col gap-8px">
          <div class="text-22px font-600 text-primary">{{ props.title }}</div>
          <p class="m-0 text-14px text-#64748b leading-6">
            {{ props.description }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-8px">
          <NTag v-if="props.endpoint" type="success" size="small">
            GET {{ props.endpoint }}
          </NTag>
          <NTag v-if="props.optionsEndpoint" type="info" size="small">
            GET {{ props.optionsEndpoint }}
          </NTag>
          <NButton type="primary" secondary :loading="loading" @click="loadPage">
            Refresh
          </NButton>
        </div>
      </div>

      <div v-if="props.notes.length" class="mt-14px flex flex-wrap gap-8px">
        <NTag v-for="note in props.notes" :key="note" type="warning" round>
          {{ note }}
        </NTag>
      </div>

      <div class="mt-16px grid gap-12px md:grid-cols-3">
        <div class="rd-12px bg-#f8fafc p-14px">
          <div class="text-12px text-#64748b uppercase tracking-1px">Loaded Rows</div>
          <div class="mt-6px text-24px font-700 text-#0f172a">{{ rows.length }}</div>
        </div>
        <div class="rd-12px bg-#f8fafc p-14px">
          <div class="text-12px text-#64748b uppercase tracking-1px">Extra Actions</div>
          <div class="mt-6px text-24px font-700 text-#0f172a">{{ props.actions.length }}</div>
        </div>
        <div class="rd-12px bg-#f8fafc p-14px">
          <div class="text-12px text-#64748b uppercase tracking-1px">Last Loaded</div>
          <div class="mt-6px text-14px font-600 text-#0f172a">
            {{ lastLoadedAt || 'Not loaded yet' }}
          </div>
        </div>
      </div>
    </NCard>

    <NCard v-if="props.endpoint" title="Records">
      <NDataTable
        :columns="resourceColumns"
        :data="rows"
        :loading="loading"
        :pagination="false"
        :scroll-x="tableScrollX"
        striped
      />

      <NEmpty
        v-if="!loading && rows.length === 0"
        class="pt-24px"
        description="No records returned from this endpoint."
      />
    </NCard>

    <NCard v-if="props.actions.length" title="Available API Actions">
      <div class="flex-col gap-12px">
        <div
          v-for="action in props.actions"
          :key="`${action.method}-${action.path}`"
          class="rd-12px border border-solid border-#e2e8f0 bg-#fcfdff p-14px"
        >
          <div class="flex flex-wrap items-center gap-8px">
            <NTag size="small" :type="action.method === 'GET' ? 'success' : action.method === 'DELETE' ? 'error' : 'warning'">
              {{ action.method }}
            </NTag>
            <code class="text-13px text-#0f172a">{{ action.path }}</code>
          </div>
          <p class="mt-8px mb-0 text-13px text-#475569 leading-6">
            {{ action.description }}
          </p>
        </div>
      </div>
    </NCard>

    <NAlert v-if="hasOperationalOnlyContent" type="info" title="Operational Endpoint Group">
      This section exposes task endpoints without a dedicated list API. Use the action references below as the
      integration guide for the backend functions currently available.
    </NAlert>
  </div>
</template>

<style scoped></style>
