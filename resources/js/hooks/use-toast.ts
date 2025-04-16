import { toast as sonnerToast } from "sonner";

type ToastProps = {
  title?: string;
  description?: string;
  variant?: "default" | "success" | "destructive" | "warning" | "info" | "error";
  duration?: number;
};

export function useToast() {
  const toast = ({ title, description, variant = "default", duration = 5000 }: ToastProps) => {
    switch (variant) {
      case "success":
        return sonnerToast.success(title, {
          description,
          duration,
        });
      case "destructive":
      case "error":
        return sonnerToast.error(title, {
          description,
          duration,
        });
      case "warning":
        return sonnerToast.warning(title, {
          description,
          duration,
        });
      case "info":
        return sonnerToast.info(title, {
          description,
          duration,
        });
      default:
        return sonnerToast(title, {
          description,
          duration,
        });
    }
  };

  return { toast };
}
