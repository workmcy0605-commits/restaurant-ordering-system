import { request } from '../request';

export interface DashboardScope {
  level: 'global' | 'company' | 'branch' | 'restaurant';
  label: string;
  companyId: number | null;
  branchId: number | null;
  restaurantId: number | null;
  contextNote: string;
  latestOrderDate: string | null;
}

export interface DashboardLatestActivity {
  date: string;
  orders: number;
  items: number;
  revenue: number;
}

export interface DashboardSummary {
  restaurantCount: number;
  menuItemCount: number;
  totalOrders: number;
  totalOrderItems: number;
  pendingOrderItems: number;
  completedOrderItems: number;
  totalOrderValue: number;
}

export interface DashboardStatusBreakdownItem {
  status: string;
  total: number;
}

export interface DashboardRestaurantPerformanceItem {
  restaurantId: number | null;
  restaurantName: string;
  orderCount: number;
  itemQuantity: number;
  revenue: number;
}

export interface DashboardTopMenuItem {
  menuItemId: number | null;
  itemName: string;
  restaurantName: string;
  quantity: number;
  revenue: number;
}

export interface DashboardRecentOrderItem {
  orderItemId: number;
  orderId: number;
  itemName: string;
  restaurantName: string;
  status: string;
  price: number;
  orderDate: string | null;
  createdAt: string | null;
}

export interface DashboardOverview {
  scope: DashboardScope;
  latestActivity: DashboardLatestActivity | null;
  summary: DashboardSummary;
  statusBreakdown: DashboardStatusBreakdownItem[];
  restaurantPerformance: DashboardRestaurantPerformanceItem[];
  topMenuItems: DashboardTopMenuItem[];
  recentOrderItems: DashboardRecentOrderItem[];
}

export function fetchDashboardOverview() {
  return request<DashboardOverview>({
    url: '/api/v1/dashboard/overview'
  });
}
