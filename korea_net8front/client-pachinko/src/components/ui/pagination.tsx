"use client";
import * as React from "react";

import { usePathname, useSearchParams } from "next/navigation";

import { MoreHorizontalIcon } from "lucide-react";

import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";
import Link from "next/link";
import { cn } from "@/lib/utils";

function useBuildPageHref() {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [mounted, setMounted] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
  }, []);

  return (page: number | null) => {
    if (!mounted) {
      // Return a safe default during SSR
      return "#";
    }

    const params = new URLSearchParams(searchParams.toString());

    if (page && page <= 1) {
      params.delete("page");
    } else {
      params.set("page", String(page));
    }

    const query = params.toString();
    return `${pathname}${query ? `?${query}` : ""}`;
  };
}

function Pagination({ className, ...props }: React.ComponentProps<"nav">) {
  return (
    <nav
      role="navigation"
      aria-label="pagination"
      data-slot="pagination"
      className={cn("mx-auto flex w-full justify-center", className)}
      {...props}
    />
  );
}

function PaginationContent({
  className,
  ...props
}: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="pagination-content"
      className={cn("flex flex-row items-center gap-1", className)}
      {...props}
    />
  );
}

function PaginationItem({ ...props }: React.ComponentProps<"li">) {
  return <li data-slot="pagination-item" {...props} />;
}

type PaginationLinkProps = {
  isActive?: boolean;
  page: number | null;
} & React.ComponentProps<"a">;

function PaginationLink({
  className,
  isActive,
  page,
  children,
  ...props
}: PaginationLinkProps) {
  const buildHref = useBuildPageHref();
  const [mounted, setMounted] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    // Return a placeholder during SSR to prevent hydration mismatch
    return (
      <span
        className={cn(
          `size-8 lg:size-9 flex items-center justify-center rounded-full text-[13px]`,
          isActive ? "bg-primary text-white" : "bg-foreground/5",
          className
        )}
      >
        {children ?? page}
      </span>
    );
  }

  return (
    <Link
      prefetch
      href={buildHref(page)}
      aria-current={isActive ? "page" : undefined}
      data-slot="pagination-link"
      data-active={isActive}
      className={cn(
        `size-8 lg:size-9 flex items-center justify-center rounded-full text-[13px] hover:opacity-80`,
        isActive ? "bg-primary text-white" : "bg-foreground/5",
        className
      )}
      {...props}
    >
      {children ?? page}
    </Link>
  );
}

type NavButtonProps = {
  page: number | null;
  disabled?: boolean;
} & Omit<React.ComponentProps<typeof PaginationLink>, "page">;

function PaginationPrevious({
  page,
  disabled = false,
  className,
  ...props
}: NavButtonProps) {
  const [mounted, setMounted] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return (
      <span
        className={cn(
          "size-8 flex items-center justify-center rounded-full bg-foreground/5",
          disabled && "opacity-60",
          className
        )}
      >
        <IconBase icon={ICONS.CHEVRON_LEFT} className="size-4" />
      </span>
    );
  }

  return (
    <PaginationLink
      page={page}
      aria-label="Previous page"
      className={cn(
        "size-8",
        disabled && "opacity-60 pointer-events-none cursor-not-allowed",
        className
      )}
      {...props}
    >
      <IconBase icon={ICONS.CHEVRON_LEFT} className="size-4" />
    </PaginationLink>
  );
}

function PaginationNext({
  page,
  disabled = false,
  className,
  ...props
}: NavButtonProps) {
  const [mounted, setMounted] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return (
      <span
        className={cn(
          "size-8 flex items-center justify-center rounded-full bg-foreground/5",
          disabled && "opacity-60",
          className
        )}
      >
        <IconBase icon={ICONS.CHEVRON_LEFT} className="size-4 rotate-180" />
      </span>
    );
  }

  return (
    <PaginationLink
      page={page}
      aria-label="Next page"
      className={cn(
        "size-8",
        disabled && "opacity-60 pointer-events-none cursor-not-allowed",
        className
      )}
      {...props}
    >
      <IconBase icon={ICONS.CHEVRON_LEFT} className="size-4 rotate-180" />
    </PaginationLink>
  );
}

function PaginationEllipsis({
  className,
  ...props
}: React.ComponentProps<"span">) {
  return (
    <span
      aria-hidden
      data-slot="pagination-ellipsis"
      className={cn("flex size-9 items-center justify-center", className)}
      {...props}
    >
      <MoreHorizontalIcon className="size-4" />
      <span className="sr-only">More pages</span>
    </span>
  );
}

export {
  Pagination,
  PaginationContent,
  PaginationLink,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
};
