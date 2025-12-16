"use client";
import { useEffect, useState } from "react";

import { getGameLaunch } from "@/actions/game.action";
import PageBreadcrumb from "@/components/common/breadCrumb";
import GameDetails from "@/components/game-details/GameDetails";
import GameMiniGame from "@/components/game-details/GameMiniGame";
import { useUserStore } from "@/store/user.store";

type Props = {
  id: string;
  type: string;
};

export default function GamePageClient({ id, type }: Props) {
  const user = useUserStore((store) => store.user);
  const [gameData, setGameData] = useState<{
    iframeUrl: string | null;
    title: string;
    api: string;
  } | null>(null);

  useEffect(() => {
    const loadGame = async () => {
      if (!user) {
        setGameData({
          iframeUrl: null,
          title: "",
          api: "",
        });
        return;
      }

      try {
        const res = await getGameLaunch({
          id: decodeURIComponent(id),
        });

        setGameData({
          iframeUrl: res.link,
          title: res.title,
          api: res.api,
        });
      } catch (error: unknown) {
        // eslint-disable-next-line no-console
        console.error(error);
        setGameData({
          iframeUrl: null,
          title: "",
          api: "",
        });
      }
    };

    loadGame();
  }, [user, id]);

  if (!gameData) {
    return <div>Loading...</div>;
  }

  const { iframeUrl, title, api } = gameData;

  return (
    <>
      {iframeUrl && (
        <PageBreadcrumb
          data={[
            {
              to: "/",
              label: "Home",
            },
            {
              to:
                type == "slot" || type == "lobby"
                  ? `/games/${type}`
                  : `/games?game=${type}&page=1`,
              label: type,
            },
            {
              label: title,
            },
          ]}
        />
      )}
      {api === "minigame" ? (
        <GameMiniGame iframeUrl={iframeUrl} />
      ) : api === "honorlink" ? (
        <GameDetails iframeUrl={iframeUrl} />
      ) : null}
    </>
  );
}
