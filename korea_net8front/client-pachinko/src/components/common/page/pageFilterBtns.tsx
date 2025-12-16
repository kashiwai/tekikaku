"use client";
import { HTMLAttributes, useEffect, useRef, useState } from "react";

import { useSearchParams, useRouter } from "next/navigation";

import { useTranslations } from "next-intl";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

type Props = {
  activeValue: string;
  data: string[];
} & HTMLAttributes<HTMLDivElement>;

const ScrollOverlay = ({
  className,
  ...props
}: HTMLAttributes<HTMLDivElement>) => {
  return (
    <div
      className={cn(
        "absolute top-0 w-12 h-full z-10 pointer-events-none",
        className
      )}
      {...props}
    ></div>
  );
};

export default function PageFilterBtns({
  activeValue,
  data,
  className,
  ...props
}: Props) {
  const router = useRouter();
  const scrollRef = useRef<HTMLDivElement>(null);
  const [showLeftOverlay, setShowLeftOverlay] = useState(true);
  const [showRightOverlay, setShowRightOverlay] = useState(false);
  const [hasScroll, setHasScroll] = useState(false);
  const [hasHover, setHasHover] = useState(false);
  const searchParams = useSearchParams();
  const t = useTranslations("COMMON");

  const createTypeUrl = (type: string) => {
    const params = new URLSearchParams(searchParams);

    if (type) {
      params.set("type", type);
    } else {
      params.delete("type");
    }

    return `?${params.toString()}`;
  };

  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;

    const checkScroll = () => {
      // Show overlays
      const isAtStart = el.scrollLeft <= 1;
      const isAtEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;

      setShowLeftOverlay(!isAtStart);
      setShowRightOverlay(!isAtEnd);

      // Check if scroll exists
      setHasScroll(el.scrollWidth > el.clientWidth);
    };

    checkScroll();

    el.addEventListener("scroll", checkScroll);
    window.addEventListener("resize", checkScroll);

    return () => {
      el.removeEventListener("scroll", checkScroll);
      window.removeEventListener("resize", checkScroll);
    };
  }, [data]);

  return (
    <>
      <div className="group relative grid">
        {showLeftOverlay && (
          <ScrollOverlay
            className="left-0"
            style={{
              background:
                "linear-gradient(90deg, var(--background), #00000000)",
            }}
          />
        )}

        <div
          ref={scrollRef}
          className={cn(
            `flex items-center gap-1.5 pb-[14px] overflow-auto hover-scroll`,
            hasScroll && "has-scroll",
            hasHover && "hovered",
            className
          )}
          onMouseEnter={() => setHasHover(true)}
          onTouchStart={() => setHasHover(true)}
          onMouseLeave={() => setHasHover(false)}
          onTouchEnd={() => setHasHover(false)}
          {...props}
        >
          {data.map((btn: string, index: number) => (
            <Button
              key={index}
              variant={btn === activeValue ? "primary" : "default"}
              className="text-[13px]"
              onClick={() => {
                router.replace(createTypeUrl(btn));
                router.refresh();
              }}
            >
              {btn == "" ? t("ALL") : btn}
            </Button>
          ))}
        </div>

        {showRightOverlay && (
          <ScrollOverlay
            className="right-0"
            style={{
              background:
                "linear-gradient(280deg, var(--background), #00000000)",
            }}
          />
        )}
      </div>
    </>
  );
}
