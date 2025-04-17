import React, { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { ChevronDownIcon, ChevronUpIcon } from "lucide-react";

interface FormSectionProps {
  title?: string;
  description?: string;
  children: React.ReactNode;
  collapsible?: boolean;
  defaultOpen?: boolean;
  columns?: 1 | 2 | 3;
  className?: string;
}

export function FormSection({
  title,
  description,
  children,
  collapsible = false,
  defaultOpen = true,
  columns = 1,
  className
}: FormSectionProps) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  
  const headerContent = title && (
    <CardHeader className="py-3 px-4">
      <div className="flex justify-between items-center">
        <div>
          <CardTitle className="text-lg font-semibold">{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </div>
        {collapsible && (
          <Button 
            variant="ghost" 
            size="sm" 
            onClick={() => setIsOpen(!isOpen)}
            className="ml-2 h-8 w-8 p-0"
          >
            {isOpen ? (
              <ChevronUpIcon className="h-4 w-4" />
            ) : (
              <ChevronDownIcon className="h-4 w-4" />
            )}
          </Button>
        )}
      </div>
    </CardHeader>
  );
  
  const contentClasses = cn(
    "grid gap-4",
    columns === 1 && "grid-cols-1",
    columns === 2 && "grid-cols-1 md:grid-cols-2",
    columns === 3 && "grid-cols-1 md:grid-cols-2 lg:grid-cols-3",
  );
  
  const content = (!collapsible || isOpen) && (
    <CardContent className={cn("px-4 py-3", !title && "pt-4")}>
      <div className={contentClasses}>
        {children}
      </div>
    </CardContent>
  );
  
  return (
    <Card className={cn("transition-all", className)}>
      {headerContent}
      {content}
    </Card>
  );
}
