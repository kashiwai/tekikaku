"use client";

import * as React from "react";

import useEmblaCarousel, {
  type UseEmblaCarouselType,
} from "embla-carousel-react";
import { ArrowLeft, ArrowRight } from "lucide-react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

type CarouselApi = UseEmblaCarouselType[1];
type UseCarouselParameters = Parameters<typeof useEmblaCarousel>;
type CarouselOptions = UseCarouselParameters[0];
type CarouselPlugin = UseCarouselParameters[1];

type CarouselProps = {
  opts?: CarouselOptions & { autoplay?: boolean; autoplayDelay?: number };
  plugins?: CarouselPlugin;
  orientation?: "horizontal" | "vertical";
  setApi?: (api: CarouselApi) => void;
};

type CarouselContextProps = {
  carouselRef: ReturnType<typeof useEmblaCarousel>[0];
  api: ReturnType<typeof useEmblaCarousel>[1];
  scrollPrev: () => void;
  scrollNext: () => void;
  canScrollPrev: boolean;
  canScrollNext: boolean;
} & CarouselProps;

const CarouselContext = React.createContext<CarouselContextProps | null>(null);

function useCarousel() {
  const context = React.useContext(CarouselContext);

  if (!context) {
    throw new Error("useCarousel must be used within a <Carousel />");
  }

  return context;
}

function Carousel({
  orientation = "horizontal",
  opts,
  setApi,
  plugins,
  className,
  children,
  ...props
}: React.ComponentProps<"div"> & CarouselProps) {
  const [carouselRef, api] = useEmblaCarousel(
    {
      ...opts,
      align: opts?.align || "start",
      axis: orientation === "horizontal" ? "x" : "y",
      loop: true, // Enable looping
    },
    plugins
  );
  const [canScrollPrev, setCanScrollPrev] = React.useState(false);
  const [canScrollNext, setCanScrollNext] = React.useState(false);

  const onSelect = React.useCallback((api: CarouselApi) => {
    if (!api) return;
    setCanScrollPrev(api.canScrollPrev());
    setCanScrollNext(api.canScrollNext());
  }, []);

  const scrollPrev = React.useCallback(() => {
    api?.scrollPrev();
  }, [api]);

  const scrollNext = React.useCallback(() => {
    api?.scrollNext();
  }, [api]);

  const handleKeyDown = React.useCallback(
    (event: React.KeyboardEvent<HTMLDivElement>) => {
      if (event.key === "ArrowLeft") {
        event.preventDefault();
        scrollPrev();
      } else if (event.key === "ArrowRight") {
        event.preventDefault();
        scrollNext();
      }
    },
    [scrollPrev, scrollNext]
  );

  React.useEffect(() => {
    if (!api || !setApi) return;
    setApi(api);
  }, [api, setApi]);

  React.useEffect(() => {
    if (!api || !opts?.autoplay) return;
    
    let interval: NodeJS.Timeout | null = null;
    let isHovered = false;

    const startAutoplay = () => {
      if (interval) clearInterval(interval);
      interval = setInterval(() => {
        if (!isHovered) {
          // Use scrollNext which now properly handles looping
          api.scrollNext();
        }
      }, opts.autoplayDelay || 3000);
    };

    const stopAutoplay = () => {
      if (interval) clearInterval(interval);
      interval = null;
    };

    // Start autoplay
    startAutoplay();

    // Pause when hovering
    const carouselEl = api.containerNode();
    const handleEnter = () => {
      isHovered = true;
      stopAutoplay();
    };
    const handleLeave = () => {
      isHovered = false;
      startAutoplay();
    };

    carouselEl.addEventListener("mouseenter", handleEnter);
    carouselEl.addEventListener("mouseleave", handleLeave);

    // Also pause when focused
    carouselEl.addEventListener("focusin", handleEnter);
    carouselEl.addEventListener("focusout", handleLeave);

    return () => {
      stopAutoplay();
      carouselEl.removeEventListener("mouseenter", handleEnter);
      carouselEl.removeEventListener("mouseleave", handleLeave);
      carouselEl.removeEventListener("focusin", handleEnter);
      carouselEl.removeEventListener("focusout", handleLeave);
    };
  }, [api, opts?.autoplay, opts?.autoplayDelay]);

  React.useEffect(() => {
    if (!api) return;
    onSelect(api);
    api.on("reInit", onSelect);
    api.on("select", onSelect);

    return () => {
      api?.off("select", onSelect);
      api?.off("reInit", onSelect);
    };
  }, [api, onSelect]);

  return (
    <CarouselContext.Provider
      value={{
        carouselRef,
        api,
        opts,
        orientation:
          orientation || (opts?.axis === "y" ? "vertical" : "horizontal"),
        scrollPrev,
        scrollNext,
        canScrollPrev,
        canScrollNext,
      }}
    >
      <div
        onKeyDownCapture={handleKeyDown}
        className={cn("relative", className)}
        role="region"
        aria-roledescription="carousel"
        data-slot="carousel"
        {...props}
      >
        {children}
      </div>
    </CarouselContext.Provider>
  );
}

function CarouselContent({
  visible = false,
  className,
  ...props
}: React.ComponentProps<"div"> & { visible?: boolean }) {
  const { carouselRef, orientation } = useCarousel();

  return (
    <div
      ref={carouselRef}
      className={!visible ? "overflow-hidden" : "overflow-visible"}
      data-slot="carousel-content"
    >
      <div
        className={cn(
          "flex",
          orientation === "horizontal" ? "-ml-4" : "-mt-4 flex-col",
          className
        )}
        {...props}
      />
    </div>
  );
}

function CarouselItem({ className, ...props }: React.ComponentProps<"div">) {
  const { orientation } = useCarousel();

  return (
    <div
      role="group"
      aria-roledescription="slide"
      data-slot="carousel-item"
      className={cn(
        "min-w-0 shrink-0 grow-0 basis-full",
        orientation === "horizontal" ? "pl-4" : "pt-4",
        className
      )}
      {...props}
    />
  );
}

function CarouselPrevious({
  className,
  variant = "default",
  size = "icon_default",
  ...props
}: React.ComponentProps<typeof Button>) {
  const { orientation, scrollPrev, canScrollPrev } = useCarousel();

  return (
    <Button
      data-slot="carousel-previous"
      variant={variant}
      size={size}
      className={cn(
        "absolute size-8 rounded-full",
        orientation === "horizontal"
          ? "top-1/2 -left-12 -translate-y-1/2"
          : "-top-12 left-1/2 -translate-x-1/2 rotate-90",
        className
      )}
      disabled={!canScrollPrev}
      onClick={scrollPrev}
      {...props}
    >
      {props.children ? props.children : <ArrowLeft />}
      <span className="sr-only">Previous slide</span>
    </Button>
  );
}

function CarouselNext({
  className,
  variant = "default",
  size = "icon_default",
  ...props
}: React.ComponentProps<typeof Button>) {
  const { orientation, scrollNext, canScrollNext } = useCarousel();

  return (
    <Button
      data-slot="carousel-next"
      variant={variant}
      size={size}
      className={cn(
        "absolute size-8 rounded-full",
        orientation === "horizontal"
          ? "top-1/2 -right-12 -translate-y-1/2"
          : "-bottom-12 left-1/2 -translate-x-1/2 rotate-90",
        className
      )}
      disabled={!canScrollNext}
      onClick={scrollNext}
      {...props}
    >
      {props.children ? props.children : <ArrowRight />}
      <span className="sr-only">Next slide</span>
    </Button>
  );
}

export function CarouselPagination({
  className,
  dotClassname = "",
  ...props
}: React.ComponentProps<"div"> & { dotClassname?: string }) {
  const { api } = useCarousel();
  const [scrollSnaps, setScrollSnaps] = React.useState<number[]>([]);
  const [selectedIndex, setSelectedIndex] = React.useState(0);

  React.useEffect(() => {
    if (!api) return;

    const onSelect = () => {
      setSelectedIndex(api.selectedScrollSnap());
    };

    setScrollSnaps(api.scrollSnapList());
    setSelectedIndex(api.selectedScrollSnap());

    api.on("select", onSelect);
    api.on("reInit", onSelect);

    return () => {
      api.off("select", onSelect);
      api.off("reInit", onSelect);
    };
  }, [api]);

  const scrollTo = (index: number) => api?.scrollTo(index);

  return (
    <div
      className={cn("flex items-center justify-center gap-2 mt-4", className)}
      {...props}
    >
      {scrollSnaps.map((_, index) => (
        <button
          key={index}
          onClick={() => scrollTo(index)}
          className={cn(
            "w-2 h-2 rounded-full bg-neutral/80 transition-colors cursor-pointer",
            index === selectedIndex ? "bg-primary" : "",
            dotClassname
          )}
          aria-label={`Go to slide ${index + 1}`}
        />
      ))}
    </div>
  );
}

function CarouselIndicator({
  className,
  ...props
}: React.HTMLAttributes<HTMLInputElement>) {
  const { api } = useCarousel();
  const [selectedIndex, setSelectedIndex] = React.useState(0);
  const [totalSlides, setTotalSlides] = React.useState(0);

  // Set up the indicator logic when api is available
  React.useEffect(() => {
    if (!api) return;

    const updateIndicator = () => {
      setSelectedIndex(api.selectedScrollSnap());
      setTotalSlides(api.scrollSnapList().length);
    };

    updateIndicator();

    api.on("select", updateIndicator);
    api.on("reInit", updateIndicator);

    return () => {
      api.off("select", updateIndicator);
      api.off("reInit", updateIndicator);
    };
  }, [api]);

  return (
    <div
      className={cn("text-xs font-medium text-foreground/80", className)}
      {...props}
    >
      {selectedIndex + 1}/{totalSlides}
    </div>
  );
}

export {
  type CarouselApi,
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselPrevious,
  CarouselNext,
  CarouselIndicator,
};
