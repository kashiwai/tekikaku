import { getGameLaunch } from "@/actions/game.action";
import PageBreadcrumb from "@/components/common/breadCrumb";
import GameDetails from "@/components/game-details/GameDetails";
import GameMiniGame from "@/components/game-details/GameMiniGame";
import { getSession } from "@/lib/getSession";
import { GameLaunchResponse } from "@/types/game.types";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

export default async function Page({ params }: Props) {
  const [param, user] = await Promise.all([params, getSession()]);

  let gameData: GameLaunchResponse | null = null;

  if (user) {
    gameData = await getGameLaunch({ id: decodeURIComponent(param.id) });
  }

  const getBreadcrumbPath = (type: string) => {
    if (type === "slot" || type === "lobby") {
      return `/games/${type}`;
    }
    return `/games?game=${type}&page=1`;
  };

  return gameData ? (
    <div className="space-y-2">
      {gameData.link && (
        <PageBreadcrumb
          data={[
            {
              to: "/",
              label: "Home",
            },
            {
              to: getBreadcrumbPath(gameData.type),
              label: gameData.type,
            },
            {
              label: gameData.title,
            },
          ]}
        />
      )}

      {gameData.api === "minigame" && (
        <GameMiniGame iframeUrl={gameData.link} />
      )}

      {gameData.api === "honorlink" && (
        <GameDetails iframeUrl={gameData.link} />
      )}
    </div>
  ) : null;
}
