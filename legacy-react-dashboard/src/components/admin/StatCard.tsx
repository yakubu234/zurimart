import { LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";

type Variant = "info" | "success" | "warning" | "danger" | "primary";

const variantClasses: Record<Variant, string> = {
  info: "bg-info text-info-foreground",
  success: "bg-success text-success-foreground",
  warning: "bg-warning text-warning-foreground",
  danger: "bg-danger text-danger-foreground",
  primary: "bg-primary text-primary-foreground",
};

interface StatCardProps {
  label: string;
  value: string | number;
  sublabel?: string;
  icon: LucideIcon;
  variant?: Variant;
}

export function StatCard({ label, value, sublabel, icon: Icon, variant = "info" }: StatCardProps) {
  return (
    <div className={cn("rounded-md shadow-sm overflow-hidden flex", variantClasses[variant])}>
      <div className="flex-1 p-4">
        <div className="text-3xl font-bold leading-tight">{value}</div>
        <div className="text-sm opacity-90 mt-1">{label}</div>
        {sublabel && <div className="text-xs opacity-75 mt-2">{sublabel}</div>}
      </div>
      <div className="flex items-center justify-center px-5 bg-black/10">
        <Icon className="h-10 w-10 opacity-90" />
      </div>
    </div>
  );
}
