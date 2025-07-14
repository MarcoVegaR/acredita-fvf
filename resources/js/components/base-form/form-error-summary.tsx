import React from "react";
import { AlertCircleIcon } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { FieldErrors } from "react-hook-form";

interface FormErrorSummaryProps {
  errors: FieldErrors;
  labels: Record<string, string>;
}

export function FormErrorSummary({ errors, labels }: FormErrorSummaryProps) {
  const errorFields = Object.keys(errors);
  const errorCount = errorFields.length;
  
  if (errorCount === 0) return null;
  
  return (
    <Alert 
      id="form-error-summary" 
      variant="destructive"
      className="border-destructive/80 bg-red-50 dark:bg-destructive/10 shadow-sm [&>svg]:text-red-600 dark:[&>svg]:text-red-400"
    >
      <AlertCircleIcon className="h-5 w-5" />
      <AlertTitle className="flex items-center gap-2 font-bold text-red-700 dark:text-red-400">
        Se encontraron {errorCount} {errorCount === 1 ? 'error' : 'errores'}
      </AlertTitle>
      <AlertDescription>
        <ul className="ml-6 mt-2 list-disc text-sm marker:text-red-600 dark:marker:text-red-400">
          {errorFields.map((field) => (
            <li key={field} className="mt-1">
              <button
                type="button"
                className="underline text-red-700 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 focus:outline-none font-semibold"
                onClick={() => {
                  // Scroll al campo con error
                  const element = document.querySelector(`[name="${field}"]`);
                  if (element) {
                    element.scrollIntoView({
                      behavior: "smooth",
                      block: "center"
                    });
                    
                    // Intentar dar foco al campo
                    (element as HTMLElement).focus();
                  } else {
                    // Para campos anidados o arrays, intentar encontrar un contenedor relacionado
                    const containers = document.querySelectorAll(`[data-field="${field}"]`);
                    if (containers.length > 0) {
                      containers[0].scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                      });
                    }
                  }
                }}
              >
                {labels[field] || field}: {errors[field]?.message as string}
              </button>
            </li>
          ))}
        </ul>
      </AlertDescription>
    </Alert>
  );
}
