import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { format } from "date-fns";
import {
  Bell,
  CalendarIcon,
  Check,
  ChevronRight,
  Clock,
  Download,
  Edit,
  Info,
  Loader2,
  Mail,
  Plus,
  Search,
  Star,
  Trash2,
  TriangleAlert,
  Upload,
  X,
} from "lucide-react";
import { toast } from "sonner";

import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard, StatusBadge } from "@/components/admin/PanelCard";
import { cn } from "@/lib/utils";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Switch } from "@/components/ui/switch";
import { Slider } from "@/components/ui/slider";
import { Progress } from "@/components/ui/progress";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { Drawer, DrawerContent, DrawerDescription, DrawerHeader, DrawerTitle, DrawerTrigger } from "@/components/ui/drawer";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/components/ui/hover-card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Calendar } from "@/components/ui/calendar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";

export const Route = createFileRoute("/ui-kit")({
  head: () => ({ meta: [{ title: "UI Kit — ZuriMart Admin" }] }),
  component: UiKitPage,
});

const sampleRows = [
  { id: "ORD-1042", customer: "Adaeze Nwosu", branch: "Lekki", total: 18500, status: "Completed" },
  { id: "ORD-1043", customer: "Tunde Bello", branch: "Ikeja", total: 7200, status: "Pending" },
  { id: "ORD-1044", customer: "Hauwa Yusuf", branch: "Wuse", total: 32400, status: "Accepted" },
  { id: "ORD-1045", customer: "Kemi Adetola", branch: "Surulere", total: 4500, status: "Rejected" },
];

function Section({ title, description, children }: { title: string; description?: string; children: React.ReactNode }) {
  return (
    <PanelCard title={title} subtitle={description}>
      {children}
    </PanelCard>
  );
}

function TimePicker({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  const [h, m] = value.split(":");
  const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, "0"));
  const minutes = ["00", "15", "30", "45"];
  return (
    <div className="flex items-center gap-2 rounded-md border bg-card p-2">
      <Clock className="h-4 w-4 text-muted-foreground" />
      <Select value={h} onValueChange={(nh) => onChange(`${nh}:${m}`)}>
        <SelectTrigger className="w-[70px] h-8"><SelectValue /></SelectTrigger>
        <SelectContent className="max-h-60">{hours.map(x => <SelectItem key={x} value={x}>{x}</SelectItem>)}</SelectContent>
      </Select>
      <span className="font-bold text-muted-foreground">:</span>
      <Select value={m} onValueChange={(nm) => onChange(`${h}:${nm}`)}>
        <SelectTrigger className="w-[70px] h-8"><SelectValue /></SelectTrigger>
        <SelectContent>{minutes.map(x => <SelectItem key={x} value={x}>{x}</SelectItem>)}</SelectContent>
      </Select>
      <div className="ml-auto flex gap-1">
        {["08:00", "12:00", "17:00"].map((t) => (
          <Button key={t} type="button" size="sm" variant="ghost" className="h-7 px-2 text-xs" onClick={() => onChange(t)}>
            {t}
          </Button>
        ))}
      </div>
    </div>
  );
}

function UiKitPage() {
  const [date, setDate] = useState<Date | undefined>(new Date());
  const [time, setTime] = useState("08:30");
  const [progress, setProgress] = useState(64);
  const [slider, setSlider] = useState([40]);
  const [tab, setTab] = useState("overview");

  return (
    <AdminLayout title="UI Kit">
      <div className="space-y-4">
        {/* Hero / intro */}
        <div className="rounded-md border border-border bg-gradient-to-r from-primary/10 via-card to-card p-5">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Component Library</h2>
              <p className="text-sm text-muted-foreground">Buttons, modals, toasts, tables, pickers, and more — themed to ZuriMart.</p>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button onClick={() => toast.success("Saved successfully")}><Check className="h-4 w-4" /> Success toast</Button>
              <Button variant="outline" onClick={() => toast.error("Something went wrong")}><X className="h-4 w-4" /> Error toast</Button>
              <Button variant="secondary" onClick={() => toast.info?.("Heads up — branch capacity 80%") ?? toast("Heads up — branch capacity 80%")}><Info className="h-4 w-4" /> Info toast</Button>
            </div>
          </div>
        </div>

        {/* Buttons & badges */}
        <Section title="Buttons & Badges" description="Variants, sizes, with icons and loading state.">
          <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
              <Button>Default</Button>
              <Button variant="secondary">Secondary</Button>
              <Button variant="outline">Outline</Button>
              <Button variant="ghost">Ghost</Button>
              <Button variant="link">Link</Button>
              <Button variant="destructive">Destructive</Button>
              <Button disabled><Loader2 className="h-4 w-4 animate-spin" /> Loading</Button>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <Button size="sm"><Plus className="h-4 w-4" /> Add</Button>
              <Button size="default"><Download className="h-4 w-4" /> Download</Button>
              <Button size="lg"><Upload className="h-4 w-4" /> Upload report</Button>
              <Button size="icon" variant="outline"><Edit className="h-4 w-4" /></Button>
              <Button size="icon" variant="outline"><Trash2 className="h-4 w-4" /></Button>
            </div>
            <div className="flex flex-wrap gap-2">
              <Badge>Default</Badge>
              <Badge variant="secondary">Secondary</Badge>
              <Badge variant="outline">Outline</Badge>
              <Badge variant="destructive">Destructive</Badge>
              <StatusBadge status="Completed" />
              <StatusBadge status="Pending" />
              <StatusBadge status="Accepted" />
              <StatusBadge status="Rejected" />
              <StatusBadge status="Wholesale" />
              <StatusBadge status="Retail" />
            </div>
          </div>
        </Section>

        {/* Form controls */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <Section title="Form Controls" description="Inputs, select, textarea, switch, checkbox, radio, slider.">
            <div className="space-y-4">
              <div className="grid sm:grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label>Customer name</Label>
                  <Input placeholder="e.g. Adaeze Nwosu" />
                </div>
                <div className="space-y-1.5">
                  <Label>Branch</Label>
                  <Select>
                    <SelectTrigger><SelectValue placeholder="Choose branch" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="lekki">Lekki</SelectItem>
                      <SelectItem value="ikeja">Ikeja</SelectItem>
                      <SelectItem value="wuse">Wuse</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-1.5">
                <Label>Search with icon</Label>
                <div className="relative">
                  <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                  <Input placeholder="Search orders…" className="pl-8" />
                </div>
              </div>
              <div className="space-y-1.5">
                <Label>Notes</Label>
                <Textarea placeholder="Special instructions…" />
              </div>
              <div className="flex flex-wrap items-center gap-6">
                <div className="flex items-center gap-2"><Switch id="sw" defaultChecked /><Label htmlFor="sw">Notifications</Label></div>
                <div className="flex items-center gap-2"><Checkbox id="cb" defaultChecked /><Label htmlFor="cb">I agree</Label></div>
                <RadioGroup defaultValue="retail" className="flex gap-4">
                  <div className="flex items-center gap-2"><RadioGroupItem value="retail" id="r1" /><Label htmlFor="r1">Retail</Label></div>
                  <div className="flex items-center gap-2"><RadioGroupItem value="wholesale" id="r2" /><Label htmlFor="r2">Wholesale</Label></div>
                </RadioGroup>
              </div>
              <div className="space-y-2">
                <Label>Capacity threshold ({slider[0]}%)</Label>
                <Slider value={slider} onValueChange={setSlider} max={100} step={5} />
              </div>
              <div className="space-y-2">
                <div className="flex justify-between text-xs text-muted-foreground"><span>Order fulfillment</span><span>{progress}%</span></div>
                <Progress value={progress} />
                <div className="flex gap-2">
                  <Button size="sm" variant="outline" onClick={() => setProgress((p) => Math.max(0, p - 10))}>-10</Button>
                  <Button size="sm" variant="outline" onClick={() => setProgress((p) => Math.min(100, p + 10))}>+10</Button>
                </div>
              </div>
            </div>
          </Section>

          {/* Pickers */}
          <Section title="Date & Time Pickers" description="Beautiful, accessible pickers built on shadcn primitives.">
            <div className="space-y-4">
              <div className="space-y-1.5">
                <Label>Pick a date</Label>
                <Popover>
                  <PopoverTrigger asChild>
                    <Button variant="outline" className={cn("w-full sm:w-[260px] justify-start font-normal", !date && "text-muted-foreground")}>
                      <CalendarIcon className="h-4 w-4" />
                      {date ? format(date, "PPP") : "Pick a date"}
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-auto p-0" align="start">
                    <Calendar mode="single" selected={date} onSelect={setDate} initialFocus className="p-3 pointer-events-auto" />
                  </PopoverContent>
                </Popover>
              </div>

              <div className="space-y-1.5">
                <Label>Pick a time</Label>
                <TimePicker value={time} onChange={setTime} />
              </div>

              <div className="rounded-md border bg-muted/40 p-3 text-sm">
                <div className="text-muted-foreground text-xs">Selected slot</div>
                <div className="font-medium">{date ? format(date, "PPP") : "—"} at {time}</div>
              </div>

              <div className="space-y-1.5">
                <Label>Inline calendar</Label>
                <div className="rounded-md border bg-card inline-block">
                  <Calendar mode="single" selected={date} onSelect={setDate} className="p-3 pointer-events-auto" />
                </div>
              </div>
            </div>
          </Section>
        </div>

        {/* Modals + overlays */}
        <Section title="Modals, Sheets & Drawers" description="Dialogs for forms, alerts, side sheets, and bottom drawers.">
          <div className="flex flex-wrap gap-2">
            <Dialog>
              <DialogTrigger asChild><Button>Open Modal</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>New Order</DialogTitle>
                  <DialogDescription>Create a wholesale order and assign a branch.</DialogDescription>
                </DialogHeader>
                <div className="space-y-3">
                  <div className="space-y-1.5"><Label>Customer</Label><Input placeholder="Customer name" /></div>
                  <div className="space-y-1.5"><Label>Quantity</Label><Input type="number" defaultValue={10} /></div>
                </div>
                <DialogFooter>
                  <Button variant="outline">Cancel</Button>
                  <Button onClick={() => toast.success("Order created")}>Save</Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>

            <AlertDialog>
              <AlertDialogTrigger asChild><Button variant="destructive">Delete…</Button></AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Delete this product?</AlertDialogTitle>
                  <AlertDialogDescription>This action cannot be undone.</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Cancel</AlertDialogCancel>
                  <AlertDialogAction onClick={() => toast.success("Deleted")}>Delete</AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>

            <Sheet>
              <SheetTrigger asChild><Button variant="outline">Open Sheet</Button></SheetTrigger>
              <SheetContent>
                <SheetHeader>
                  <SheetTitle>Filters</SheetTitle>
                  <SheetDescription>Narrow down orders.</SheetDescription>
                </SheetHeader>
                <div className="mt-4 space-y-3">
                  <div className="space-y-1.5"><Label>Status</Label><Select><SelectTrigger><SelectValue placeholder="All" /></SelectTrigger><SelectContent><SelectItem value="all">All</SelectItem><SelectItem value="pending">Pending</SelectItem></SelectContent></Select></div>
                  <div className="space-y-1.5"><Label>Branch</Label><Input placeholder="Branch" /></div>
                </div>
              </SheetContent>
            </Sheet>

            <Drawer>
              <DrawerTrigger asChild><Button variant="secondary">Open Drawer</Button></DrawerTrigger>
              <DrawerContent>
                <DrawerHeader>
                  <DrawerTitle>Quick view</DrawerTitle>
                  <DrawerDescription>Mobile-friendly bottom sheet.</DrawerDescription>
                </DrawerHeader>
                <div className="px-4 pb-6 text-sm text-muted-foreground">Drawer content goes here.</div>
              </DrawerContent>
            </Drawer>
          </div>
        </Section>

        {/* Alerts */}
        <Section title="Alerts" description="Inline status messages.">
          <div className="grid sm:grid-cols-2 gap-3">
            <Alert><Info className="h-4 w-4" /><AlertTitle>Heads up</AlertTitle><AlertDescription>Lekki branch has 3 slots left this hour.</AlertDescription></Alert>
            <Alert variant="destructive"><TriangleAlert className="h-4 w-4" /><AlertTitle>Capacity reached</AlertTitle><AlertDescription>Ikeja branch is fully booked.</AlertDescription></Alert>
            <div className="rounded-md border border-success/40 bg-success/10 text-success p-3 text-sm flex gap-2"><Check className="h-4 w-4 mt-0.5" /><div><div className="font-medium">Restock complete</div><div className="text-success/80">All loaves replenished.</div></div></div>
            <div className="rounded-md border border-warning/40 bg-warning/15 p-3 text-sm flex gap-2 text-warning-foreground"><Bell className="h-4 w-4 mt-0.5" /><div><div className="font-medium">Wholesale order</div><div className="opacity-80">3 new wholesale orders pending.</div></div></div>
          </div>
        </Section>

        {/* Tabs + Accordion */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <Section title="Tabs">
            <Tabs value={tab} onValueChange={setTab}>
              <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="activity">Activity</TabsTrigger>
                <TabsTrigger value="settings">Settings</TabsTrigger>
              </TabsList>
              <TabsContent value="overview" className="text-sm text-muted-foreground pt-2">Daily production summary, branch load, top products.</TabsContent>
              <TabsContent value="activity" className="text-sm text-muted-foreground pt-2">Recent orders, restocks, and assignments.</TabsContent>
              <TabsContent value="settings" className="text-sm text-muted-foreground pt-2">Pricing tiers, capacity, notifications.</TabsContent>
            </Tabs>
          </Section>

          <Section title="Accordion">
            <Accordion type="single" collapsible defaultValue="a">
              <AccordionItem value="a"><AccordionTrigger>How is branch capacity calculated?</AccordionTrigger><AccordionContent className="text-sm text-muted-foreground">Each branch defines a per-minute oven capacity. Orders lock that capacity on accept.</AccordionContent></AccordionItem>
              <AccordionItem value="b"><AccordionTrigger>Can wholesale orders span branches?</AccordionTrigger><AccordionContent className="text-sm text-muted-foreground">Yes — large orders can be split across branches automatically.</AccordionContent></AccordionItem>
              <AccordionItem value="c"><AccordionTrigger>How are managers notified?</AccordionTrigger><AccordionContent className="text-sm text-muted-foreground">Push, email, or WhatsApp the moment they are tagged.</AccordionContent></AccordionItem>
            </Accordion>
          </Section>
        </div>

        {/* Responsive table */}
        <Section title="Responsive Table" description="Horizontally scrolls on narrow screens; collapses to cards on mobile.">
          <div className="hidden md:block">
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Order</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Branch</TableHead>
                    <TableHead className="text-right">Total (₦)</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {sampleRows.map((r) => (
                    <TableRow key={r.id}>
                      <TableCell className="font-medium">{r.id}</TableCell>
                      <TableCell>{r.customer}</TableCell>
                      <TableCell>{r.branch}</TableCell>
                      <TableCell className="text-right">{r.total.toLocaleString()}</TableCell>
                      <TableCell><StatusBadge status={r.status} /></TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild><Button size="sm" variant="ghost">Actions <ChevronRight className="h-4 w-4" /></Button></DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuLabel>Manage</DropdownMenuLabel>
                            <DropdownMenuItem onClick={() => toast("Viewing " + r.id)}>View</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => toast("Editing " + r.id)}>Edit</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem className="text-destructive" onClick={() => toast.error("Deleted " + r.id)}>Delete</DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
          {/* Mobile cards */}
          <div className="md:hidden space-y-2">
            {sampleRows.map((r) => (
              <div key={r.id} className="rounded-md border border-border bg-card p-3">
                <div className="flex items-center justify-between">
                  <div className="font-semibold text-sm">{r.id}</div>
                  <StatusBadge status={r.status} />
                </div>
                <div className="mt-1 text-sm">{r.customer}</div>
                <div className="text-xs text-muted-foreground">{r.branch}</div>
                <div className="mt-2 flex items-center justify-between">
                  <div className="font-medium">₦ {r.total.toLocaleString()}</div>
                  <Button size="sm" variant="outline">View</Button>
                </div>
              </div>
            ))}
          </div>

          <Separator className="my-4" />

          <Pagination>
            <PaginationContent>
              <PaginationItem><PaginationPrevious href="#" /></PaginationItem>
              <PaginationItem><PaginationLink href="#" isActive>1</PaginationLink></PaginationItem>
              <PaginationItem><PaginationLink href="#">2</PaginationLink></PaginationItem>
              <PaginationItem><PaginationLink href="#">3</PaginationLink></PaginationItem>
              <PaginationItem><PaginationNext href="#" /></PaginationItem>
            </PaginationContent>
          </Pagination>
        </Section>

        {/* Tooltip / Hover / Breadcrumb / Toggle / Avatar / Skeleton */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <Section title="Tooltips, Hover & Popovers">
            <div className="flex flex-wrap items-center gap-3">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild><Button variant="outline"><Mail className="h-4 w-4" /> Tooltip</Button></TooltipTrigger>
                  <TooltipContent>Send an email</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <HoverCard>
                <HoverCardTrigger asChild><Button variant="outline">Hover card</Button></HoverCardTrigger>
                <HoverCardContent>
                  <div className="flex gap-3">
                    <Avatar><AvatarFallback>AN</AvatarFallback></Avatar>
                    <div className="text-sm"><div className="font-semibold">Adaeze Nwosu</div><div className="text-muted-foreground text-xs">VIP wholesale customer</div></div>
                  </div>
                </HoverCardContent>
              </HoverCard>

              <Popover>
                <PopoverTrigger asChild><Button variant="outline"><Star className="h-4 w-4" /> Popover</Button></PopoverTrigger>
                <PopoverContent className="text-sm">Quick info popover with any content.</PopoverContent>
              </Popover>
            </div>
          </Section>

          <Section title="Breadcrumb, Toggles, Avatars & Skeletons">
            <div className="space-y-4">
              <Breadcrumb>
                <BreadcrumbList>
                  <BreadcrumbItem><BreadcrumbLink href="/">Dashboard</BreadcrumbLink></BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem><BreadcrumbLink href="/orders">Orders</BreadcrumbLink></BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem><BreadcrumbPage>ORD-1042</BreadcrumbPage></BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>

              <ToggleGroup type="single" defaultValue="day" variant="outline">
                <ToggleGroupItem value="day">Day</ToggleGroupItem>
                <ToggleGroupItem value="week">Week</ToggleGroupItem>
                <ToggleGroupItem value="month">Month</ToggleGroupItem>
              </ToggleGroup>

              <div className="flex items-center gap-2">
                {["AN", "TB", "HY", "KA"].map((n, i) => (
                  <Avatar key={n} className={cn("border-2 border-card -ml-2 first:ml-0")}>
                    <AvatarFallback className={cn(i % 2 ? "bg-primary text-primary-foreground" : "bg-secondary")}>{n}</AvatarFallback>
                  </Avatar>
                ))}
                <span className="text-xs text-muted-foreground ml-2">+12 staff online</span>
              </div>

              <div className="space-y-2">
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-4 w-1/2" />
                <Skeleton className="h-20 w-full" />
              </div>
            </div>
          </Section>
        </div>
      </div>
    </AdminLayout>
  );
}
