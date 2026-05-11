import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard } from "@/components/admin/PanelCard";
import { Button } from "@/components/ui/button";
import { Plus, Edit, Trash2 } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import { categoriesStore, type Category } from "@/lib/categories-store";

export const Route = createFileRoute("/categories")({
  head: () => ({ meta: [{ title: "Categories — ZuriMart Admin" }] }),
  component: CategoriesPage,
});

function CategoriesPage() {
  const [items, setItems] = useState<Category[]>(categoriesStore.getAll());
  const [open, setOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({ name: "", description: "", active: true });

  useEffect(() => {
    const unsub = categoriesStore.subscribe(() => setItems([...categoriesStore.getAll()]));
    return () => { unsub; };
  }, []);

  const resetForm = () => {
    setForm({ name: "", description: "", active: true });
    setEditingId(null);
  };

  const handleOpenAdd = () => {
    resetForm();
    setOpen(true);
  };

  const handleEdit = (c: Category) => {
    setEditingId(c.id);
    setForm({ name: c.name, description: c.description, active: c.active });
    setOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name.trim()) {
      toast.error("Category name is required");
      return;
    }
    if (editingId) {
      categoriesStore.update(editingId, { ...form, name: form.name.trim() });
      toast.success("Category updated");
    } else {
      const id = "CAT-" + form.name.trim().toUpperCase().replace(/\s+/g, "-").slice(0, 12);
      if (items.some((c) => c.id === id || c.name.toLowerCase() === form.name.trim().toLowerCase())) {
        toast.error("That category already exists");
        return;
      }
      categoriesStore.add({ id, name: form.name.trim(), description: form.description, active: form.active });
      toast.success(`${form.name} created`);
    }
    resetForm();
    setOpen(false);
  };

  const handleDelete = (c: Category) => {
    categoriesStore.remove(c.id);
    toast.success(`${c.name} removed`);
  };

  return (
    <AdminLayout
      title="Product Categories"
      breadcrumbs={[{ label: "Products", to: "/products" }, { label: "Categories" }]}
      actions={
        <Button size="sm" className="bg-primary text-primary-foreground" onClick={handleOpenAdd}>
          <Plus className="h-4 w-4 mr-1" /> Add Category
        </Button>
      }
    >
      <PanelCard title="All Categories" subtitle={`${items.length} total`} noPadding>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-2 text-left">ID</th>
                <th className="px-4 py-2 text-left">Name</th>
                <th className="px-4 py-2 text-left">Description</th>
                <th className="px-4 py-2 text-center">Status</th>
                <th className="px-4 py-2 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {items.map((c) => (
                <tr key={c.id} className="border-t border-border hover:bg-secondary/40">
                  <td className="px-4 py-2 font-mono text-xs text-primary">{c.id}</td>
                  <td className="px-4 py-2 font-medium">{c.name}</td>
                  <td className="px-4 py-2 text-muted-foreground">{c.description || "—"}</td>
                  <td className="px-4 py-2 text-center">
                    <span
                      className={`inline-block px-2 py-0.5 rounded text-xs font-medium ${
                        c.active ? "bg-success/15 text-success" : "bg-muted text-muted-foreground"
                      }`}
                    >
                      {c.active ? "Active" : "Inactive"}
                    </span>
                  </td>
                  <td className="px-4 py-2">
                    <div className="flex items-center justify-center gap-1">
                      <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => handleEdit(c)}>
                        <Edit className="h-3.5 w-3.5" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        className="h-7 w-7 text-danger"
                        onClick={() => handleDelete(c)}
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
              {items.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                    No categories yet. Click "Add Category" to create one.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </PanelCard>

      <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) resetForm(); }}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{editingId ? "Edit Category" : "Add New Category"}</DialogTitle>
            <DialogDescription>
              Categories group products together (e.g. Core, Loaf, Specialty).
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="cname">Name</Label>
              <Input
                id="cname"
                placeholder="e.g. Pastries"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="cdesc">Description</Label>
              <Input
                id="cdesc"
                placeholder="Short description"
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
              />
            </div>
            <div className="flex items-center justify-between rounded-md border border-border p-3">
              <div>
                <Label className="text-sm">Active</Label>
                <p className="text-xs text-muted-foreground">Available when adding products</p>
              </div>
              <Switch
                checked={form.active}
                onCheckedChange={(v) => setForm({ ...form, active: v })}
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button type="submit">{editingId ? "Save Changes" : "Create Category"}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AdminLayout>
  );
}
