import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";

type Props = {
  title: string;
  description?: string;
} & HTMLAttributes<HTMLDivElement>;
export default function CardWrapper({
  title,
  description,
  className,
  children,
  ...props
}: Props) {
  return (
    <div
      className={cn(
        "flex flex-col gap-3 border border-foreground/10 bg-foreground/5 rounded-2xl p-4",
        className
      )}
      {...props}
    >
      <div className="space-y-1">
        <h6 className="text-sm font-semibold text-foreground/40">{title}</h6>
        {description && (
          <p className="text-xs font-normal leading-[130%]">{description}</p>
        )}
      </div>

      {children}
    </div>
  );
}
