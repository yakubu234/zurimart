import { ReactNode } from "react";
import { SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar";
import { AppSidebar } from "./AppSidebar";
import { Bell, Search, ChevronRight, User, Settings as SettingsIcon, LogOut, ShoppingCart, Package, AlertCircle } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Link } from "@tanstack/react-router";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { toast } from "sonner";

interface AdminLayoutProps {
  title: string;
  breadcrumbs?: { label: string; to?: string }[];
  children: ReactNode;
  actions?: ReactNode;
}

export function AdminLayout({ title, breadcrumbs = [], children, actions }: AdminLayoutProps) {
  return (
    <SidebarProvider>
      <div className="min-h-screen flex w-full bg-background">
        <AppSidebar />

        <div className="flex-1 flex flex-col min-w-0">
          {/* Top navbar */}
          <header className="h-14 bg-card border-b border-border flex items-center px-3 sm:px-4 gap-2 sm:gap-3 sticky top-0 z-20">
            <SidebarTrigger className="text-foreground shrink-0" />
            <div className="hidden md:flex items-center gap-2 flex-1 max-w-md">
              <div className="relative w-full">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search orders, products, branches..."
                  className="pl-9 h-9 bg-secondary border-transparent focus-visible:bg-card"
                />
              </div>
            </div>
            <div className="ml-auto flex items-center gap-1 sm:gap-2">
              <button
                className="md:hidden h-9 w-9 rounded-md hover:bg-secondary flex items-center justify-center"
                onClick={() => toast.info("Search coming soon")}
                aria-label="Search"
              >
                <Search className="h-4 w-4" />
              </button>

              {/* Notifications dropdown */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button
                    className="relative h-9 w-9 rounded-md hover:bg-secondary flex items-center justify-center"
                    aria-label="Notifications"
                  >
                    <Bell className="h-4 w-4" />
                    <span className="absolute top-1.5 right-1.5 h-4 w-4 rounded-full bg-danger text-danger-foreground text-[10px] flex items-center justify-center font-bold">
                      3
                    </span>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-72 sm:w-80">
                  <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notifications</span>
                    <span className="text-[10px] font-normal text-muted-foreground">3 new</span>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem className="flex items-start gap-2 py-2.5">
                    <ShoppingCart className="h-4 w-4 mt-0.5 text-info shrink-0" />
                    <div className="flex flex-col gap-0.5 min-w-0">
                      <span className="text-sm font-medium truncate">New wholesale order #1287</span>
                      <span className="text-xs text-muted-foreground">Lekki branch • 2 min ago</span>
                    </div>
                  </DropdownMenuItem>
                  <DropdownMenuItem className="flex items-start gap-2 py-2.5">
                    <AlertCircle className="h-4 w-4 mt-0.5 text-warning shrink-0" />
                    <div className="flex flex-col gap-0.5 min-w-0">
                      <span className="text-sm font-medium truncate">Ikeja oven near capacity</span>
                      <span className="text-xs text-muted-foreground">92% utilised • 10 min ago</span>
                    </div>
                  </DropdownMenuItem>
                  <DropdownMenuItem className="flex items-start gap-2 py-2.5">
                    <Package className="h-4 w-4 mt-0.5 text-success shrink-0" />
                    <div className="flex flex-col gap-0.5 min-w-0">
                      <span className="text-sm font-medium truncate">Chocolate restock complete</span>
                      <span className="text-xs text-muted-foreground">Surulere • 1 hr ago</span>
                    </div>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem className="justify-center text-sm text-primary">
                    View all notifications
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Profile dropdown */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button
                    className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold hover:opacity-90"
                    aria-label="Account menu"
                  >
                    CO
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>
                    <div className="flex flex-col">
                      <span className="text-sm font-medium">Chinedu Obi</span>
                      <span className="text-xs text-muted-foreground font-normal">admin@zurimart.ng</span>
                    </div>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild>
                    <Link to="/users"><User className="h-4 w-4 mr-2" />Profile</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link to="/settings"><SettingsIcon className="h-4 w-4 mr-2" />Settings</Link>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={() => toast.success("Logged out")}
                    className="text-danger focus:text-danger"
                  >
                    <LogOut className="h-4 w-4 mr-2" />Logout
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </header>

          {/* Page header */}
          <div className="bg-card border-b border-border px-4 sm:px-6 py-3 sm:py-4 flex flex-wrap items-center justify-between gap-3">
            <div className="min-w-0">
              <h1 className="text-lg sm:text-xl font-semibold text-foreground truncate">{title}</h1>
              <nav className="flex items-center gap-1 text-xs text-muted-foreground mt-1 flex-wrap">
                <Link to="/" className="hover:text-primary">Home</Link>
                {breadcrumbs.map((bc, i) => (
                  <span key={i} className="flex items-center gap-1">
                    <ChevronRight className="h-3 w-3" />
                    {bc.to ? (
                      <Link to={bc.to} className="hover:text-primary">{bc.label}</Link>
                    ) : (
                      <span className="text-foreground">{bc.label}</span>
                    )}
                  </span>
                ))}
              </nav>
            </div>
            {actions && <div className="flex items-center gap-2 flex-wrap">{actions}</div>}
          </div>

          {/* Content */}
          <main className="flex-1 p-4 sm:p-6 overflow-x-hidden">{children}</main>

          <footer className="border-t border-border bg-card px-4 sm:px-6 py-3 text-xs text-muted-foreground flex flex-col sm:flex-row gap-1 sm:gap-0 justify-between">
            <span>© 2026 ZuriMart Bakery. All rights reserved.</span>
            <span>Version <strong className="text-foreground">1.0.0</strong></span>
          </footer>
        </div>
      </div>
    </SidebarProvider>
  );
}
