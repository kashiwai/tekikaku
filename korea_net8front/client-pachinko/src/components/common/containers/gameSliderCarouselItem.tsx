"use client";

import { HTMLAttributes } from "react";

import { CarouselItem } from "@/components/ui/carousel";
import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function GameSliderCarouselItem({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const basisStyleWhen = {
    bothIsOpen: "lg:basis-1/6 xl:basis-1/8",
    onlyOneIsOpen: "lg:basis-1/6 xl:basis-1/9",
    bothIsClosed: "lg:basis-[calc(100%/9)]",
  };
  const basisDefaultStyles =
    leftSideOpen && rightSideOpen
      ? basisStyleWhen.bothIsOpen
      : leftSideOpen || rightSideOpen
      ? basisStyleWhen.onlyOneIsOpen
      : basisStyleWhen.bothIsClosed;
  return (
    <CarouselItem
      className={cn(`pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 ${basisDefaultStyles}`, props.className)}
      {...props}
    >
      {props.children}
    </CarouselItem>
  );
}
