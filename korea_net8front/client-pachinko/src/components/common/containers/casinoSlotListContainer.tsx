"use client";

import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function CasinoSlotListContainer({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore(
    (store) => store.isNotificationOpen
  );
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const gridStylesWhen = {
    bothIsOpen: "grid-cols-2 min-[1160px]:grid-cols-3 2xl:grid-cols-4",
    onlyOneIsOpen: "grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5",
    bothIsClosed: "grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6",
  };
  const gridDefaultStyles =
    leftSideOpen && rightSideOpen
      ? gridStylesWhen.bothIsOpen
      : leftSideOpen || rightSideOpen
      ? gridStylesWhen.onlyOneIsOpen
      : gridStylesWhen.bothIsClosed;
  return (
    <div
      className={cn(
        `w-full grid ${gridDefaultStyles} gap-y-4 md:gap-y-3 gap-x-0 md:gap-x-3`,
        props.className
      )}
      {...props}
    >
      {props.children}
    </div>
  );
}
