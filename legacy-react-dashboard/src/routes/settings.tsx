import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard } from "@/components/admin/PanelCard";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Button } from "@/components/ui/button";

export const Route = createFileRoute("/settings")({
  head: () => ({ meta: [{ title: "Settings — ZuriMart Admin" }] }),
  component: SettingsPage,
});

function SettingsPage() {
  return (
    <AdminLayout title="System Settings" breadcrumbs={[{ label: "Settings" }]}>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <PanelCard title="Business Profile">
          <div className="space-y-3">
            <div><Label>Business Name</Label><Input defaultValue="ZuriMart Bakery" /></div>
            <div><Label>HQ Address</Label><Input defaultValue="12 Bakery Lane, Lagos" /></div>
            <div><Label>Support Email</Label><Input defaultValue="support@zurimart.ng" /></div>
            <div><Label>Currency</Label><Input defaultValue="₦ Nigerian Naira" /></div>
          </div>
        </PanelCard>

        <PanelCard title="Pricing Tiers">
          <div className="space-y-3">
            <div><Label>Retail Range (units)</Label><Input defaultValue="1 – 49" /></div>
            <div><Label>Wholesale Threshold</Label><Input defaultValue="50+" /></div>
            <div><Label>Wholesale Discount (%)</Label><Input defaultValue="10" type="number" /></div>
          </div>
        </PanelCard>

        <PanelCard title="Notifications">
          {[
            ["Real-time push to branch managers", true],
            ["WhatsApp API alerts", true],
            ["Email order receipts", true],
            ["SMS for advance bookings", false],
          ].map(([label, val]) => (
            <div key={label as string} className="flex items-center justify-between py-2 border-b border-border last:border-0">
              <span className="text-sm">{label}</span>
              <Switch defaultChecked={val as boolean} />
            </div>
          ))}
        </PanelCard>

        <PanelCard title="Concurrency & Locking">
          <div className="space-y-3 text-sm">
            <div className="flex items-center justify-between"><span>Lock branch capacity on accept</span><Switch defaultChecked /></div>
            <div className="flex items-center justify-between"><span>Auto-reroute on rejection</span><Switch defaultChecked /></div>
            <div className="flex items-center justify-between"><span>Hide overly-booked branches</span><Switch defaultChecked /></div>
          </div>
        </PanelCard>
      </div>
      <div className="flex justify-end mt-6 gap-2">
        <Button variant="outline">Cancel</Button>
        <Button className="bg-primary text-primary-foreground">Save Changes</Button>
      </div>
    </AdminLayout>
  );
}
