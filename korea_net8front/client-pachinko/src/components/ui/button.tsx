import * as React from "react";

import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { Loader } from "lucide-react";
import { Ripple } from "react-ripple-click";

import { cn } from "@/lib/utils";

import "react-ripple-click/dist/index.css";

const buttonVariants = cva(
  "relative overlow-hidden inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive cursor-pointer hover:opacity-80",
  {
    variants: {
      variant: {
        default: "bg-neutral/5 border border-neutral/10",
        primary: "bg-primary text-white border border-primary",
        primary_bordered: "bg-primary/20 border-2 border-primary",
        success: "bg-success dark:bg-[#1B955A] text-white",
        success_ghost: "bg-success/5 text-success",
        danger_ghost: "bg-danger/5 text-danger",
      },
      size: {
        default: "h-[42px] min-h-[42px] px-4 rounded-[14px]",
        xs: "h-[34px] min-h-[34px] px-3 text-xs rounded-[8px]",
        icon_xs: "h-[34px] w-[34px] px-0",
        sm: "h-9 min-h-9 px-3",
        icon_default: "h-[42px] w-[42px] px-0 rounded-2xl [&>svg]:!size-5",
        icon_sm: "h-9 w-9 px-0",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

function Button({
  className,
  variant,
  size,
  asChild = false,
  loading = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean;
    loading?: boolean;
  }) {
  const Comp = asChild ? Slot : "button";

  return (
    <Comp
      style={{
        position: "relative",
        overflow: "hidden",
        isolation: "isolate",
      }}
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      disabled={loading || props.disabled}
      {...props}
    >
      <>
        {loading && <Loader className="animate-spin" />}
        {props.children}
        <Ripple />
      </>
    </Comp>
  );
}

export { Button, buttonVariants };
