// Static demo data for ZuriMart Bakery admin

export const products = [
  { id: "P-001", name: "Sandine 600g", category: "Core", weight: 600, price: 800, stock: 420 },
  { id: "P-002", name: "Chocolate 600g", category: "Core", weight: 600, price: 1000, stock: 310 },
  { id: "P-003", name: "Fruit 600g", category: "Core", weight: 600, price: 950, stock: 180 },
  { id: "P-004", name: "Coconut 600g", category: "Core", weight: 600, price: 950, stock: 220 },
  { id: "P-005", name: "Wheat 600g", category: "Core", weight: 600, price: 1100, stock: 260 },
  { id: "P-006", name: "Mini Loaf 400g", category: "Loaf", weight: 400, price: 600, stock: 540 },
  { id: "P-007", name: "Standard 850g", category: "Loaf", weight: 850, price: 1200, stock: 360 },
  { id: "P-008", name: "Bigi 1.2kg", category: "Loaf", weight: 1200, price: 1700, stock: 140 },
  { id: "P-009", name: "Family Size 1.2kg", category: "Loaf", weight: 1200, price: 1800, stock: 120 },
  { id: "P-010", name: "Burger Bread (Pack of 6)", category: "Specialty", weight: 480, price: 900, stock: 95 },
  { id: "P-011", name: "Burger Bread (Pack of 12)", category: "Specialty", weight: 960, price: 1700, stock: 60 },
];

export const branches = [
  { id: "BR-IKJ", name: "Ikeja Production", manager: "Tunde Bello", capacity: 1200, used: 870, status: "Available" as const, phone: "+234 801 111 2233" },
  { id: "BR-LKK", name: "Lekki Production", manager: "Amaka Eze", capacity: 900, used: 900, status: "Overly Booked" as const, phone: "+234 802 333 4455" },
  { id: "BR-SRL", name: "Surulere Production", manager: "Femi Ade", capacity: 1000, used: 540, status: "Available" as const, phone: "+234 803 555 6677" },
  { id: "BR-ABJ", name: "Abuja Production", manager: "Hauwa Musa", capacity: 1500, used: 1100, status: "Available" as const, phone: "+234 805 777 8899" },
];

export const orders = [
  { id: "ORD-10241", customer: "Mama Ngozi Minimart", type: "Outlet", demand: "Retail", units: 35, branch: "Ikeja Production", status: "Accepted", tier: "Retail", total: 28000, date: "2026-04-30" },
  { id: "ORD-10242", customer: "BigBite Wholesalers", type: "Wholesale", demand: "Wholesale", units: 220, branch: "Abuja Production", status: "Pending", tier: "Wholesale", total: 198000, date: "2026-04-30" },
  { id: "ORD-10243", customer: "Walk-in Retailer", type: "Public", demand: "Retail", units: 4, branch: "Surulere Production", status: "Completed", tier: "Retail", total: 4400, date: "2026-04-29" },
  { id: "ORD-10244", customer: "Lekki Outlet", type: "Outlet", demand: "Wholesale", units: 80, branch: "Lekki Production", status: "Rejected", tier: "Wholesale", total: 72000, date: "2026-04-29" },
  { id: "ORD-10245", customer: "Sunrise Hotels", type: "Wholesale", demand: "Wholesale", units: 150, branch: "Ikeja Production", status: "Accepted", tier: "Wholesale", total: 135000, date: "2026-04-28" },
  { id: "ORD-10246", customer: "Yaba Outlet", type: "Outlet", demand: "Retail", units: 25, branch: "Surulere Production", status: "Completed", tier: "Retail", total: 21000, date: "2026-04-28" },
  { id: "ORD-10247", customer: "Festac Catering Co.", type: "Wholesale", demand: "Wholesale", units: 300, branch: "Abuja Production", status: "Accepted", tier: "Wholesale", total: 270000, date: "2026-04-27" },
];

export const users = [
  { id: "U-01", name: "Tunde Bello", email: "tunde@zurimart.ng", role: "Branch Manager", branch: "Ikeja Production", status: "Active" },
  { id: "U-02", name: "Amaka Eze", email: "amaka@zurimart.ng", role: "Branch Manager", branch: "Lekki Production", status: "Active" },
  { id: "U-03", name: "Mama Ngozi", email: "ngozi@minimart.ng", role: "Outlet/Minimart", branch: "—", status: "Active" },
  { id: "U-04", name: "BigBite Co.", email: "ops@bigbite.ng", role: "Whole Marketer", branch: "—", status: "Active" },
  { id: "U-05", name: "Hauwa Musa", email: "hauwa@zurimart.ng", role: "Branch Manager", branch: "Abuja Production", status: "Active" },
  { id: "U-06", name: "Chinedu Obi", email: "chinedu@zurimart.ng", role: "Super Admin", branch: "HQ", status: "Active" },
  { id: "U-07", name: "Sunrise Hotels", email: "procure@sunrise.ng", role: "Whole Marketer", branch: "—", status: "Suspended" },
];

export const advanceBookings = [
  { id: "AB-501", customer: "Festac Catering Co.", units: 300, date: "2026-05-04", branch: "Abuja Production", status: "Confirmed" },
  { id: "AB-502", customer: "BigBite Wholesalers", units: 250, date: "2026-05-06", branch: "Ikeja Production", status: "Pending" },
  { id: "AB-503", customer: "Sunrise Hotels", units: 180, date: "2026-05-08", branch: "Surulere Production", status: "Confirmed" },
];

export const salesTrend = [
  { day: "Mon", retail: 120, wholesale: 320 },
  { day: "Tue", retail: 145, wholesale: 410 },
  { day: "Wed", retail: 132, wholesale: 380 },
  { day: "Thu", retail: 178, wholesale: 460 },
  { day: "Fri", retail: 210, wholesale: 540 },
  { day: "Sat", retail: 260, wholesale: 620 },
  { day: "Sun", retail: 190, wholesale: 380 },
];

export const branchPerformance = [
  { name: "Ikeja", orders: 142 },
  { name: "Lekki", orders: 98 },
  { name: "Surulere", orders: 110 },
  { name: "Abuja", orders: 168 },
];

export const stats = {
  totalRevenue: 4_820_000,
  totalOrders: 518,
  pendingOrders: 23,
  activeBranches: 4,
  lowStockItems: 3,
  wholesaleShare: 64,
};
