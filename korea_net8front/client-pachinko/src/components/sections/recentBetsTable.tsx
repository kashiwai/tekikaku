"use client";
import Image from "next/image";

import { format, parseISO, isToday, isYesterday } from "date-fns";
import { toZonedTime } from "date-fns-tz";
import { useTranslations } from "next-intl";

import SectionTitle from "@/components/sections/sectionTitle";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useBetHistoryStore } from "@/store/bets.store";
import { useRouter } from "next/navigation";
import numeral from "numeral";

export default function RecentBetsTable() {
  const t = useTranslations("RECENT_BET_TABLE");
  const bets = useBetHistoryStore((store) => store.betsData);
  const router = useRouter();

  return (
    <div className="flex flex-col gap-2">
      <SectionTitle>{t("TITLE")}</SectionTitle>
      <Table>
        <TableHeader>
          <TableRow className="!bg-transparent">
            <TableHead>{t("GAME")}</TableHead>
            <TableHead className="hidden min-[480px]:table-cell">
              {t("USER")}
            </TableHead>
            <TableHead className="hidden md:table-cell">{t("TIME")}</TableHead>
            <TableHead className="hidden md:table-cell">
              {t("BET_AMOUNT")}
            </TableHead>
            <TableHead className="hidden md:table-cell">
              {t("MULTIPLIER")}
            </TableHead>
            <TableHead>{t("PAYOUT")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {bets.betlist.slice(0, 10).map((bet, index) => (
            <TableRow key={index}>
              <TableCell>
                <div
                  className="flex items-center gap-2 cursor-pointer"
                  onClick={() => {
                    router.push(`/games/play/${bet.gameId}`);
                  }}
                >
                  <Image
                    src={bet.gameThumbnail}
                    alt="slot-title"
                    width={20}
                    height={20}
                    className="size-5 rounded-full"
                  />
                  <p className="truncate">{bet.gameTitle}</p>
                </div>
              </TableCell>
              <TableCell className="hidden min-[480px]:table-cell">
                {bet.userName}
              </TableCell>
              <TableCell className="hidden md:table-cell">
                {(() => {
                  const d = toZonedTime(
                    parseISO(bet.time),
                    Intl.DateTimeFormat().resolvedOptions().timeZone
                  );
                  return isToday(d)
                    ? format(d, "h:mma")
                    : isYesterday(d)
                      ? `Yesterday ${format(d, "h:mma")}`
                      : format(d, "MMM d, h:mma");
                })()}
              </TableCell>
              <TableCell className="hidden md:table-cell">
                <div className="flex items-center gap-2">
                  <Image
                    src={`/imgs/coins/btc.svg`}
                    alt="slot-title"
                    width={20}
                    height={20}
                    className="size-5 rounded-full"
                  />
                  <p className="truncate">
                    {numeral(bet.bet).format("0,0.00")}
                  </p>
                </div>
              </TableCell>
              <TableCell className="hidden md:table-cell">
                {numeral(bet.multipler).format("0,0.00")}x
              </TableCell>
              <TableCell
                variant={Number(bet.win) < bet.bet ? "danger" : "success"}
              >
                <div className="flex items-center gap-2">
                  <Image
                    src={`/imgs/coins/btc.svg`}
                    alt="slot-title"
                    width={20}
                    height={20}
                    className="size-5 rounded-full"
                  />
                  <p className="truncate">
                    {numeral(bet.win).format("0,0.00")}
                  </p>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
