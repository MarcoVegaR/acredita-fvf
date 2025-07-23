import React, { useState, useEffect } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

interface ActionDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;

  reasonLabel: string;
  reasonPlaceholder: string;
  confirmButtonLabel: string;
  confirmButtonClass?: string;
  isReasonRequired?: boolean;
  onConfirm: (reason: string) => void;
}

export function ActionDialog({
  open,
  onOpenChange,
  title,
  description,

  reasonLabel,
  reasonPlaceholder,
  confirmButtonLabel,
  confirmButtonClass = "bg-primary hover:bg-primary/90",
  isReasonRequired = false,
  onConfirm,
}: ActionDialogProps) {
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);

  // Resetear el estado cuando el diálogo se abre o cierra
  useEffect(() => {
    if (!open) {
      setReason("");
      setError(null);
    }
  }, [open]);

  const handleConfirm = () => {
    if (isReasonRequired && !reason.trim()) {
      setError(`El ${reasonLabel.toLowerCase()} es obligatorio`);
      return;
    }
    
    // Garantizar que reason nunca sea null
    const safeReason = reason ? reason.trim() : "";
    
    onConfirm(safeReason);
    setReason(""); // Reset after submit
    setError(null);
    onOpenChange(false);
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>{description}</AlertDialogDescription>
        </AlertDialogHeader>
        
        <div className="py-4">
          <Label htmlFor="action-reason" className="block text-sm font-medium mb-1">
            {reasonLabel} {isReasonRequired && <span className="text-destructive">*</span>}
          </Label>
          <Input
            id="action-reason"
            value={reason}
            onChange={(e) => {
              setReason(e.target.value);
              if (e.target.value.trim() || !isReasonRequired) {
                setError(null);
              }
            }}
            placeholder={reasonPlaceholder}
            className={error ? "border-destructive" : ""}
          />
          {error && <p className="text-destructive text-sm mt-1">{error}</p>}
        </div>
        
        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction 
            onClick={(e) => {
              e.preventDefault();
              handleConfirm();
            }}
            className={confirmButtonClass}
          >
            {confirmButtonLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
