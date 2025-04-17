import React from "react";
import { Skeleton } from "@/components/ui/skeleton";

interface ShowSectionSkeletonProps {
  fieldCount?: number;
}

export function ShowSectionSkeleton({ fieldCount = 4 }: ShowSectionSkeletonProps) {
  return (
    <section className="border rounded-lg overflow-hidden bg-card">
      {/* Encabezado del skeleton */}
      <div className="bg-muted px-4 py-3 border-b">
        <Skeleton className="h-6 w-40" />
      </div>

      {/* Contenido del skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
        {Array.from({ length: fieldCount }).map((_, index) => (
          <div key={index} className="py-2">
            <Skeleton className="h-4 w-24 mb-2" />
            <Skeleton className="h-5 w-full max-w-[200px]" />
          </div>
        ))}
      </div>
    </section>
  );
}
