import React, { useState } from "react";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { FormTab } from "./form-tab";

interface FormTabContainerProps {
  children: React.ReactNode;
  defaultValue?: string;
  className?: string;
}

export function FormTabContainer({ 
  children, 
  defaultValue, 
  className 
}: FormTabContainerProps) {
  // Si no se proporciona un defaultValue, usar el valor del primer tab
  const childrenArray = React.Children.toArray(children).filter(
    (child) => React.isValidElement(child) && child.type === FormTab
  );
  
  const firstTabValue = React.isValidElement(childrenArray[0])
    ? (childrenArray[0].props as any).value
    : "";
    
  const [value, setValue] = useState(defaultValue || firstTabValue);
  
  return (
    <Tabs 
      value={value} 
      onValueChange={setValue}
      className={className}
    >
      <TabsList className="mb-4 w-full grid grid-cols-2 md:grid-cols-3 lg:flex lg:flex-wrap lg:justify-start">
        {React.Children.map(children, (child) => {
          if (React.isValidElement(child) && child.type === FormTab) {
            const { value, label, icon } = child.props;
            return (
              <TabsTrigger value={value} className="flex items-center gap-1">
                {icon}
                {label}
              </TabsTrigger>
            );
          }
          return null;
        })}
      </TabsList>
      
      {children}
    </Tabs>
  );
}
