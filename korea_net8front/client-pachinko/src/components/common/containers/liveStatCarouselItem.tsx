"use client";

import { HTMLAttributes } from "react";

import { CarouselItem } from "@/components/ui/carousel";
import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";

type Props = HTMLAttributes<HTMLDivElement>;

export default function LiveStatCarouselItem({ ...props }: Props) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const leftSideOpen = useLayoutStore((store) => store.isAsideOpen);
  const rightSideOpen = isNotificationOpen;

  const basisStyleWhen = {
    bothIsOpen: "lg:basis-[calc(100%/7)] xl:basis-[calc(100%/11)]",
    onlyOneIsOpen: "lg:basis-[calc(100%/10)] xl:basis-[calc(100%/14)]",
    bothIsClosed: "lg:basis-[calc(100%/14)]",
  };
  const basisDefaultStyles =
    leftSideOpen && rightSideOpen
      ? basisStyleWhen.bothIsOpen
      : leftSideOpen || rightSideOpen
      ? basisStyleWhen.onlyOneIsOpen
      : basisStyleWhen.bothIsClosed;
  return (
    <CarouselItem
      className={cn(`-full pl-4 basis-1/4 min-[440px]:basis-[calc(100%/6)] sm:basis-[calc(100%/8)] md:basis-[calc(100%/12)] ${basisDefaultStyles}`, props.className)}
      {...props}
    >
      {props.children}
    </CarouselItem>
  );
}
