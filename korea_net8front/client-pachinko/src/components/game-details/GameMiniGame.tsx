"use client";
import { useEffect, useRef, useState } from "react";

import Image from "next/image";

import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";

export default function GameMiniGame({
  iframeUrl,
}: {
  iframeUrl: string | null;
}) {
  const authModal = useModal("auth");
  const [isFullScreen, setIsFullScreen] = useState(false);
  const [ifrUrl, setIfrUrl] = useState<string | null>(iframeUrl);
  const user = useUserStore((state) => state.user);
  const [amount, setAmount] = useState<string>("");
  const t = useTranslations("GAME_DETAIL");
  const [activeBet, setActiveBet] = useState<{
    value: string;
    label: string;
  } | null>(null);

  const [isLoading] = useState(false);
  const [hasNextPage] = useState(true);
  const scrollRef = useRef<HTMLDivElement>(null);
  // const currentPageRef = useRef(1);
  const [tab, setActiveTab] = useState("betting-cart");

  useEffect(() => {
    const container = scrollRef.current;

    if (!container) return;

    const onScroll = () => {
      if (isLoading || !hasNextPage) return;

      const threshold = 10;
      const isBottom =
        container.scrollHeight - container.scrollTop - container.clientHeight <
        threshold;

      if (isBottom) {
        // fetchGames(searchVal, null, currentPageRef.current + 1);
      }
    };

    container.addEventListener("scroll", onScroll);
    return () => container.removeEventListener("scroll", onScroll);
  }, [hasNextPage, isLoading]);

  useEffect(() => {
    setIfrUrl(iframeUrl);
  }, [iframeUrl]);

  const bettingData = [
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },

    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },

    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },
  ];

  const bettingHistory = [
    {
      value: "1.95",
      label: "1.95x",
    },
    {
      value: "1.25",
      label: "1.25x",
    },
    {
      value: "2.95",
      label: "2.95x",
    },
    {
      value: "6.95",
      label: "6.95x",
    },

    {
      value: "1.15",
      label: "1.15x",
    },
    {
      value: "3.25",
      label: "3.25x",
    },
    {
      value: "4.95",
      label: "4.95x",
    },
    {
      value: "5.95",
      label: "5.95x",
    },

    {
      value: "6.25",
      label: "3.25x",
    },
    {
      value: "7.95",
      label: "4.95x",
    },
    {
      value: "8.95",
      label: "5.95x",
    },

    {
      value: "9.25",
      label: "3.25x",
    },
    {
      value: "10.95",
      label: "4.95x",
    },
    {
      value: "11.95",
      label: "5.95x",
    },

    {
      value: "10.15",
      label: "12.15x",
    },
    {
      value: "13.25",
      label: "14.25x",
    },
    {
      value: "15.95",
      label: "15.95x",
    },
    {
      value: "16.95",
      label: "16.95x",
    },

    {
      value: "17.25",
      label: "17.25x",
    },
    {
      value: "18.95",
      label: "18.95x",
    },
    {
      value: "19.95",
      label: "19.95x",
    },

    {
      value: "9.25",
      label: "3.25x",
    },
    {
      value: "10.95",
      label: "4.95x",
    },
    {
      value: "11.95",
      label: "5.95x",
    },
  ];

  return (
    <div className="grid lg:grid-cols-[1fr_250px] gap-3 overflow-hidden">
      <div
        className={`${isFullScreen
            ? "fixed top-0 left-0 w-full flex flex-col h-screen z-[999999] rounded-none"
            : "rounded-3xl"
          } overflow-hidden bg-[#1a2332]`}
      >
        {!user ? (
          <div
            className="flex flex-col justify-center items-center gap-1 py-[18px] mt-2"
            style={{ aspectRatio: 1152 / 726 }}
          >
            <Button onClick={() => authModal.onOpen({ tab: "login" })}>
              {t("LOGIN_TO_CONTINUE")}
            </Button>
          </div>
        ) : (
          <div
            className={`${isFullScreen ? "h-[calc(100%-65px)]" : "h-[90%]"
              } relative`}
            style={{ aspectRatio: 1152 / 726 }}
          >
            {ifrUrl && ifrUrl != "" && user && (
              <iframe
                src={ifrUrl}
                style={{ width: "100%", height: "100%" }}
              ></iframe>
            )}
          </div>
        )}
        <div className="flex w-full bg-foreground/5 h-[55px] md:h-[65px] px-3">
          <div className="ml-auto flex items-center gap-2.5">
            <Button
              onClick={() => setIsFullScreen((state) => !state)}
              variant={`default`}
              size={`icon_sm`}
              className="rounded-xl border-transparent"
            >
              <IconBase icon={ICONS.FULLSCREEN} className="size-4" />
            </Button>
          </div>
        </div>
      </div>
      <div className="flex-1 h-full rounded-2xl bg-foreground/5 p-1">
        <Tabs
          value={tab}
          onValueChange={(val) => setActiveTab(val)}
          defaultValue="betting-cart"
          className="flex-1 h-full"
        >
          <TabsList className="w-full h-12">
            <TabsTrigger
              value="betting-cart"
              className="w-full grid place-content-center p-2 data-[state=active]:bg-foreground/10"
            >
              Betting Cart
            </TabsTrigger>
            <TabsTrigger
              value="my-bets"
              className="w-full grid place-content-center p- data-[state=active]:bg-foreground/10"
            >
              My Bets
            </TabsTrigger>
          </TabsList>
          <div className="grid grid-cols-2 py-2 divide-x divide-primary">
            <div className="text-center text-sm text-primary font-bold">
              Round 249
            </div>
            <div className="text-center text-sm text-primary font-bold">
              Ends: 2:51
            </div>
          </div>
          <div style={{ display: tab === "betting-cart" ? "flex" : "none" }} className="grid h-full flex-1">
            <div className="flex-1 h-full overflow-auto">
              <div className="grid grid-cols-2 gap-1 py-4 border-y border-y-foreground/10 p-2 h-[400px]  overflow-auto custom-scrollbar">
                {bettingData.map((bet, index: number) => (
                  <Button
                    onClick={() => setActiveBet(bet)}
                    key={index}
                    variant={
                      activeBet?.value === bet.value ? "primary" : "default"
                    }
                    size={`sm`}
                  >
                    {bet.label}
                  </Button>
                ))}
              </div>
            </div>
          </div>
          <div style={{ display: tab === "my-bets" ? "flex" : "none" }}>
            <div className="flex-1 h-full">
              <div
                className="grid grid-cols-2 gap-1 py-4 border-y border-y-foreground/10 p-2 h-[400px]  overflow-auto custom-scrollbar"
                ref={scrollRef}
              >
                {bettingHistory.map((bet) => (
                  <Button
                    onClick={() => setActiveBet(bet)}
                    key={bet.value}
                    variant={
                      activeBet?.value === bet.value ? "primary" : "default"
                    }
                    size={`sm`}
                    disabled
                  >
                    {bet.label}
                  </Button>
                ))}
              </div>
            </div>
          </div>
          <div className="w-full h-[140px] flex flex-col">
            <div className="relative">
              <Image
                src={`/imgs/coins/btc.svg`}
                alt="btc"
                width={40}
                height={40}
                className="w-5 h-5 min-w-5 absolute left-3 top-1/2 -translate-y-1/2"
              />
              <Input
                type="number"
                placeholder={"Enter bet"}
                className="pl-10"
                onChange={(e) => setAmount(e.target.value)}
                value={amount}
              />
            </div>
            <div className="grid grid-cols-2 gap-2 p-1">
              <div className=" bg-foreground/5 text-xs rounded-xl font-semibold text-center py-1.5 items-center flex justify-center">
                X {activeBet?.value ? activeBet.value : "-"}
              </div>
              <div className="flex flex-col bg-foreground/5 rounded-xl text-xs font-semibold text-center py-1.5 items-center justify-center">
                <span>Winning</span>
                <span>
                  {Number(activeBet?.value) * Number(amount)
                    ? (Number(activeBet?.value) * Number(amount)).toFixed(1)
                    : "0"}
                </span>
              </div>
            </div>
            <Button variant={`primary`} className="mt-1 rounded-xl">
              Bet Now (05:29)
            </Button>
          </div>
        </Tabs>
      </div>
    </div>
  );
}
