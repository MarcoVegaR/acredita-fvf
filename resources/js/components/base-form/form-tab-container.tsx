import React, { useState } from "react";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { FormTab } from "@/components/base-form/form-tab";

// Definir el tipo para las props de FormTab para acceder de forma segura
type FormTabProps = {
  value: string;
  label: string;
  icon?: React.ReactNode;
  children: React.ReactNode;
};

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
    ? (childrenArray[0].props as FormTabProps).value
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
            const { value, label, icon } = child.props as FormTabProps;
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
