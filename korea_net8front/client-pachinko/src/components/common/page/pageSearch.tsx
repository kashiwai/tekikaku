"use client";
import { ChangeEvent, InputHTMLAttributes, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import { searchParamUtils } from "@/utils/searchparam.utils";

type Props = {
  queryKey?: string;
  minChars?: number;
} & InputHTMLAttributes<HTMLInputElement>;

export default function PageSearch({
  className,
  queryKey = "search",
  minChars = 1,
  ...props
}: Props) {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [value, setValue] = useState(searchParams.get(queryKey) || "");
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => setDebouncedValue(value), 500);
    return () => clearTimeout(handler);
  }, [value]);

  useEffect(() => {
    const sp = new URLSearchParams(window.location.search);

    if (!debouncedValue || debouncedValue.length < minChars) {
      sp.delete(queryKey);
    } else {
      sp.set(queryKey, debouncedValue);
    }

    const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
    router.replace(`?${sortedQuery}`);
  }, [debouncedValue, queryKey, minChars, router]);

  return (
    <Input
      type="search"
      placeholder={props.placeholder || "Search..."}
      className={cn("bg-foreground/5", className)}
      value={value}
      onChange={(e: ChangeEvent<HTMLInputElement>) => setValue(e.target.value)}
      {...props}
    />
  );
}