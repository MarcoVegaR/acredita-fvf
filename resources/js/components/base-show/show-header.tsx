import React from "react";

interface ShowHeaderProps {
  title: string;
  subtitle?: string;
}

export function ShowHeader({ title, subtitle }: ShowHeaderProps) {
  return (
    <div className="space-y-4 mb-6">
      {/* Título y subtítulo */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
        {subtitle && (
          <p className="text-muted-foreground mt-1">{subtitle}</p>
        )}
      </div>
    </div>
  );
}
