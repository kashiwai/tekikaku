"use client";
import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function SlotGridContainer({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const gridStylesWhen = {
    bothIsOpen: "lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-7",
    onlyOneIsOpen: "lg:grid-cols-5 xl:grid-cols-7",
    bothIsClosed: "",
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
        `grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 ${gridDefaultStyles} gap-3`,
        props.className
      )}
      {...props}
    >
      {props.children}
    </div>
  );
}
