import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";

type Props = {} & HTMLAttributes<HTMLHeadingElement>;

export default function PageTitle({ className, children, ...props }: Props) {
  return (
    <h1 className={cn("text-lg md:text-2xl font-bold", className)} {...props}>
      {children}
    </h1>
  );
}
