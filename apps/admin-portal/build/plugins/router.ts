import type { RouteMeta } from 'vue-router';
import ElegantVueRouter from '@elegant-router/vue/vite';
import type { RouteKey } from '@elegant-router/types';

export function setupElegantRouter() {
  return ElegantVueRouter({
    layouts: {
      base: 'src/layouts/base-layout/index.vue',
      blank: 'src/layouts/blank-layout/index.vue'
    },
    routePathTransformer(routeName, routePath) {
      const key = routeName as RouteKey;

      if (key === 'login') {
        const modules: UnionKey.LoginModule[] = ['pwd-login', 'code-login', 'register', 'reset-pwd', 'bind-wechat'];

        const moduleReg = modules.join('|');

        return `/login/:module(${moduleReg})?`;
      }

      return routePath;
    },
    onRouteMetaGen(routeName) {
      const key = routeName as RouteKey;

      const constantRoutes: RouteKey[] = ['login', '403', '404', '500'];

      const routeMetaMap: Record<string, Partial<RouteMeta>> = {
        home: {
          icon: 'mdi:monitor-dashboard',
          order: 1
        },
        'account-management': {
          icon: 'mdi:account-cog-outline',
          order: 2
        },
        'account-management_roles': {
          icon: 'mdi:shield-account-outline',
          order: 1
        },
        'account-management_admins': {
          icon: 'mdi:account-tie-outline',
          order: 2
        },
        'account-management_companies': {
          icon: 'mdi:domain',
          order: 3
        },
        'account-management_branches': {
          icon: 'mdi:source-branch',
          order: 4
        },
        'account-management_restaurants': {
          icon: 'mdi:storefront-outline',
          order: 5
        },
        'account-management_users': {
          icon: 'mdi:account-group-outline',
          order: 6
        },
        'system-setting': {
          icon: 'mdi:cog-outline',
          order: 3
        },
        'system-setting_permissions': {
          icon: 'mdi:key-chain-variant',
          order: 1
        },
        'system-setting_locales': {
          icon: 'mdi:translate',
          order: 2
        },
        'system-setting_system-settings': {
          icon: 'mdi:tune-variant',
          order: 3
        },
        'system-setting_system-setting-types': {
          icon: 'mdi:shape-outline',
          order: 4
        },
        'system-setting_selections': {
          icon: 'mdi:format-list-bulleted-square',
          order: 5
        },
        'system-setting_access-controls': {
          icon: 'mdi:lock-outline',
          order: 6
        },
        'system-setting_backend-locales': {
          icon: 'mdi:web',
          order: 7
        },
        'system-setting_audits': {
          icon: 'mdi:file-document-search-outline',
          order: 8
        },
        'system-setting_holiday-preferences': {
          icon: 'mdi:calendar-check-outline',
          order: 9
        },
        'menu-management': {
          icon: 'mdi:food-outline',
          order: 4
        },
        'menu-management_menu-categories': {
          icon: 'mdi:shape-plus-outline',
          order: 1
        },
        'menu-management_menu-items': {
          icon: 'mdi:silverware-fork-knife',
          order: 2
        },
        'menu-management_menu-served-dates': {
          icon: 'mdi:calendar-range-outline',
          order: 3
        }
      };

      const meta: Partial<RouteMeta> = {
        title: key,
        i18nKey: `route.${key}` as App.I18n.I18nKey,
        ...routeMetaMap[key]
      };

      if (constantRoutes.includes(key)) {
        meta.constant = true;
      }

      return meta;
    }
  });
}
