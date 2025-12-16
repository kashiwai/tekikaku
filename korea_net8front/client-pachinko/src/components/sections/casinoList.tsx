import { useTranslations } from "next-intl";

import CasinoSlotListContainer from "@/components/common/containers/casinoSlotListContainer";
import IconBase from "@/components/icon/iconBase";
import SectionTitle from "@/components/sections/sectionTitle";
import { ICONS } from "@/constants/icons";
import CasinoListCard from "./casinoCard";

// 🔑 Config-driven setup
const casinoProviders = (t: ReturnType<typeof useTranslations>) => [
  {
    href: "/games/play/evolution_baccarat_sicbo",
    title: t("EVOLUTION"),
    logo: {
      src: "/imgs/slot-casino-assets/evolution_logo.png",
      alt: "evolution",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/evolution.png",
      alt: "evolution",
    },
  },
  {
    href: "/games/play/101",
    title: t("PRAGMATIC"),
    logo: {
      src: "/imgs/slot-casino-assets/pragmatic_logo.png",
      alt: "pragmatic",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/pragmatic.png",
      alt: "pragmatic",
    },
  },
  {
    href: "/games/play/dgcasino",
    title: t("DREAM_CASINO"),
    logo: {
      src: "/imgs/slot-casino-assets/dream_logo.png",
      alt: "Dream Casino",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/dream.png",
      alt: "Dream Casino",
    },
  },
  {
    href: "/games/play/0",
    title: t("ASIA_CASINO"),
    logo: { src: "/imgs/slot-casino-assets/asia_logo.png", alt: "Asia Casino" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/asia.png",
      alt: "Asia Casino",
    },
  },
  {
    href: "/games/play/wmcasino",
    title: t("WM_CASINO"),
    logo: { src: "/imgs/slot-casino-assets/wm_logo.png", alt: "WM Casino" },
    additionalImg: { src: "/imgs/slot-casino-assets/WM.png", alt: "WM Casino" },
  },
  {
    href: "/games/play/SMG_titaniumLiveGames_MP_Baccarat",
    title: t("MICROGAMING_CASINO"),
    logo: {
      src: "/imgs/slot-casino-assets/micro_logo.png",
      alt: "Microgaming Casino",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/micro.png",
      alt: "Microgaming Casino",
    },
  },
];

export default function CasinoList({ className }: { className?: string }) {
  const t = useTranslations("CASINO_PROVIDERS");

  return (
    <div className="flex flex-col gap-4 md:gap-10">
      <SectionTitle className="flex items-center gap-2">
        <IconBase icon={ICONS.POKER_CHIP} className="size-5" />
        {t("CASINO")}
      </SectionTitle>

      <CasinoSlotListContainer className={className}>
        {casinoProviders(t).map((provider) => (
          <CasinoListCard key={provider.href} {...provider} />
        ))}
      </CasinoSlotListContainer>
    </div>
  );
}
