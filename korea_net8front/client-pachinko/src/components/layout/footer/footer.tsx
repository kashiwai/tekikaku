import { useTranslations } from "next-intl";

export default function Footer() {
  const t = useTranslations()

  return (
    <footer className="flex md:pt-24 pb-24 lg:pb-12">
      <span className="mx-auto text-xs md:text-sm font-medium md:font-semibold text-foreground/80 px-4 max-w-[580px] text-center">
        {t("FOOTER")}
      </span>
    </footer>
  );
}
