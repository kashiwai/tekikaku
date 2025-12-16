import Image from "next/image";
import Link from "next/link";

import { getTranslations } from "next-intl/server";

import { getBonusDetails } from "@/actions/api.actions";
import PageTitle from "@/components/common/page/pageTitle";
import Bonus from "@/components/pages/bonus";
import SectionTitle from "@/components/sections/sectionTitle";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { generateModalPath } from "@/lib/modal";
import { Button } from "@/components/ui/button";

type Props = {
  searchParams: Promise<{
    filter?: string;
    promotionId?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const [t, queryFilters] = await Promise.all([
    getTranslations("BONUS"),
    searchParams,
  ]);

  const currentQueryFilters = new URLSearchParams(queryFilters);

  const depositModalLink = generateModalPath(currentQueryFilters, "wallet", {
    tab: "deposit",
  });
  const wheelModalLink = generateModalPath(currentQueryFilters, "wheel");
  const attendanceModalLink = generateModalPath(
    currentQueryFilters,
    "attendance"
  );

  const data = await getBonusDetails(true);

  return (
    <>
      <div className="space-y-4">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-2.5">
          <PageTitle>{t("BONUS")}</PageTitle>
        </div>

        <Bonus data={data} />
      </div>
      <div className="flex flex-col gap-4">
        <SectionTitle>{t("SPECIAL_ADVENTURES")}</SectionTitle>
        <div className="grid md:grid-cols-3 gap-4">
          <div className="relative flex flex-col w-full rounded-3xl overflow-hidden bg-primary/5">
            <div
              className="absolute w-[427px] h-[427px] -top-76 blur-[25px] left-1/2 -translate-x-1/2 rounded-full"
              style={{
                background: "linear-gradient(90deg, #7D2Dff00, #7D2DFF39)",
              }}
            ></div>
            <div className="flex flex-col items-center p-3">
              <Image
                src={`/imgs/wheel-icon.svg`}
                alt="wheel-icon"
                width={130}
                height={130}
                className="w-[139px] h-[130px]"
              />
              <div className="flex flex-col gap-4 w-full">
                <div className="flex flex-col w-full items-center gap-1">
                  <h6 className="font-extrabold text-xl text-center">
                    {t("LUCKY_WHEEL")}
                  </h6>
                  <p className="text-[13px] text-foreground/80 max-w-[213px] text-center">
                    {t("GET_SPIN_BY_LEVEL_UP_DEPOSIT")}
                  </p>
                </div>
              </div>
            </div>

            <Link
              href={wheelModalLink}
              className="w-full h-10.5 text-sm font-medium flex items-center justify-center rounded-none mt-auto bg-primary/5 border-0"
            >
              {t("SPIN_NOW")}
            </Link>
          </div>
          <div className="relative flex flex-col w-full rounded-3xl overflow-hidden bg-[#2D3BFF]/5">
            <div
              className="absolute w-[427px] h-[427px] -top-76 blur-[25px] left-1/2 -translate-x-1/2 rounded-full"
              style={{
                background: "linear-gradient(90deg, #2D3BFF00, #2D3BFF39)",
              }}
            ></div>
            <div className="flex flex-col items-center p-3">
              <Image
                src={`/imgs/todolist-icon.svg`}
                alt="wheel-icon"
                width={130}
                height={130}
                className="w-[139px] h-[130px]"
              />
              <div className="flex flex-col gap-4 w-full">
                <div className="flex flex-col w-full items-center gap-1">
                  <h6 className="font-extrabold text-xl text-center">
                    {/* GIGI */}
                    {/* {t("ATTENDANCE")} - {data?.attendance.count} {t("DAY")} */}
                  </h6>
                  <p className="text-[13px] text-foreground/80 max-w-[213px] text-center">
                    {t("VISIT_SITE_GET_REWARDS")}
                  </p>
                </div>
                {/* <div className="flex flex-col gap-2 w-full px-2">
                  <div className="w-full flex items-center justify-between">
                    <span className="text-[13px] font-normal text-foreground/80">
                      {t("MY_CURRENT_ATTENDANCE_COUNT")}
                    </span>
                    <span className="text-[13px] font-semibold text-foreground">
                    </span>
                  </div>
                  <div className="w-full flex items-center justify-between">
                    <span className="text-[13px] font-normal text-foreground/80">
                      {t("REMAINING_REWARDS")}
                    </span>
                    <span className="text-[13px] font-semibold text-foreground">
                      {t("BONUS")}
                    </span>
                  </div>
                </div> */}
              </div>
            </div>
            <Link
              href={attendanceModalLink}
              className="w-full h-10.5 text-sm font-medium flex items-center justify-center rounded-none mt-auto bg-[#2D3BFF]/5 border-0"
            >
              {t("VIEW_DETAILS")}
            </Link>
          </div>
        </div>
      </div>

      <div className="flex flex-col gap-4">
        <SectionTitle>{t("DEPOSIT_RELATED")}</SectionTitle>
        <div className="grid md:grid-cols-3 gap-3">
          <div className="w-full bg-foreground/5 rounded-xl p-4">
            <div className="flex items-center justify-between w-full mb-2">
              <h6 className="text-xs font-semibold">{t("NEW_USER_DEPOSIT")}</h6>
              <Button
                variant={`success`}
                size={`sm`}
                className="rounded-xl"
                disabled={
                  !data?.depositEvent.filter(
                    (item) => item.type === "newDeposit"
                  )[0].isUse
                }
              >
                <Link href={depositModalLink}>
                  {/* {data?.newDeposit.applicable
                    ? t("DEPOSIT_CURRENT")
                    : t("DEPOSIT")} */}
                  {t("DEPOSIT")}
                </Link>
              </Button>
            </div>
            <Table>
              <TableHeader>
                <TableRow className="!bg-transparent">
                  <TableHead className="text-center">%</TableHead>
                  <TableHead className="text-center">{t("MAX")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "newDeposit"
                      )[0].percent
                    }
                    %
                  </TableCell>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "newDeposit"
                      )[0].maxBonus
                    }
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div className="w-full bg-foreground/5 rounded-xl p-4">
            <div className="flex items-center justify-between w-full mb-2">
              <h6 className="text-xs font-semibold">
                {t("FIRST_DEPOSIT_DAY")}
              </h6>

              <Button
                variant={`success`}
                size={`sm`}
                className="rounded-xl"
                disabled={
                  !data?.depositEvent.filter(
                    (item) => item.type === "firstDeposit"
                  )[0].isUse
                }
              >
                <Link href={depositModalLink}>
                  {/* {data?.todayFirstDeposit.applicable */}
                  {/* ? t("DEPOSIT_CURRENT") */}
                  {/* : t("DEPOSIT")} */}
                  {t("DEPOSIT")}
                </Link>
              </Button>
            </div>
            <Table>
              <TableHeader>
                <TableRow className="!bg-transparent">
                  <TableHead className="text-center">%</TableHead>
                  <TableHead className="text-center">{t("MAX")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "firstDeposit"
                      )[0].percent
                    }
                    %
                  </TableCell>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "firstDeposit"
                      )[0].maxBonus
                    }
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div className="w-full bg-foreground/5 rounded-xl p-4">
            <div className="flex items-center justify-between w-full mb-2">
              <h6 className="text-xs font-semibold">
                {t("DEPOSIT_EVERY_TIME")}
              </h6>
              <Button
                variant={`success`}
                size={`sm`}
                className="rounded-xl"
                disabled={
                  !data?.depositEvent.filter(
                    (item) => item.type === "everyDeposit"
                  )[0].isUse
                }
              >
                <Link href={depositModalLink}>
                  {/* {data?.everyDeposit.applicable */}
                  {/* ? t("DEPOSIT_CURRENT") */}
                  {/* : t("DEPOSIT")} */}
                  {t("DEPOSIT")}
                </Link>
              </Button>
            </div>
            <Table>
              <TableHeader>
                <TableRow className="!bg-transparent">
                  <TableHead className="text-center">%</TableHead>
                  <TableHead className="text-center">{t("MAX")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "everyDeposit"
                      )[0].percent
                    }
                    %
                  </TableCell>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "everyDeposit"
                      )[0].maxBonus
                    }
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div className="w-full bg-foreground/5 rounded-xl p-4">
            <div className="flex items-center justify-between w-full mb-2">
              <h6 className="text-xs font-semibold">{t("SPECIAL_DEPOSIT")}</h6>
              <Button
                variant={`success`}
                size={`sm`}
                className="rounded-xl"
                disabled={
                  !data?.depositEvent.filter(
                    (item) => item.type === "specialDeposit"
                  )[0].isUse
                }
              >
                <Link href={depositModalLink}>
                  {/* {data?.specialDeposit.applicable */}
                  {/* ? t("DEPOSIT_CURRENT") */}
                  {/* : t("DEPOSIT")} */}
                  {t("DEPOSIT")}
                </Link>
              </Button>
            </div>
            <Table>
              <TableHeader>
                <TableRow className="!bg-transparent">
                  <TableHead className="text-center">%</TableHead>
                  <TableHead className="text-center">{t("MAX")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "specialDeposit"
                      )[0].percent
                    }
                    %
                  </TableCell>
                  <TableCell className="text-center">
                    {
                      data?.depositEvent.filter(
                        (item) => item.type === "specialDeposit"
                      )[0].maxBonus
                    }
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
      </div>
    </>
  );
}
