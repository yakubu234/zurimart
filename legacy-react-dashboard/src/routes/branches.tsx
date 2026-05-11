import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { Switch } from "@/components/ui/switch";
import { Phone, User } from "lucide-react";
import { branches } from "@/lib/zurimart-data";

export const Route = createFileRoute("/branches")({
  head: () => ({ meta: [{ title: "Branches — ZuriMart Admin" }] }),
  component: BranchesPage,
});

function BranchesPage() {
  return (
    <AdminLayout title="Production Branches" breadcrumbs={[{ label: "Branches" }]}>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {branches.map((b) => {
          const pct = Math.round((b.used / b.capacity) * 100);
          const barColor = pct >= 95 ? "bg-danger" : pct >= 75 ? "bg-warning" : "bg-success";
          return (
            <PanelCard
              key={b.id}
              title={b.name}
              subtitle={`Branch ID: ${b.id}`}
              actions={<StatusBadge status={b.status} />}
            >
              <div className="space-y-4">
                <div className="flex items-center gap-4 text-sm">
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span>{b.manager}</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Phone className="h-4 w-4" />
                    <span>{b.phone}</span>
                  </div>
                </div>

                <div>
                  <div className="flex items-center justify-between text-sm mb-1">
                    <span className="font-medium">Oven Capacity</span>
                    <span className="text-muted-foreground">{b.used} / {b.capacity} units ({pct}%)</span>
                  </div>
                  <div className="h-3 rounded-full bg-secondary overflow-hidden">
                    <div className={`h-full ${barColor} transition-all`} style={{ width: `${pct}%` }} />
                  </div>
                </div>

                <div className="flex items-center justify-between p-3 bg-secondary/60 rounded-md">
                  <div>
                    <div className="text-sm font-medium">Accept New Orders</div>
                    <div className="text-xs text-muted-foreground">Toggle to mark branch as Overly Booked</div>
                  </div>
                  <Switch defaultChecked={b.status === "Available"} />
                </div>
              </div>
            </PanelCard>
          );
        })}
      </div>
    </AdminLayout>
  );
}
