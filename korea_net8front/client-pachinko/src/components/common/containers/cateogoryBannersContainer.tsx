"use client";

import { HTMLAttributes } from "react";

import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function CategoryBannersContainer({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const stylesWhen = {
    bothIsOpen: "lg:grid-cols-[50%_auto] xl:grid-cols-[60%_auto] aspect-[892/213]",
    onlyOneIsOpen: "lg:grid-cols-[60%_auto] md:aspect-[892/213]",
    bothIsClosed:
      "aspect-[358/177] md:aspect-[892/213]",
  };
  const styles =
    leftSideOpen && rightSideOpen
      ? stylesWhen.bothIsOpen
      : leftSideOpen || rightSideOpen
      ? stylesWhen.onlyOneIsOpen
      : stylesWhen.bothIsClosed;
  return (
    <div
      className={cn(
        `w-full grid md:grid-cols-[60%_auto] aspect-[358/177] gap-2 md:gap-3 ${styles}`,
        props.className
      )}
      {...props}
    >
      {props.children}
    </div>
  );
}
