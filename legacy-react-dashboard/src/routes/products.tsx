import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard } from "@/components/admin/PanelCard";
import { Button } from "@/components/ui/button";
import { Plus, Edit, Trash2, Tags } from "lucide-react";
import { products as seedProducts } from "@/lib/zurimart-data";
import { categoriesStore } from "@/lib/categories-store";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";

export const Route = createFileRoute("/products")({
  head: () => ({ meta: [{ title: "Products — ZuriMart Admin" }] }),
  component: ProductsPage,
});

type Product = (typeof seedProducts)[number];

function ProductsPage() {
  const fmt = (n: number) => "₦" + n.toLocaleString();
  const [categories, setCategories] = useState<string[]>(categoriesStore.getActiveNames());
  useEffect(() => {
    const unsub = categoriesStore.subscribe(() => setCategories(categoriesStore.getActiveNames()));
    return () => { unsub; };
  }, []);

  const [products, setProducts] = useState<Product[]>(seedProducts);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({
    id: "",
    name: "",
    category: categoriesStore.getActiveNames()[0] ?? "",
    weight: "",
    price: "",
    stock: "",
  });

  const resetForm = () =>
    setForm({ id: "", name: "", category: categoriesStore.getActiveNames()[0] ?? "", weight: "", price: "", stock: "" });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.id || !form.name) {
      toast.error("SKU and product name are required");
      return;
    }
    if (products.some((p) => p.id.toLowerCase() === form.id.toLowerCase())) {
      toast.error("A product with that SKU already exists");
      return;
    }
    const newProduct: Product = {
      id: form.id.toUpperCase(),
      name: form.name,
      category: form.category,
      weight: Number(form.weight) || 0,
      price: Number(form.price) || 0,
      stock: Number(form.stock) || 0,
    };
    setProducts((prev) => [...prev, newProduct]);
    toast.success(`${newProduct.name} added to catalog`);
    resetForm();
    setOpen(false);
  };

  return (
    <AdminLayout
      title="Product Catalog"
      breadcrumbs={[{ label: "Products" }]}
      actions={
        <>
          <Button asChild size="sm" variant="outline">
            <Link to="/categories"><Tags className="h-4 w-4 mr-1" /> Manage Categories</Link>
          </Button>
          <Button
            size="sm"
            className="bg-primary text-primary-foreground"
            onClick={() => setOpen(true)}
          >
            <Plus className="h-4 w-4 mr-1" /> Add Product
          </Button>
        </>
      }
    >
      <div className="space-y-6">
        {categories.map((cat) => (
          <PanelCard
            key={cat}
            title={`${cat} Varieties`}
            subtitle={`${products.filter((p) => p.category === cat).length} items`}
            noPadding
          >
            <table className="w-full text-sm">
              <thead className="bg-secondary/60 text-muted-foreground text-xs uppercase tracking-wide">
                <tr>
                  <th className="px-4 py-2 text-left">SKU</th>
                  <th className="px-4 py-2 text-left">Product</th>
                  <th className="px-4 py-2 text-right">Weight</th>
                  <th className="px-4 py-2 text-right">Price</th>
                  <th className="px-4 py-2 text-right">Stock</th>
                  <th className="px-4 py-2 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {products
                  .filter((p) => p.category === cat)
                  .map((p) => (
                    <tr key={p.id} className="border-t border-border hover:bg-secondary/40">
                      <td className="px-4 py-2 font-mono text-xs text-primary">{p.id}</td>
                      <td className="px-4 py-2 font-medium">{p.name}</td>
                      <td className="px-4 py-2 text-right text-muted-foreground">{p.weight}g</td>
                      <td className="px-4 py-2 text-right font-semibold">{fmt(p.price)}</td>
                      <td
                        className={`px-4 py-2 text-right font-medium ${
                          p.stock < 150 ? "text-danger" : "text-success"
                        }`}
                      >
                        {p.stock}
                      </td>
                      <td className="px-4 py-2">
                        <div className="flex items-center justify-center gap-1">
                          <Button size="icon" variant="ghost" className="h-7 w-7">
                            <Edit className="h-3.5 w-3.5" />
                          </Button>
                          <Button
                            size="icon"
                            variant="ghost"
                            className="h-7 w-7 text-danger"
                            onClick={() => {
                              setProducts((prev) => prev.filter((x) => x.id !== p.id));
                              toast.success(`${p.name} removed`);
                            }}
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
              </tbody>
            </table>
          </PanelCard>
        ))}
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Add New Product</DialogTitle>
            <DialogDescription>
              Add a new item to the ZuriMart bakery catalog.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="sku">SKU</Label>
                <Input
                  id="sku"
                  placeholder="e.g. SAN-200"
                  value={form.id}
                  onChange={(e) => setForm({ ...form, id: e.target.value })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="category">Category</Label>
                <Select
                  value={form.category}
                  onValueChange={(v) => setForm({ ...form, category: v })}
                >
                  <SelectTrigger id="category">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((c) => (
                      <SelectItem key={c} value={c}>
                        {c}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="name">Product Name</Label>
              <Input
                id="name"
                placeholder="e.g. Sandine Loaf"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
            </div>
            <div className="grid grid-cols-3 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="weight">Weight (g)</Label>
                <Input
                  id="weight"
                  type="number"
                  value={form.weight}
                  onChange={(e) => setForm({ ...form, weight: e.target.value })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="price">Price (₦)</Label>
                <Input
                  id="price"
                  type="number"
                  value={form.price}
                  onChange={(e) => setForm({ ...form, price: e.target.value })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="stock">Stock</Label>
                <Input
                  id="stock"
                  type="number"
                  value={form.stock}
                  onChange={(e) => setForm({ ...form, stock: e.target.value })}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button type="submit">Save Product</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AdminLayout>
  );
}
