export const revalidate = 0;

import { Suspense } from "react";

import { getTranslations } from "next-intl/server";

import Card from "@/components/cards/game-cards/slotCard";
import SlotGridContainer from "@/components/common/containers/slotGridContainer";
import NoResults from "@/components/common/page/NoResults";
import PageFilterBtns from "@/components/common/page/pageFilterBtns";
import PagePagination from "@/components/common/page/pagePagination";
import PageTitle from "@/components/common/page/pageTitle";
import ContentLoader from "@/components/loader/contentLoader";
import { gameConfig } from "@/config/game.config";
import { gamesApi } from "@/lib/api/games.api";
import { GameItem } from "@/types/game.types";
import { searchParamUtils } from "@/utils/searchparam.utils";
import PageSearch from "@/components/common/page/pageSearch";

type Props = {
  searchParams: Promise<{
    type?: string;
    game?: string;
    limit?: string;
    vendor?: string;
    page?: string;
    search?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const [t, queryParams] = await Promise.all([
    getTranslations("GAME"),
    searchParams,
  ]);
  
  const { type, game, limit, vendor, page, search } =
    searchParamUtils.getParams(queryParams, {
      type: "",
      game: "",
      limit: String(gameConfig.pagination.limit),
      vendor: "",
      page: "1",
      search: "",
    });

  const activePage = Number(page);

  const params = searchParamUtils.buildSearchParams({
    game,
    type,
    vendor,
    page,
    limit,
    search,
  });

  const { total, types } = await gamesApi.games({
    type,
    game,
    limit,
    vendor,
    page,
    search,
    ssr: true,
  });

  return (
    <>
      <div className="flex flex-col gap-2.5 w-full">
        <div className="w-full flex items-center justify-between">
          <PageTitle>{game ? t(game.toUpperCase()) : t("GAME")}</PageTitle>
          <div className="w-full max-w-[360px]">
            <PageSearch
              queryKey="search"
              placeholder="Search by title min 3 char"
              minChars={3}
            />
          </div>
        </div>
        {vendor === "" && types.length > 0 && (
          <PageFilterBtns data={["", ...types]} activeValue={type} />
        )}
      </div>

      {total == 0 ? (
        <NoResults className="py-28">
          <p className="text-foreground/60 max-w-[178px] text-center">
            {t("NO_RESULT_FOUND")}
          </p>
        </NoResults>
      ) : (
        <Suspense
          key={`${params}`}
          fallback={<ContentLoader className="w-full h-[68svh]" />}
        >
          <DynamicContent {...{ type, game, limit, vendor, page, search }} />
        </Suspense>
      )}

      <PagePagination
        activePage={activePage}
        total={total}
        limit={gameConfig.pagination.limit}
      />
    </>
  );
}

const DynamicContent = async ({
  type,
  game,
  limit,
  vendor,
  search,
  page,
}: {
  type: string;
  game: string;
  limit: string;
  vendor: string;
  search: string;
  page: string;
}) => {
  const { list: games } = await gamesApi.games({
    type,
    game,
    limit,
    vendor,
    page,
    search,
    ssr: true,
  });

  return (
    <SlotGridContainer>
      {games.map((game: GameItem, index: number) => (
        <Card
          key={game.gameId}
          href={`/games/play/${game.gameId}`}
          priority={index <= 12}
          {...game}
        />
      ))}
    </SlotGridContainer>
  );
};
