"use client";

import { HTMLAttributes } from "react";

import { CarouselItem } from "@/components/ui/carousel";
import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function HeroCarouselItem({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const basisStyleWhen = {
    bothIsOpen: "md:basis-full xl:basis-1/2",
    onlyOneIsOpen: "xl:basis-1/2 2xl:basis-1/3",
    bothIsClosed: "sm:basis-1/2 2xl:basis-1/3",
  };
  const basisDefaultStyles =
    leftSideOpen && rightSideOpen
      ? basisStyleWhen.bothIsOpen
      : leftSideOpen || rightSideOpen
      ? basisStyleWhen.onlyOneIsOpen
      : basisStyleWhen.bothIsClosed;
  return (
    <CarouselItem
      className={cn(`-full pl-4 ${basisDefaultStyles}`, props.className)}
      {...props}
    >
      {props.children}
    </CarouselItem>
  );
}


