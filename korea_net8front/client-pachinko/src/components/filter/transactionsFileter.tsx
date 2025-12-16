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

export default function TransactionsFilter({ pageName }: { pageName: string }) {
  const t = useTranslations("TRANSACTIONS");
  const router = useRouter();
  const searchParams = useSearchParams();

  const typeSelect = useSelect();
  const maxSelect = useSelect();
  const startDateSelect = useSelect();
  const endDateSelect = useSelect();

  // ---- State ----
  const [selectedType, setSelectedType] = React.useState<string>(() => {
    const param = searchParams.get("type");
    if (param) return param;
    if (pageName === "deposit") return "deposit";
    if (pageName === "withdraw") return "withdraw";
    return "all";
  });

  const [selectedLimit, setSelectedLimit] = React.useState<number>(() => {
    const param = searchParams.get("limit");
    return paginationConfig.options.includes(param || "")
      ? Number(param)
      : 25;
  });

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

      const sorted = searchParamUtils.updateParamsSorted(params, {});
      router.push(`?${sorted}`, { scroll: false });
    },
    [selectedType, selectedLimit, startDate, endDate, searchParams, router]
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
            <SelectItem value="all" hidden={pageName !== "transactions"}>
              {t("ALL")}
            </SelectItem>
            <SelectItem value="deposit" hidden={pageName === "withdraw"}>
              {t("DEPOSIT")}
            </SelectItem>
            <SelectItem value="withdraw" hidden={pageName === "deposit"}>
              {t("WITHDRAW")}
            </SelectItem>

            {/* Transactions-specific types */}
            <SelectItem value="본사 회원 금액지급" hidden={pageName !== "transactions"}>
              {t("HEAD_PAYMENT")}
            </SelectItem>
            <SelectItem value="본사 회원 금액회수" hidden={pageName !== "transactions"}>
              {t("HEAD_COLLECT")}
            </SelectItem>
            <SelectItem value="총판 회원 금액지급" hidden={pageName !== "transactions"}>
              {t("AGENCY_PAYMENT")}
            </SelectItem>
            <SelectItem value="총판 회원 금액회수" hidden={pageName !== "transactions"}>
              {t("AGENCY_COLLECT")}
            </SelectItem>
            <SelectItem value="claim-bonus" hidden={pageName !== "transactions"}>
              {t("BONUS_CONVERT")}
            </SelectItem>
            <SelectItem value="unlock-bonus" hidden={pageName !== "transactions"}>
              {t("BONUS_UNLOCK")}
            </SelectItem>
            <SelectItem value="보너스지급" hidden={pageName !== "transactions"}>
              {t("BONUS_PAYMENT")}
            </SelectItem>
            <SelectItem value="보너스회수" hidden={pageName !== "transactions"}>
              {t("BONUS_COLLECT")}
            </SelectItem>
            <SelectItem value="deposit-event" hidden={pageName !== "transactions"}>
              {t("DEPOSIT_EVENT")}
            </SelectItem>
            <SelectItem value="claim-coupon" hidden={pageName !== "transactions"}>
              {t("COUPON")}
            </SelectItem>
            <SelectItem value="claim-attendance" hidden={pageName !== "transactions"}>
              {t("ATTENDACNE")}
            </SelectItem>
            <SelectItem value="claim-roulette" hidden={pageName !== "transactions"}>
              {t("ROULLETE")}
            </SelectItem>
            <SelectItem value="rolling-cashback-user" hidden={pageName !== "transactions"}>
              {t("CASHBACK")}
            </SelectItem>
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

      {/* Date Pickers */}
      <div className="grid grid-cols-2 items-end gap-1.5">
        {/* Start Date */}
        <FormItem>
          <Label className="px-1 text-xs text-foreground/80">{t("START_DATE")}</Label>
          <Popover open={startDateSelect.isOpen} onOpenChange={startDateSelect.onOpenChange}>
            <PopoverTrigger asChild>
              <Button variant="default" className="w-full text-[13px] justify-between font-normal !pr-3 border-foreground/5">
                {startDate ? startDate.toLocaleDateString() : t("SELECT_DATE")}
                <ChevronDownIcon className="text-foreground/40" />
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
                  updateQueryParams({
                    startDate: date.toISOString().split("T")[0],
                    page: "1",
                  });
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
              <Button variant="default" className="w-full text-[13px] justify-between font-normal !pr-3 border-foreground/5">
                {endDate ? endDate.toLocaleDateString() : t("SELECT_DATE")}
                <ChevronDownIcon className="text-foreground/40" />
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
                  updateQueryParams({
                    endDate: date.toISOString().split("T")[0],
                    page: "1",
                  });
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