import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Plus, Filter, Download, Check, X } from "lucide-react";
import { orders } from "@/lib/zurimart-data";

export const Route = createFileRoute("/orders")({
  head: () => ({ meta: [{ title: "Orders — ZuriMart Admin" }] }),
  component: OrdersPage,
});

function OrdersPage() {
  const fmt = (n: number) => "₦" + n.toLocaleString();
  return (
    <AdminLayout
      title="Orders Management"
      breadcrumbs={[{ label: "Orders" }]}
      actions={
        <>
          <Button variant="outline" size="sm"><Download className="h-4 w-4 mr-1" /> Export</Button>
          <Button size="sm" className="bg-primary text-primary-foreground"><Plus className="h-4 w-4 mr-1" /> New Order</Button>
        </>
      }
    >
      <PanelCard
        title="All Orders"
        subtitle="Smart routing • Wholesale & Retail tiers"
        actions={
          <>
            <Input placeholder="Search..." className="h-8 w-48" />
            <Button variant="outline" size="sm"><Filter className="h-4 w-4" /></Button>
          </>
        }
        noPadding
      >
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-3 text-left">Order ID</th>
                <th className="px-4 py-3 text-left">Date</th>
                <th className="px-4 py-3 text-left">Customer</th>
                <th className="px-4 py-3 text-left">Demand</th>
                <th className="px-4 py-3 text-right">Units</th>
                <th className="px-4 py-3 text-left">Tagged Branch</th>
                <th className="px-4 py-3 text-left">Tier</th>
                <th className="px-4 py-3 text-right">Total</th>
                <th className="px-4 py-3 text-left">Status</th>
                <th className="px-4 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((o) => (
                <tr key={o.id} className="border-t border-border hover:bg-secondary/40">
                  <td className="px-4 py-3 font-mono text-xs text-primary font-semibold">{o.id}</td>
                  <td className="px-4 py-3 text-muted-foreground">{o.date}</td>
                  <td className="px-4 py-3">
                    <div className="font-medium">{o.customer}</div>
                    <div className="text-xs text-muted-foreground">{o.type}</div>
                  </td>
                  <td className="px-4 py-3"><StatusBadge status={o.demand} /></td>
                  <td className="px-4 py-3 text-right font-medium">{o.units}</td>
                  <td className="px-4 py-3 text-muted-foreground">{o.branch}</td>
                  <td className="px-4 py-3"><StatusBadge status={o.tier} /></td>
                  <td className="px-4 py-3 text-right font-semibold">{fmt(o.total)}</td>
                  <td className="px-4 py-3"><StatusBadge status={o.status} /></td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-center gap-1">
                      <Button size="icon" variant="ghost" className="h-7 w-7 text-success hover:bg-success/10"><Check className="h-4 w-4" /></Button>
                      <Button size="icon" variant="ghost" className="h-7 w-7 text-danger hover:bg-danger/10"><X className="h-4 w-4" /></Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-border flex items-center justify-between text-xs text-muted-foreground">
          <span>Showing 1 to {orders.length} of {orders.length} entries</span>
          <div className="flex gap-1">
            <Button variant="outline" size="sm" disabled>Previous</Button>
            <Button variant="outline" size="sm" className="bg-primary text-primary-foreground border-primary">1</Button>
            <Button variant="outline" size="sm">Next</Button>
          </div>
        </div>
      </PanelCard>
    </AdminLayout>
  );
}
