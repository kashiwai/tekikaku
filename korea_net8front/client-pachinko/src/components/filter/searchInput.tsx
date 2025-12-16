"use client";

import { ChangeEvent, InputHTMLAttributes, useState } from "react";

import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

type Props = {
  onValueChange?: (value: string) => void;
} & InputHTMLAttributes<HTMLInputElement>;

export default function SearchInput({
  className,
  placeholder = "Search...",
  onValueChange,
  ...props
}: Props) {
  const [value, setValue] = useState("");

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value;
    setValue(val);
    onValueChange?.(val);
  };

  return (
    <Input
      type="search"
      placeholder={placeholder}
      className={cn("bg-foreground/5", className)}
      value={value}
      onChange={handleChange}
      {...props}
    />
  );
}
