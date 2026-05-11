import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import { advanceBookings } from "@/lib/zurimart-data";

export const Route = createFileRoute("/bookings")({
  head: () => ({ meta: [{ title: "Advance Bookings — ZuriMart Admin" }] }),
  component: BookingsPage,
});

function BookingsPage() {
  return (
    <AdminLayout
      title="Advance Bookings"
      breadcrumbs={[{ label: "Bookings" }]}
      actions={<Button size="sm" className="bg-primary text-primary-foreground"><Plus className="h-4 w-4 mr-1" /> New Booking</Button>}
    >
      <PanelCard title="Reserved Production Slots" subtitle="Wholesale advance bookings (min. 50 units)" noPadding>
        <table className="w-full text-sm">
          <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
            <tr>
              <th className="px-4 py-3 text-left">Booking ID</th>
              <th className="px-4 py-3 text-left">Customer</th>
              <th className="px-4 py-3 text-right">Units</th>
              <th className="px-4 py-3 text-left">Production Date</th>
              <th className="px-4 py-3 text-left">Assigned Branch</th>
              <th className="px-4 py-3 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            {advanceBookings.map((b) => (
              <tr key={b.id} className="border-t border-border hover:bg-secondary/40">
                <td className="px-4 py-3 font-mono text-xs text-primary font-semibold">{b.id}</td>
                <td className="px-4 py-3 font-medium">{b.customer}</td>
                <td className="px-4 py-3 text-right font-semibold">{b.units}</td>
                <td className="px-4 py-3">{b.date}</td>
                <td className="px-4 py-3 text-muted-foreground">{b.branch}</td>
                <td className="px-4 py-3"><StatusBadge status={b.status} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </PanelCard>
    </AdminLayout>
  );
}
