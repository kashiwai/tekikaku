import Image from "next/image";
import { redirect } from "next/navigation";

import CasinoList from "@/components/sections/casinoList";
import SlotList from "@/components/sections/slotList";
import { gameConfig, GameType } from "@/config/game.config";
import { bannersApi } from "@/lib/api/banners.api";
import Pachinko from "@/components/sections/pachinko";

type Props = {
  params: Promise<{ type: string }>;
};
export default async function Page({ params }: Props) {
  const [{ type }, banners] = await Promise.all([params, bannersApi.banners()]);
  const gameType = type as GameType;

  if (!gameConfig.gameType.includes(gameType)) return redirect("/");

  return (
    <div>
      <div className="w-full grid lg:items-center lg:grid-cols-[auto_760px] gap-6">
        {(gameType === "slot" || gameType === "casino") && (
          <Image
            src={`${
              banners.game.find((game) => game.title === type)?.thumbnail
            }`}
            alt={type}
            width={500}
            height={500}
            className="w-full max-w-[760px]"
          />
        )}
        {gameType === "slot" ? (
          <SlotList className="grid-cols-2 md:grid-cols-3 w-full grid gap-3 gap-y-4 md:gap-y-8 gap-x-0 md:gap-x-3" />
        ) : gameType === "casino" ? (
          <CasinoList className="grid-cols-2 md:grid-cols-3 w-full grid gap-3 gap-y-4 md:gap-y-8 gap-x-0 md:gap-x-3" />
        ) : (
          <Pachinko userId="user_123" modelId="HOKUTO4GO" />
        )}
      </div>
    </div>
  );
}
