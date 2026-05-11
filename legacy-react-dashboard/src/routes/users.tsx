import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { Button } from "@/components/ui/button";
import { Plus, Edit } from "lucide-react";
import { users } from "@/lib/zurimart-data";

export const Route = createFileRoute("/users")({
  head: () => ({ meta: [{ title: "Users & Roles — ZuriMart Admin" }] }),
  component: UsersPage,
});

function UsersPage() {
  return (
    <AdminLayout
      title="Users & Roles"
      breadcrumbs={[{ label: "Users" }]}
      actions={<Button size="sm" className="bg-primary text-primary-foreground"><Plus className="h-4 w-4 mr-1" /> Add User</Button>}
    >
      <PanelCard title="System Users" subtitle="Role-based access control" noPadding>
        <table className="w-full text-sm">
          <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
            <tr>
              <th className="px-4 py-3 text-left">User</th>
              <th className="px-4 py-3 text-left">Email</th>
              <th className="px-4 py-3 text-left">Role</th>
              <th className="px-4 py-3 text-left">Branch</th>
              <th className="px-4 py-3 text-left">Status</th>
              <th className="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id} className="border-t border-border hover:bg-secondary/40">
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    <div className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
                      {u.name.split(" ").map(n => n[0]).slice(0, 2).join("")}
                    </div>
                    <span className="font-medium">{u.name}</span>
                  </div>
                </td>
                <td className="px-4 py-3 text-muted-foreground">{u.email}</td>
                <td className="px-4 py-3"><span className="text-xs font-medium px-2 py-0.5 rounded bg-accent text-accent-foreground">{u.role}</span></td>
                <td className="px-4 py-3 text-muted-foreground">{u.branch}</td>
                <td className="px-4 py-3"><StatusBadge status={u.status} /></td>
                <td className="px-4 py-3 text-center">
                  <Button size="icon" variant="ghost" className="h-7 w-7"><Edit className="h-3.5 w-3.5" /></Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </PanelCard>
    </AdminLayout>
  );
}
