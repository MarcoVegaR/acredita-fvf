import React from "react";
// Removed unused imports

interface FormHeaderProps {
  title: string;
  subtitle?: string;
  breadcrumbs?: { title: string; href: string }[];
}

export function FormHeader({ title, subtitle }: FormHeaderProps) {
  // Ya no renderizamos las migas de pan aquí porque ahora se manejan en el AppLayout
  return (
    <div className="space-y-2">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
        {subtitle && (
          <p className="text-muted-foreground mt-1">{subtitle}</p>
        )}
      </div>
    </div>
  );
}
