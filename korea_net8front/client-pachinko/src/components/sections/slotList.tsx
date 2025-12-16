import { useTranslations } from "next-intl";

import CasinoSlotListContainer from "@/components/common/containers/casinoSlotListContainer";
import IconBase from "@/components/icon/iconBase";
import SectionTitle from "@/components/sections/sectionTitle";
import { ROUTES } from "@/config/routes.config";
import { ICONS } from "@/constants/icons";
import SlotListCard from "./slotListCard";

// 🔑 Config-driven slot providers
const slotProviders = (t: ReturnType<typeof useTranslations>) => [
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=PragmaticPlay`,
    title: t("PRAGMATIC"),
    logo: {
      src: "/imgs/slot-casino-assets/pragmatic_logo.png",
      alt: "pragmatic",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/pragmatic_slot.png",
      alt: "pragmatic",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Booongo`,
    title: t("BOOONGO"),
    logo: { src: "/imgs/slot-casino-assets/booongo_logo.png", alt: "Booongo" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/booongo_slot.png",
      alt: "Booongo",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=CQ9`,
    title: t("CQ9"),
    logo: { src: "/imgs/slot-casino-assets/cq9_logo.png", alt: "CQ9" },
    additionalImg: { src: "/imgs/slot-casino-assets/cq9_slot.png", alt: "CQ9" },
    additionalImgClassName:
      "max-w-[58%] group-hover:w-[63%] group-hover:max-w-[63%] right-[10px]",
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Habanero`,
    title: t("HABANERO"),
    logo: {
      src: "/imgs/slot-casino-assets/habanero_logo.png",
      alt: "Habanero",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/habanero_slot.png",
      alt: "Habanero",
    },
    additionalImgClassName:
      "bottom-[20px] max-w-[58%] group-hover:w-[63%] group-hover:max-w-[63%]",
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=PG Soft`,
    title: t("PGSOFT"),
    logo: { src: "/imgs/slot-casino-assets/pgsoft_logo.png", alt: "PGSoft" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/pgsoft_slot.png",
      alt: "PGSoft",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Blueprint Gaming`,
    title: t("BLUEPRINT_SLOT"),
    logo: {
      src: "/imgs/slot-casino-assets/blueprint_logo.png",
      alt: "Blueprint_Slot",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/blueprint_slot.png",
      alt: "Blueprint_Slot",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Wazdan`,
    title: t("WAZDAN"),
    logo: { src: "/imgs/slot-casino-assets/wazdan_logo.png", alt: "Wazdan" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/wazdan_slot.png",
      alt: "Wazdan",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=GameArt`,
    title: t("GAMEART"),
    logo: { src: "/imgs/slot-casino-assets/gameart_logo.png", alt: "GameArt" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/gameart_slot.png",
      alt: "GameArt",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=1X2 Gaming`,
    title: t("1X2_GAMING"),
    logo: {
      src: "/imgs/slot-casino-assets/1x2game_logo.png",
      alt: "1x2 Gaming",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/1x2game_slot.png",
      alt: "1x2 Gaming",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Nolimit City`,
    title: t("NOLIMIT"),
    logo: { src: "/imgs/slot-casino-assets/nolimit_logo.png", alt: "Nolimit" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/avatarux_slot.png",
      alt: "Nolimit",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Relax Gaming`,
    title: t("RELAX_GAME"),
    logo: { src: "/imgs/slot-casino-assets/relax_logo.png", alt: "Relax" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/relax_slot.png",
      alt: "Relax",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=Thunderkick`,
    title: t("THUNDERKICK"),
    logo: {
      src: "/imgs/slot-casino-assets/thunderkick_logo.png",
      alt: "Thunderkick",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/netent_slot.png",
      alt: "Thunderkick",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=evoplay`,
    title: t("EVOPLAY"),
    logo: { src: "/imgs/slot-casino-assets/evoplay_logo.png", alt: "Evoplay" },
    additionalImg: {
      src: "/imgs/slot-casino-assets/evoplay_slot.png",
      alt: "Evoplay",
    },
  },
  {
    href: `${ROUTES.SLOT_LIST}?game=slot&vendor=PlayStar`,
    title: t("PLAYSTAR"),
    logo: {
      src: "/imgs/slot-casino-assets/playstar_logo.png",
      alt: "PlayStar",
    },
    additionalImg: {
      src: "/imgs/slot-casino-assets/playstar_slot.png",
      alt: "PlayStar",
    },
  },
];

export default function SlotList({ className }: { className?: string }) {
  const t = useTranslations("SLOT_PROVIDERS");

  return (
    <div className="flex flex-col gap-4 md:gap-10">
      <SectionTitle className="flex items-center gap-2">
        <IconBase icon={ICONS.CHERRY} className="size-5" />
        {t("SLOT")}
      </SectionTitle>

      <CasinoSlotListContainer className={className}>
        {slotProviders(t).map((provider) => (
          <SlotListCard key={provider.href} {...provider} />
        ))}
      </CasinoSlotListContainer>
    </div>
  );
}
