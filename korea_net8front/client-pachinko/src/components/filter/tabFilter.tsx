"use client";
import { useRouter } from "next/navigation";

import { useTranslations } from "next-intl";

import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { cn } from "@/lib/utils";


export type TabFilterProps = {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  value: any;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  onValueChange?: (value: any) => void;
  tabs: string[];
  searchParam?: string;
  className?: string;
  pageName?: string;
}

export default function TabFilter({
  value,
  onValueChange,
  tabs,
  searchParam,
  className,
  pageName
}: TabFilterProps) {
  const router = useRouter();
  const t = useTranslations(pageName)

  return (
    <Tabs
      className={cn("w-full p-1 rounded-2xl border border-neutral/5", className)}
      value={value}
      onValueChange={onValueChange}
    >
      <TabsList className="w-full rounded-none p-0">
        {tabs.map((item) => (
          <TabsTrigger
            onClick={() => {
              if (searchParam) {
                router.push(`?${searchParam}=${item}`);
              }
            }}
            className={`${value === item ? "!bg-foreground/5" : ""
              } capitalize cursor-pointer h-full rounded-xl text-xs text-foreground`}
            key={item}
            value={item}
          >
            {t(item.split("-").join(" "))}
          </TabsTrigger>
        ))}
      </TabsList>
    </Tabs>
  );
}
