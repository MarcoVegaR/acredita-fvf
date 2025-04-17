import React from "react";
import { TabsContent } from "@/components/ui/tabs";

interface FormTabProps {
  value: string;
  label: string;
  children: React.ReactNode;
  icon?: React.ReactNode;
}

export function FormTab({ value, label, children, icon }: FormTabProps) {
  return (
    <TabsContent value={value} className="space-y-4 mt-4 outline-none">
      {children}
    </TabsContent>
  );
}
