import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { X, CheckCircle, AlertCircle, Info, AlertTriangle } from "lucide-react";

import { cn } from "@/lib/utils";

const toastVariants = cva(
  "group pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border p-4 pr-6 shadow-lg transition-all data-[swipe=cancel]:translate-x-0 data-[swipe=end]:translate-x-[var(--radix-toast-swipe-end-x)] data-[swipe=move]:translate-x-[var(--radix-toast-swipe-move-x)] data-[swipe=move]:transition-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[swipe=end]:animate-out data-[state=closed]:fade-out-80 data-[state=closed]:slide-out-to-right-full data-[state=open]:slide-in-from-top-full data-[state=open]:sm:slide-in-from-bottom-full",
  {
    variants: {
      variant: {
        default: "border bg-background text-foreground",
        success: "border-green-200 bg-green-50 text-green-900 dark:bg-green-950 dark:border-green-800 dark:text-green-50",
        destructive: "border-red-200 bg-red-50 text-red-900 dark:bg-red-950 dark:border-red-800 dark:text-red-50",
        warning: "border-yellow-200 bg-yellow-50 text-yellow-900 dark:bg-yellow-950 dark:border-yellow-800 dark:text-yellow-50",
        info: "border-blue-200 bg-blue-50 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-50",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

const iconMap = {
  success: <CheckCircle className="h-5 w-5" />,
  destructive: <AlertCircle className="h-5 w-5" />,
  warning: <AlertTriangle className="h-5 w-5" />,
  info: <Info className="h-5 w-5" />,
  default: <Info className="h-5 w-5" />,
};

interface ToastNotificationProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof toastVariants> {
  title?: string;
  description?: string;
  onClose?: () => void;
}

const ToastNotification = React.forwardRef<HTMLDivElement, ToastNotificationProps>(
  ({ className, variant = "default", title, description, onClose, ...props }, ref) => {
    return (
      <div
        ref={ref}
        className={cn(toastVariants({ variant }), className)}
        {...props}
      >
        <div className="flex items-start gap-3">
          <div className="flex-shrink-0">
            {iconMap[variant as keyof typeof iconMap]}
          </div>
          <div className="flex-1">
            {title && <div className="font-medium">{title}</div>}
            {description && <div className="mt-1 text-sm opacity-90">{description}</div>}
          </div>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="flex h-8 w-8 items-center justify-center rounded-md p-0 absolute right-1 top-1 text-foreground/50 hover:text-foreground"
          >
            <X className="h-4 w-4" />
            <span className="sr-only">Close</span>
          </button>
        )}
      </div>
    );
  }
);
ToastNotification.displayName = "ToastNotification";

export { ToastNotification, toastVariants };
