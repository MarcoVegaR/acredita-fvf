import React, { useState } from "react";
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

interface SuspensionDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  onConfirm: (reason: string) => void;
}

export function SuspensionDialog({
  open,
  onOpenChange,
  title,
  description,
  onConfirm,
}: SuspensionDialogProps) {
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);

  const handleConfirm = () => {
    if (!reason.trim()) {
      setError("El motivo de suspensión es obligatorio");
      return;
    }
    
    onConfirm(reason);
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
          <Label htmlFor="suspension-reason" className="block text-sm font-medium mb-1">
            Motivo de suspensión <span className="text-destructive">*</span>
          </Label>
          <Input
            id="suspension-reason"
            value={reason}
            onChange={(e) => {
              setReason(e.target.value);
              if (e.target.value.trim()) {
                setError(null);
              }
            }}
            placeholder="Ingrese el motivo de la suspensión"
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
            className="bg-destructive hover:bg-destructive/90"
          >
            Suspender
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
