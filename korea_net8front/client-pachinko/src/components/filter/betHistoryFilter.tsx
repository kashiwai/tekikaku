"use client";
import React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ChevronDownIcon } from "lucide-react";
import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { FormItem } from "@/components/ui/form";
import { Label } from "@/components/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

import { paginationConfig } from "@/config/pagination.config";
import { ICONS } from "@/constants/icons";
import { useSelect } from "@/hooks/useSelect";
import { searchParamUtils } from "@/utils/searchparam.utils";

export default function BetHistoryFilter() {
  const t = useTranslations("BET_HISTORY");
  const router = useRouter();
  const searchParams = useSearchParams();

  const typeSelect = useSelect();
  const maxSelect = useSelect();
  const startDateSelect = useSelect();
  const endDateSelect = useSelect();

  // ---- State ----
  const [selectedType, setSelectedType] = React.useState<string>(
    searchParams.get("type") || ""
  );

  const [selectedLimit, setSelectedLimit] = React.useState<number>(
    paginationConfig.options.includes(searchParams.get("limit") || "")
      ? Number(searchParams.get("limit"))
      : 25
  );

  const [startDate, setStartDate] = React.useState<Date | undefined>(() => {
    const param = searchParams.get("startDate");
    return param ? new Date(param) : undefined;
  });

  const [endDate, setEndDate] = React.useState<Date | undefined>(() => {
    const param = searchParams.get("endDate");
    return param ? new Date(param) : undefined;
  });

  // ---- Unified updater ----
  const updateQueryParams = React.useCallback(
    (extraUpdates: Record<string, string | undefined> = {}) => {
      const params = new URLSearchParams(searchParams.toString());

      const stateUpdates: Record<string, string | undefined> = {
        type: selectedType || undefined,
        limit: selectedLimit.toString(),
        startDate: startDate ? startDate.toISOString().split("T")[0] : undefined,
        endDate: endDate ? endDate.toISOString().split("T")[0] : undefined,
        ...extraUpdates,
      };

      Object.entries(stateUpdates).forEach(([key, value]) => {
        if (!value || value === "all") {
          params.delete(key);
        } else {
          params.set(key, value);
        }
      });

      const sortedQuery = searchParamUtils.updateParamsSorted(params, {});
      router.push(`?${sortedQuery}`, { scroll: false });
    },
    [selectedType, selectedLimit, startDate, endDate, router, searchParams]
  );

  // ---- UI ----
  return (
    <div className="flex flex-col gap-3">
      <div className="grid grid-cols-2 gap-1.5">
        {/* Type Select */}
        <Select
          open={typeSelect.isOpen}
          value={selectedType}
          onOpenChange={typeSelect.onOpenChange}
          onValueChange={(value) => {
            setSelectedType(value === "all" ? "" : value);
            updateQueryParams({ type: value === "all" ? undefined : value, page: "1" });
          }}
        >
          <SelectTrigger className="bg-foreground/5">
            <SelectValue placeholder={t("SELECT_TYPE")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("ALL")}</SelectItem>
            <SelectItem value="sport">{t("SPORT")}</SelectItem>
            <SelectItem value="casino">{t("CASINO")}</SelectItem>
            <SelectItem value="slot">{t("SLOT")}</SelectItem>
            <SelectItem value="holdem">{t("HOLDEM")}</SelectItem>
            <SelectItem value="minigame">{t("MINIGAME")}</SelectItem>
            <SelectItem value="virtual">{t("VIRTUAL")}</SelectItem>
          </SelectContent>
        </Select>

        {/* Limit Select */}
        <Select
          open={maxSelect.isOpen}
          onOpenChange={maxSelect.onOpenChange}
          value={selectedLimit.toString()}
          onValueChange={(value) => {
            setSelectedLimit(Number(value));
            updateQueryParams({ limit: value, page: "1" });
          }}
        >
          <SelectTrigger className="bg-foreground/5">
            <SelectValue placeholder={t("SELECT_LIMIT")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="10">10</SelectItem>
            <SelectItem value="25">25</SelectItem>
            <SelectItem value="50">50</SelectItem>
            <SelectItem value="100">100</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Dates + Reset */}
      <div className="grid grid-cols-2 md:grid-cols-[1fr_1fr_auto] items-end gap-1.5">
        {/* Start Date */}
        <FormItem>
          <Label className="px-1 text-xs text-foreground/80">{t("START_DATE")}</Label>
          <Popover open={startDateSelect.isOpen} onOpenChange={startDateSelect.onOpenChange}>
            <PopoverTrigger asChild>
              <Button variant="default" className="w-full text-[13px] justify-between font-normal">
                {startDate ? startDate.toLocaleDateString() : t("SELECT_DATE")}
                <ChevronDownIcon />
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto overflow-hidden p-0" align="start">
              <Calendar
                mode="single"
                selected={startDate}
                captionLayout="dropdown"
                onSelect={(date) => {
                  if (!date) return;
                  setStartDate(date);
                  updateQueryParams({ startDate: date.toISOString().split("T")[0], page: "1" });
                  startDateSelect.onClose();
                }}
              />
            </PopoverContent>
          </Popover>
        </FormItem>

        {/* End Date */}
        <FormItem>
          <Label className="px-1 text-xs text-foreground/80">{t("END_DATE")}</Label>
          <Popover open={endDateSelect.isOpen} onOpenChange={endDateSelect.onOpenChange}>
            <PopoverTrigger asChild>
              <Button variant="default" className="w-full text-[13px] justify-between font-normal">
                {endDate ? endDate.toLocaleDateString() : t("SELECT_DATE")}
                <ChevronDownIcon />
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto overflow-hidden p-0" align="start">
              <Calendar
                mode="single"
                selected={endDate}
                captionLayout="dropdown"
                onSelect={(date) => {
                  if (!date) return;
                  setEndDate(date);
                  updateQueryParams({ endDate: date.toISOString().split("T")[0], page: "1" });
                  endDateSelect.onClose();
                }}
              />
            </PopoverContent>
          </Popover>
        </FormItem>
      </div>
    </div>
  );
}