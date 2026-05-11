import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { StatCard } from "@/components/admin/StatCard";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { DollarSign, ShoppingCart, Clock, Building2, AlertTriangle, TrendingUp } from "lucide-react";
import { stats, orders, branches, salesTrend, branchPerformance } from "@/lib/zurimart-data";
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  BarChart,
  Bar,
  Legend,
} from "recharts";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "Dashboard — ZuriMart Bakery Admin" },
      { name: "description", content: "Centralized dashboard for ZuriMart bakery production, orders and branches." },
    ],
  }),
  component: Dashboard,
});

function Dashboard() {
  const fmt = (n: number) => "₦" + n.toLocaleString();

  return (
    <AdminLayout title="Dashboard" breadcrumbs={[{ label: "Dashboard" }]}>
      {/* Stat row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <StatCard label="Total Revenue" value={fmt(stats.totalRevenue)} icon={DollarSign} variant="success" sublabel="This month" />
        <StatCard label="Total Orders" value={stats.totalOrders} icon={ShoppingCart} variant="info" sublabel="All branches" />
        <StatCard label="Pending Orders" value={stats.pendingOrders} icon={Clock} variant="warning" sublabel="Awaiting acceptance" />
        <StatCard label="Active Branches" value={stats.activeBranches} icon={Building2} variant="primary" sublabel="Production sites" />
        <StatCard label="Low Stock" value={stats.lowStockItems} icon={AlertTriangle} variant="danger" sublabel="Items < 150 units" />
        <StatCard label="Wholesale %" value={stats.wholesaleShare + "%"} icon={TrendingUp} variant="info" sublabel="Of total volume" />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <PanelCard title="Sales Trend (last 7 days)" subtitle="Retail vs Wholesale units" className="lg:col-span-2">
          <div className="h-72">
            <ResponsiveContainer>
              <AreaChart data={salesTrend}>
                <defs>
                  <linearGradient id="r" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="oklch(0.65 0.15 230)" stopOpacity={0.5} />
                    <stop offset="100%" stopColor="oklch(0.65 0.15 230)" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="w" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="oklch(0.62 0.16 55)" stopOpacity={0.5} />
                    <stop offset="100%" stopColor="oklch(0.62 0.16 55)" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.9 0.01 250)" />
                <XAxis dataKey="day" stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <YAxis stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <Tooltip contentStyle={{ borderRadius: 6, border: "1px solid oklch(0.9 0.01 250)" }} />
                <Legend />
                <Area type="monotone" dataKey="retail" stroke="oklch(0.65 0.15 230)" fill="url(#r)" strokeWidth={2} />
                <Area type="monotone" dataKey="wholesale" stroke="oklch(0.62 0.16 55)" fill="url(#w)" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </PanelCard>

        <PanelCard title="Branch Performance" subtitle="Orders fulfilled this week">
          <div className="h-72">
            <ResponsiveContainer>
              <BarChart data={branchPerformance}>
                <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.9 0.01 250)" />
                <XAxis dataKey="name" stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <YAxis stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <Tooltip contentStyle={{ borderRadius: 6, border: "1px solid oklch(0.9 0.01 250)" }} />
                <Bar dataKey="orders" fill="oklch(0.62 0.16 55)" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </PanelCard>
      </div>

      {/* Recent activity */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <PanelCard title="Recent Orders" className="lg:col-span-2" noPadding>
          <table className="w-full text-sm">
            <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-2 text-left">Order ID</th>
                <th className="px-4 py-2 text-left">Customer</th>
                <th className="px-4 py-2 text-left">Branch</th>
                <th className="px-4 py-2 text-left">Tier</th>
                <th className="px-4 py-2 text-right">Total</th>
                <th className="px-4 py-2 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              {orders.slice(0, 6).map((o) => (
                <tr key={o.id} className="border-t border-border hover:bg-secondary/40">
                  <td className="px-4 py-2 font-mono text-xs text-primary">{o.id}</td>
                  <td className="px-4 py-2">{o.customer}</td>
                  <td className="px-4 py-2 text-muted-foreground">{o.branch}</td>
                  <td className="px-4 py-2"><StatusBadge status={o.tier} /></td>
                  <td className="px-4 py-2 text-right font-medium">{fmt(o.total)}</td>
                  <td className="px-4 py-2"><StatusBadge status={o.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </PanelCard>

        <PanelCard title="Production Branch Status" subtitle="Real-time oven capacity">
          <div className="space-y-4">
            {branches.map((b) => {
              const pct = Math.round((b.used / b.capacity) * 100);
              const barColor = pct >= 95 ? "bg-danger" : pct >= 75 ? "bg-warning" : "bg-success";
              return (
                <div key={b.id}>
                  <div className="flex items-center justify-between mb-1">
                    <div>
                      <div className="text-sm font-medium">{b.name}</div>
                      <div className="text-xs text-muted-foreground">Mgr: {b.manager}</div>
                    </div>
                    <StatusBadge status={b.status} />
                  </div>
                  <div className="h-2 rounded-full bg-secondary overflow-hidden">
                    <div className={`h-full ${barColor}`} style={{ width: `${pct}%` }} />
                  </div>
                  <div className="flex justify-between text-xs text-muted-foreground mt-1">
                    <span>{b.used} / {b.capacity} units</span>
                    <span>{pct}%</span>
                  </div>
                </div>
              );
            })}
          </div>
        </PanelCard>
      </div>
    </AdminLayout>
  );
}
