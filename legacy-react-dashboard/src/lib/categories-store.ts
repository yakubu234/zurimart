// Lightweight in-memory categories store with subscribe/notify
// so the Products page reflects changes made on /categories during the session.

export type Category = {
  id: string;
  name: string;
  description: string;
  active: boolean;
};

let categories: Category[] = [
  { id: "CAT-CORE", name: "Core", description: "Core bakery varieties (Sandine, Chocolate, Fruit...)", active: true },
  { id: "CAT-LOAF", name: "Loaf", description: "Loaf sizes (Mini, Standard, Bigi, Family)", active: true },
  { id: "CAT-SPEC", name: "Specialty", description: "Specialty items like burger bread packs", active: true },
];

const listeners = new Set<() => void>();

export const categoriesStore = {
  getAll: () => categories,
  getActiveNames: () => categories.filter((c) => c.active).map((c) => c.name),
  add: (c: Category) => {
    categories = [...categories, c];
    listeners.forEach((l) => l());
  },
  update: (id: string, patch: Partial<Category>) => {
    categories = categories.map((c) => (c.id === id ? { ...c, ...patch } : c));
    listeners.forEach((l) => l());
  },
  remove: (id: string) => {
    categories = categories.filter((c) => c.id !== id);
    listeners.forEach((l) => l());
  },
  subscribe: (fn: () => void) => {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
};
