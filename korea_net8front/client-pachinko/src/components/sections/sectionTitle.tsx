import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";

type Props = {} & HTMLAttributes<HTMLHeadingElement>;

export default function SectionTitle({ className, children, ...props }: Props) {
  return (
    <h2
      className={cn("text-sm md:text-lg font-semibold", className)}
      {...props}
    >
      {children}
    </h2>
  );
}
