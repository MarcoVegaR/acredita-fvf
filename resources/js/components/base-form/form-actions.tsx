import React from "react";
import { Button } from "@/components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { LoaderIcon, Save, X } from "lucide-react";

interface ButtonConfig {
  label?: string;
  icon?: React.ReactNode;
  variant?: "default" | "outline" | "secondary" | "ghost" | "link" | "destructive";
  disabledText?: string;
  href?: string;
}

interface FormActionsProps {
  isSubmitting: boolean;
  canSubmit: boolean;
  onCancel: () => void;
  saveConfig?: ButtonConfig;
  cancelConfig?: ButtonConfig;
}

export function FormActions({
  isSubmitting,
  canSubmit,
  onCancel,
  saveConfig,
  cancelConfig
}: FormActionsProps) {
  const saveLabel = saveConfig?.label || "Guardar";
  const saveVariant = saveConfig?.variant || "default";
  const saveIcon = saveConfig?.icon || <Save className="mr-2 h-4 w-4" />;
  const saveDisabledText = saveConfig?.disabledText || "No tienes permisos para realizar esta acción";

  const cancelLabel = cancelConfig?.label || "Cancelar";
  const cancelVariant = cancelConfig?.variant || "outline";
  const cancelIcon = cancelConfig?.icon || <X className="mr-2 h-4 w-4" />;

  const SaveButton = () => (
    <Button 
      type="submit" 
      variant={saveVariant}
      disabled={isSubmitting || !canSubmit}
      className="min-w-[120px]"
    >
      {isSubmitting ? (
        <>
          <LoaderIcon className="mr-2 h-4 w-4 animate-spin" />
          Guardando...
        </>
      ) : (
        <>
          {saveIcon}
          {saveLabel}
        </>
      )}
    </Button>
  );

  return (
    <div className="flex items-center justify-end space-x-4 pt-4">
      <Button
        type="button"
        variant={cancelVariant as any}
        onClick={onCancel}
        disabled={isSubmitting}
      >
        {cancelIcon}
        {cancelLabel}
      </Button>

      {canSubmit ? (
        <SaveButton />
      ) : (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span>
                <SaveButton />
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p>{saveDisabledText}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      )}
    </div>
  );
}
