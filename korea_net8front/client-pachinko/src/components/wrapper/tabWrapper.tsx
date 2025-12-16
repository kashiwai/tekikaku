import React from "react";

import { cn } from "@/lib/utils";

type Props = {
  startContent?: React.ReactNode;
  title?: string;
  description?: string;
  endContent?: React.ReactNode;
} & React.HtmlHTMLAttributes<HTMLDivElement>;

export default function TabWrapper({
  startContent,
  title,
  description,
  endContent,
  ...props
}: Props) {
  return (
    <div className="space-y-2 md:space-y-4 p-4 rounded-2xl bg-foreground/5 border border-foreground/5">
      <div className="flex items-center w-full justify-between">
        {(title || description || startContent) && (
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              {startContent && startContent}
              {title && (
                <h6 className="text-sm md:text-lg font-semibold">{title}</h6>
              )}
            </div>
            {description && (
              <p className="text-sm text-foreground/60">{description}</p>
            )}
          </div>
        )}
        {endContent && endContent}
      </div>
      <div className={cn("space-y-4", props.className)}>{props.children}</div>
    </div>
  );
}
