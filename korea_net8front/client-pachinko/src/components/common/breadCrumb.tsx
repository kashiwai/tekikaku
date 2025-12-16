import React from "react";

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

type BreadcrumbData = {
  to?: string;
  label: string;
};

type Props = {
  data: BreadcrumbData[];
};

export default async function PageBreadcrumb({ data }: Props) {
  return (
    <Breadcrumb>
      <BreadcrumbList>
        {data.map((nav, i) => (
          <React.Fragment key={i}>
            <BreadcrumbItem>
              {i !== data.length - 1 ? (
                <BreadcrumbLink className="font-medium" href={nav.to}>
                  {nav.label}
                </BreadcrumbLink>
              ) : (
                <BreadcrumbPage className="opacity-70 font-medium">
                  {nav.label}
                </BreadcrumbPage>
              )}
            </BreadcrumbItem>
            {i !== data.length - 1 && <BreadcrumbSeparator />}
          </React.Fragment>
        ))}
      </BreadcrumbList>
    </Breadcrumb>
  );
}
