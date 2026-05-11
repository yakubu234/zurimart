import { ReactNode } from "react";
import { cn } from "@/lib/utils";

interface PanelCardProps {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  children: ReactNode;
  className?: string;
  noPadding?: boolean;
}

export function PanelCard({ title, subtitle, actions, children, className, noPadding }: PanelCardProps) {
  return (
    <div className={cn("bg-card rounded-md shadow-sm border border-border overflow-hidden", className)}>
      <div className="border-b border-border px-4 py-3 flex items-center justify-between bg-card">
        <div>
          <h3 className="text-sm font-semibold text-foreground">{title}</h3>
          {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
        </div>
        {actions && <div className="flex items-center gap-2">{actions}</div>}
      </div>
      <div className={cn(noPadding ? "" : "p-4")}>{children}</div>
    </div>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const map: Record<string, string> = {
    Available: "bg-success/15 text-success border border-success/30",
    "Overly Booked": "bg-danger/15 text-danger border border-danger/30",
    Accepted: "bg-info/15 text-info border border-info/30",
    Pending: "bg-warning/20 text-warning-foreground border border-warning/40",
    Completed: "bg-success/15 text-success border border-success/30",
    Rejected: "bg-danger/15 text-danger border border-danger/30",
    Active: "bg-success/15 text-success border border-success/30",
    Suspended: "bg-danger/15 text-danger border border-danger/30",
    Confirmed: "bg-success/15 text-success border border-success/30",
    Retail: "bg-info/15 text-info border border-info/30",
    Wholesale: "bg-primary/15 text-primary border border-primary/30",
  };
  return (
    <span className={cn("inline-flex items-center text-xs font-medium px-2 py-0.5 rounded", map[status] ?? "bg-secondary text-secondary-foreground")}>
      {status}
    </span>
  );
}
