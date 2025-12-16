"use client";
import { ChangeEvent, useEffect, useRef, useState } from "react";

import { useTranslations } from "next-intl";

import Card from "@/components/cards/game-cards/slotCard";
import IconBase from "@/components/icon/iconBase";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { gameConfig } from "@/config/game.config";
import { ICONS } from "@/constants/icons";
import { getGameList } from "@/helpers/games.helpers";
import { ModalControls } from "@/hooks/useModal";
import { GameItem } from "@/types/game.types";

import ModalLayout from "./modalLayout";

type Props = ModalControls<"search">;

export default function SearchModal({ isOpen, onClose }: Props) {
  const t = useTranslations("SEARCH");

  const gameTypes = [
    { key: "slot", value: t("SLOT") },
    { key: "casino", value: t("CASINO") },
    { key: "minigame", value: t("MINIGAME") },
    { key: "holdem", value: t("HOLDEM") },
    { key: "virtual", value: t("VIRTUAL") },
    { key: "sports", value: t("SPORTS") },
  ];

  const [gameType, setGameType] = useState<{ key: string; value: string }>({
    key: "casino",
    value: t("CASINO"),
  });
  const [games, setGames] = useState<GameItem[]>([]);
  const [searchVal, setSearchVal] = useState("");
  const [hasNextPage, setHasNextPage] = useState(true);
  const [isLoading, setIsLoading] = useState(false);

  const scrollRef = useRef<HTMLDivElement>(null);
  const currentPageRef = useRef(1);

  const fetchGames = async (
    search = "",
    newGameType: { key: string; value: string } | null,
    page = 1
  ) => {
    if (isLoading || (!hasNextPage && page !== 1)) return;

    setIsLoading(true);

    const { list: data, total } = await getGameList({
      game: newGameType ? newGameType.key : gameType.key,
      type: "",
      title: search,
      page,
      limit: gameConfig.pagination.limit,
    });

    setGames((prev) => (page === 1 ? data : [...prev, ...data]));

    const totalPages = Math.ceil(total / 35);
    const nextPage = page + 1;

    currentPageRef.current = page;
    setHasNextPage(nextPage <= totalPages);

    setIsLoading(false);
  };

  const onSearch = async (e: ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setSearchVal(value);

    if (value.trim().length >= 3) {
      currentPageRef.current = 1;
      setHasNextPage(true);
      await fetchGames(value, null, 1);
    } else {
      setGames([]);
    }
  };

  useEffect(() => {
    const container = scrollRef.current;
    if (!container) return;

    const onScroll = () => {
      if (isLoading || !hasNextPage) return;

      const threshold = 200;
      const isBottom =
        container.scrollHeight - container.scrollTop - container.clientHeight <
        threshold;

      if (isBottom) {
        fetchGames(searchVal, null, currentPageRef.current + 1);
      }
    };

    container.addEventListener("scroll", onScroll);
    return () => container.removeEventListener("scroll", onScroll);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchVal, hasNextPage, isLoading]);

  const shouldShowLoaderFirstTime = isLoading && games.length === 0;
  const shouldShowBottomLoader = isLoading && games.length > 0;

  return (
    <ModalLayout
      isOpen={isOpen}
      onClose={() => {
        setSearchVal("");
        setGames([]);
        currentPageRef.current = 1;
        setHasNextPage(true);
        onClose();
      }}
      bg="transparent"
      size="lg"
      className="!p-0 !gap-2 !mb-auto mt-0 !px-4 md:!mt-[50px] !rounded-none"
      closeBtnClassname="!top-1 !right-5 !bg-transparent"
      ariaLabel={t("SEARCH_GAMES")}
    >
      <div className="flex items-center bg-background dark:bg-[#1A202C] rounded-xl">
        <div className="flex h-full items-center bg-background dark:bg-[#1A202C] rounded-xl">
          <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-1 border border-foreground/10 rounded-l-xl px-4 h-full min-w-[120px]">
              <span className="text-xs font-medium">{gameType.value}</span>
              <IconBase icon={ICONS.ARROW_DOWN} className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent
              align="start"
              className="flex flex-col gap-0 linear-background rounded-xl border border-foreground/10 w-[150px] mt-1"
            >
              {gameTypes.map((item: { key: string; value: string }) => (
                <DropdownMenuItem
                  key={item.key}
                  onClick={async () => {
                    setGameType(item);
                    currentPageRef.current = 1;
                    setHasNextPage(true);
                    await fetchGames(searchVal, item, 1);
                  }}
                  className="text-xs font-medium px-3 py-2 hover:bg-foreground/5 transition-all"
                >
                  {item.value}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
        <div className="flex-1 flex items-center h-full rounded-l-none border border-foreground/10 rounded-xl px-3 gap-3 ">
          <IconBase icon={ICONS.SEARCH} className="size-4" />
          <input
            onChange={onSearch}
            value={searchVal}
            placeholder={t("SEARCH_MIN_CHARS")}
            className="h-10 w-full rounded-r-xl text-sm outline-none pr-8 focus:border-foreground/20 flex-1"
          />
        </div>
      </div>

      <div className="flex bg-background dark:bg-[#1A202C] rounded-xl p-[10px] min-h-[200px]">
        {searchVal.trim().length === 0 ? (
          <div className="w-full flex flex-col h-full">
            <div className="flex w-full h-full items-center justify-center text-xs font-medium text-foreground/60">
              <p>{t("SEARCH_MIN_CHARS")}</p>
            </div>
          </div>
        ) : (
          <div
            ref={scrollRef}
            className="w-full max-h-[calc(100vh-300px)] overflow-auto custom-scrollbar pr-1"
          >
            {shouldShowLoaderFirstTime ? (
              <div className="flex items-center justify-center py-24">
                <IconBase
                  icon={ICONS.SPINNER}
                  className="size-6 animate-spin"
                />
              </div>
            ) : games.length > 0 ? (
              <>
                <div className="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-7 gap-3">
                  {games.map((game: GameItem, index: number) => (
                    <Card
                      key={game.gameId}
                      href={`/games/play/${game.gameId}`}
                      priority={index <= 12}
                      {...game}
                    />
                  ))}
                </div>

                {shouldShowBottomLoader && (
                  <div className="flex flex-col items-center gap-1 py-[18px] mt-4">
                    <IconBase
                      icon={ICONS.SPINNER}
                      className="size-6 animate-spin"
                    />
                    <span className="text-xs font-normal">
                      {t("LOADING_MORE")}
                    </span>
                  </div>
                )}
              </>
            ) : (
              <>
                <div className="h-full flex flex-col items-center justify-center text-center  text-xs font-medium text-foreground/60">
                  <p className="py-12">{t("NO_GAMES_FOUND")}</p>
                  {/* <Carousel className="grid gap-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-1.5">
                        <IconBase icon={ICONS.CHERRY} className="size-4" />
                        <span className="text-sm font-semibold">
                          {t("SUGGESTED_GAMES")}
                        </span>
                      </div>
                      <div className="flex items-center gap-3">
                        <Link
                          href={`#`}
                          className="text-xs font-normal hover:underline"
                        >
                          {t("VIEW_ALL")}
                        </Link>
                        <div className="flex items-center gap-1.5">
                          <CarouselPrevious className="relative left-0 translate-0 border border-neutral rounded-full size-5">
                            <IconBase
                              icon={ICONS.CHEVRON_LEFT}
                              className="size-4"
                            />
                          </CarouselPrevious>
                          <CarouselNext className="relative left-0 translate-0 border border-neutral rounded-full size-5">
                            <IconBase
                              icon={ICONS.CHEVRON_RIGHT}
                              className="size-4"
                            />
                          </CarouselNext>
                        </div>
                      </div>
                    </div>

                    <CarouselContent className="-ml-2">
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                      <CarouselItem className="pl-2 basis-1/3 sm:basis-1/5 md:basis-1/7 lg:basis-1/9">
                        <SlotCard
                          href="#"
                          src="/imgs/slots/slot1.svg"
                          title={t("ROULETTE_SLOT_LIVE_LOBBY")}
                          style={{ aspectRatio: 100 / 100 }}
                        />
                      </CarouselItem>
                    </CarouselContent>
                  </Carousel> */}
                </div>
              </>
            )}
          </div>
        )}
      </div>
    </ModalLayout>
  );
}
