export interface IfosApiAction {
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  path: string;
  description: string;
}

export interface IfosPortalPageConfig {
  title: string;
  description: string;
  endpoint?: string;
  optionsEndpoint?: string;
  defaultParams?: Record<string, string | number | boolean>;
  actions?: IfosApiAction[];
  notes?: string[];
}

const DEFAULT_LIST_PARAMS = {
  items: 25
} as const;

export const ifosPortalPages = {
  accountManagementRoles: {
    title: 'Roles',
    description: 'Browse roles, attached permissions, and role-level form options from the admin API.',
    endpoint: '/api/v1/account-management/roles',
    optionsEndpoint: '/api/v1/account-management/roles/options',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'POST',
        path: '/api/v1/account-management/roles/permissions',
        description: 'Create a new permission entry for the role matrix.'
      }
    ]
  },
  accountManagementAdmins: {
    title: 'Admins',
    description: 'Show web administrators, role assignments, and admin form options.',
    endpoint: '/api/v1/account-management/admins',
    optionsEndpoint: '/api/v1/account-management/admins/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  accountManagementCompanies: {
    title: 'Companies',
    description: 'Show companies, linked payment methods, and company onboarding options.',
    endpoint: '/api/v1/account-management/companies',
    optionsEndpoint: '/api/v1/account-management/companies/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  accountManagementBranches: {
    title: 'Branches',
    description: 'Show branch records and the branch creation/update reference options.',
    endpoint: '/api/v1/account-management/branches',
    optionsEndpoint: '/api/v1/account-management/branches/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  accountManagementRestaurants: {
    title: 'Restaurants',
    description: 'Show restaurants together with the option payload used by the restaurant forms.',
    endpoint: '/api/v1/account-management/restaurants',
    optionsEndpoint: '/api/v1/account-management/restaurants/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  accountManagementUsers: {
    title: 'Users',
    description: 'Show end users and the option payload used to create and maintain them.',
    endpoint: '/api/v1/account-management/users',
    optionsEndpoint: '/api/v1/account-management/users/options',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'GET',
        path: '/api/v1/account-management/users/search-username',
        description: 'Search usernames when checking for uniqueness.'
      },
      {
        method: 'GET',
        path: '/api/v1/account-management/users/payment-method',
        description: 'Fetch payment-method details for user flows.'
      }
    ]
  },
  systemSettingPermissions: {
    title: 'Permissions',
    description: 'Inspect the permission catalog and permission form options.',
    endpoint: '/api/v1/system-setting/permissions',
    optionsEndpoint: '/api/v1/system-setting/permissions/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingLocales: {
    title: 'Locales',
    description: 'Inspect locale records available to the admin system.',
    endpoint: '/api/v1/system-setting/locales',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingSystemSettings: {
    title: 'System Settings',
    description: 'Browse system settings and the options payload used by the settings forms.',
    endpoint: '/api/v1/system-setting/system-settings',
    optionsEndpoint: '/api/v1/system-setting/system-settings/options',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'PATCH',
        path: '/api/v1/system-setting/system-settings/{systemSetting}/toggle-status',
        description: 'Toggle a setting status without performing a full update payload.'
      }
    ]
  },
  systemSettingSystemSettingTypes: {
    title: 'System Setting Types',
    description: 'Inspect the type records that organize system settings.',
    endpoint: '/api/v1/system-setting/system-setting-types',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingSelections: {
    title: 'Selections',
    description: 'Show selection lists and the options payload exposed by the backend.',
    endpoint: '/api/v1/system-setting/selections',
    optionsEndpoint: '/api/v1/system-setting/selections/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingAccessControls: {
    title: 'Access Controls',
    description: 'Show access-control rules and the related options payload.',
    endpoint: '/api/v1/system-setting/access-controls',
    optionsEndpoint: '/api/v1/system-setting/access-controls/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingBackendLocales: {
    title: 'Backend Locales',
    description: 'Inspect backend locale dictionaries and the backend-locales options payload.',
    endpoint: '/api/v1/system-setting/backend-locales',
    optionsEndpoint: '/api/v1/system-setting/backend-locales/options',
    defaultParams: DEFAULT_LIST_PARAMS
  },
  systemSettingAudits: {
    title: 'Audits',
    description: 'Browse audit log entries returned by the admin API.',
    endpoint: '/api/v1/system-setting/audits',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'GET',
        path: '/api/v1/system-setting/audits/{audit}',
        description: 'Open a single audit item in detail.'
      }
    ]
  },
  systemSettingHolidayPreferences: {
    title: 'Holiday Preferences',
    description: 'This part of the API exposes operational toggle endpoints rather than a list resource.',
    actions: [
      {
        method: 'POST',
        path: '/api/v1/system-setting/holiday-preferences/toggle-weekend',
        description: 'Toggle whether weekends are treated as holiday ordering constraints.'
      },
      {
        method: 'POST',
        path: '/api/v1/system-setting/holiday-preferences/toggle-holiday',
        description: 'Toggle whether a holiday date is active or inactive.'
      }
    ],
    notes: ['Operational endpoint set only', 'No list endpoint is exposed for this section']
  },
  menuManagementMenuCategories: {
    title: 'Menu Categories',
    description: 'Browse menu categories and the category option payload used by the admin forms.',
    endpoint: '/api/v1/menu-management/menu-categories',
    optionsEndpoint: '/api/v1/menu-management/menu-categories/options',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'GET',
        path: '/api/v1/menu-management/menu-categories/{menuCategory}/details',
        description: 'Load item-level details for a single menu category.'
      }
    ]
  },
  menuManagementMenuItems: {
    title: 'Menu Items',
    description: 'Browse menu items, menu-item options, and the import/export endpoints exposed by the backend.',
    endpoint: '/api/v1/menu-management/menu-items',
    optionsEndpoint: '/api/v1/menu-management/menu-items/options',
    defaultParams: DEFAULT_LIST_PARAMS,
    actions: [
      {
        method: 'POST',
        path: '/api/v1/menu-management/menu-items/import-store',
        description: 'Bulk import menu items.'
      },
      {
        method: 'GET',
        path: '/api/v1/menu-management/menu-items/export',
        description: 'Export the current menu item dataset.'
      }
    ]
  },
  menuManagementMenuServedDates: {
    title: 'Menu Served Dates',
    description: 'The backend exposes maintenance actions for served dates, but no list endpoint in this route group.',
    actions: [
      {
        method: 'DELETE',
        path: '/api/v1/menu-management/menu-served-dates/delete',
        description: 'Delete served dates by request payload.'
      },
      {
        method: 'DELETE',
        path: '/api/v1/menu-management/menu-served-dates/{menuServedDate}',
        description: 'Delete a single served-date record by id.'
      }
    ],
    notes: ['Maintenance endpoint set only', 'No GET list endpoint is exposed for this section']
  }
} satisfies Record<string, IfosPortalPageConfig>;
