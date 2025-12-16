import Image from "next/image";
import Link from "next/link";

import { useLocale, useTranslations } from "next-intl";

import { Button } from "@/components/ui/button";
import { LocaleKey } from "@/i18n/request";

type Props = {
  promotionId: string;
  src: string;
  title: Record<string, string | null>;
  endDate: string;
  status: boolean;
  searchParams: URLSearchParams;
};

export default function PromotionCard({
  promotionId,
  src,
  title,
  endDate,
  status,
  searchParams,
}: Props) {
  const locale = useLocale() as LocaleKey;
  const params = new URLSearchParams(searchParams.toString());
  params.set("promotionId", promotionId);
  params.set("modal", "promotion");
  const href = `?${params.toString()}`;

  return (
    <Link
      href={href}
      role="button"
      tabIndex={0}
      className="group w-full flex flex-col rounded-2xl bg-foreground/5"
      aria-label={`View details of ${title[locale]} promotion`}
    >
      <div
        className="relative w-full rounded-2xl overflow-hidden"
        style={{ aspectRatio: 377 / 163 }}
      >
        <Image
          src={src}
          alt={title[locale] ?? ""}
          fill
          className="group-hover:scale-110 duration-500 transition-all"
        />
      </div>
      <PromotionInfo
        title={title}
        endDate={endDate}
        status={status}
        locale={locale}
      />
    </Link>
  );
}

export const PromotionInfo = ({
  title,
  endDate,
  status,
  locale,
}: Omit<Props, "src" | "promotionId" | "searchParams"> & {
  locale: LocaleKey;
}) => {
  const t = useTranslations("PROMOTIONS");

  return (
    <div className="flex items-center justify-between p-3">
      <div className="grid gap-1">
        <h6 className="text-sm font-semibold truncate">{title[locale]}</h6>
        <p className="text-xs text-foreground/60 truncate">{endDate}</p>
      </div>
      <Button
        size={`sm`}
        variant={status ? "success_ghost" : "danger_ghost"}
        className="rounded-xl text-xs font-semibold"
      >
        {status ? t("processing") : t("ended")}
      </Button>
    </div>
  );
};
