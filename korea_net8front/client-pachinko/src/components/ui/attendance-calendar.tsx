"use client";

import * as React from "react";
import { CheckIcon, Loader } from "lucide-react";
import { DayPicker } from "react-day-picker";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { format } from "date-fns";

type Props = {
  claimedDays: string[]; // User timezone ISO strings
  selected?: string; // selected day as ISO string
  onSelect?: (isoDate: string | undefined) => void;
  onClaim: (isoDate: string, date: Date) => void;
  className?: string;
  loading: boolean;
};

export function AttendanceCalendar({
  claimedDays,
  selected,
  onSelect,
  onClaim,
  className,
  loading,
}: Props) {
  const today = new Date();

  const isDayClaimed = (day: Date) => {
    const dayString = format(day, "yyyy-MM-dd");
    return claimedDays.includes(dayString);
  };

  // Check if the day is today in user's timezone
  const isToday = (day: Date) =>
    day.getFullYear() === today.getFullYear() &&
    day.getMonth() === today.getMonth() &&
    day.getDate() === today.getDate();

  return (
    <DayPicker
      mode="single"
      selected={selected ? new Date(selected) : undefined}
      onSelect={(date) => onSelect?.(date?.toISOString())}
      className={cn(
        "w-full p-2 rounded-2xl bg-foreground/5 border border-foreground/10",
        className
      )}
      classNames={{
        month_caption: "text-center mt-1 mb-3 text-sm font-medium",
        button_previous: "hidden",
        button_next: "hidden",
        month_grid: "w-full",
        weekday: "text-sm font-medium pb-1",
      }}
      month={today}
      disabled={[{ before: today }, { after: today }]}
      components={{
        DayButton: ({ day, ...props }) => {
          const claimed = isDayClaimed(day.date);
          const todayUser = isToday(day.date);

          return (
            <Button
              {...props}
              size="icon_sm"
              onClick={() => {
                if (loading) return;
                if (todayUser) onClaim(day.date.toISOString(), day.date);
              }}
              className={cn(
                "relative flex items-center justify-center w-full h-8 bg-transparent border-transparent overflow-hidden",
                claimed ? "" : "hover:bg-primary/15 transition-all",
                (loading && todayUser) || (claimed && "opacity-80 pointer-events-none")
              )}
            >
              {todayUser && loading ? (
                <Loader className="animate-spin absolute left-1/2 top-1/2 -translate-1/2" />
              ) : (
                day.date.getDate()
              )}

              {claimed && <CheckIcon className="absolute top-0 right-0.5 size-4 text-success" />}
            </Button>
          );
        },
      }}
    />
  );
}