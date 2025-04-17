import React from "react";
import { TabsContent } from "@/components/ui/tabs";

interface FormTabProps {
  value: string;
  label: string;
  icon?: React.ReactNode;
  children: React.ReactNode;
}

 
export function FormTab({ value, children }: FormTabProps) {
  return (
    <TabsContent value={value} className="space-y-4 mt-4 outline-none">
      {children}
    </TabsContent>
  );
}
