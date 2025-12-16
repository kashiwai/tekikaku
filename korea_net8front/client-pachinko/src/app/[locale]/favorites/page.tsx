import { getTranslations } from "next-intl/server";

import Card from "@/components/cards/game-cards/slotCard";
import SlotGridContainer from "@/components/common/containers/slotGridContainer";
import NoResults from "@/components/common/page/NoResults";
import PagePagination from "@/components/common/page/pagePagination";
import PageTitle from "@/components/common/page/pageTitle";
import { gameConfig } from "@/config/game.config";
import { gamesApi } from "@/lib/api/games.api";
import { FavoriteItem } from "@/types/favorite";
import { searchParamUtils } from "@/utils/searchparam.utils";
import { GameItem } from "@/types/game.types";

type Props = {
  searchParams: Promise<{
    page?: string;
    limit?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const [t, queryParams, { list: data, total }] = await Promise.all([
    getTranslations("FAVORITES"),
    searchParams,
    gamesApi.favorites(),
  ]);

  const { page } = searchParamUtils.getParams(queryParams, {
    page: "1",
  });

  const activePage = Number(page);

  return (
    <>
      <div className="space-y-4">
        <PageTitle>{t("FAVORITES")}</PageTitle>
      </div>

      {total == 0 ? (
        <NoResults className="py-28">
          <p className="text-foreground/60 max-w-[178px] text-center">
            {t("NO_FAVORITES_YET")}
          </p>
        </NoResults>
      ) : (
        <SlotGridContainer>
          {data.map((game: GameItem, index: number) => (
            <Card
              key={game.gameId}
              href={`/games/${game.type}/${game.gameId}`}
              priority={index <= 12}
              {...game}
            />
          ))}
        </SlotGridContainer>
      )}

      <PagePagination
        activePage={activePage}
        total={total}
        limit={gameConfig.pagination.limit}
      />
    </>
  );
}
