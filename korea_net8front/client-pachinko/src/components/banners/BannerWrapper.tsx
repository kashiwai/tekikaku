import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";

export default function BannerWrapper({
  className,
  style,
  ...props
}: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("relative px-9 flex justify-between items-center w-full h-[280px] rounded-3xl bg-linear-to-r from-primary dark:from-primary/40 to-primary/10", className)}
      style={{
        ...style,
      }}
    >
      {props.children}
    </div>
  );
}
