import React from "react";
import AppLayout from "@/layouts/app-layout";
import { BaseForm, BaseFormOptions } from "./base-form";
import { z } from "zod";
import { Head } from "@inertiajs/react";

// Props específicas para BaseFormPage
interface BaseFormPageProps<T extends Record<string, any>> {
  options: BaseFormOptions<T>;
  schema: z.ZodType<T>;
  defaultValues: Partial<T>;
  serverErrors?: Record<string, string>;
  FormComponent: React.ComponentType<{ form: any; options: BaseFormOptions<T> }>;
}

export function BaseFormPage<T extends Record<string, any>>({
  options,
  schema,
  defaultValues,
  serverErrors,
  FormComponent,
}: BaseFormPageProps<T>) {
  return (
    <AppLayout breadcrumbs={options.breadcrumbs}>
      <Head title={options.title} />
      
      <BaseForm<T>
        options={options}
        schema={schema}
        defaultValues={defaultValues}
        serverErrors={serverErrors}
      >
        <FormComponent 
          form={undefined} // Será accesible mediante useFormContext dentro del componente
          options={options}
        />
      </BaseForm>
    </AppLayout>
  );
}
