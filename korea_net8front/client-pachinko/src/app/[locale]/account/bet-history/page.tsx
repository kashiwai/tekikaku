import { getTranslations } from "next-intl/server";

import PagePagination from "@/components/common/page/pagePagination";
import BetHistoryFilter from "@/components/filter/betHistoryFilter";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import TabWrapper from "@/components/wrapper/tabWrapper";
import { bettingApi } from "@/lib/api/betting.api";
import { formatDate } from "@/lib/utils";
import { paginationUtils } from "@/utils/pagination.utils";
import { searchParamUtils } from "@/utils/searchparam.utils";
import FilterResetBtn from "@/components/filter/filterResetBtn";
import BetInfoBtn from "@/components/common/btns/betInfoBtn";
import { Suspense } from "react";
import ContentLoader from "@/components/loader/contentLoader";
import numeral from "numeral";

type Props = {
  searchParams: Promise<{
    page?: string;
    type?: string;
    limit?: string;
    startDate?: string;
    endDate?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const [t, queryParams] = await Promise.all([
    getTranslations("BET_HISTORY"),
    searchParams,
  ]);

  const { page, limit, startDate, endDate, type } = searchParamUtils.getParams(
    queryParams,
    {
      page: "1",
      limit: paginationUtils.checkLimit(queryParams.limit, "25"),
      startDate: "",
      endDate: "",
      type: "",
    }
  );

  return (
    <TabWrapper
      title={t("TITLE")}
      className="grid"
      endContent={
        <FilterResetBtn
          filters={["startDate", "endDate", "type", "limit", "page"]}
        />
      }
    >
      <BetHistoryFilter />

      <Suspense
        key={`${page}-${limit}-${startDate}-${endDate}-${type}`}
        fallback={<ContentLoader className="w-full h-[350px]" />}
      >
        <Dynamic
          page={page}
          limit={limit}
          startDate={startDate}
          endDate={endDate}
          type={type}
        />
      </Suspense>
    </TabWrapper>
  );
}

async function Dynamic({
  page,
  limit,
  startDate,
  endDate,
  type,
}: {
  page: string;
  limit: string;
  startDate: string;
  endDate: string;
  type: string;
}) {
  const t = await getTranslations("BET_HISTORY");

  const { list, total } = await bettingApi.betHistories({
    page,
    limit,
    startDate,
    endDate,
    type,
  });
  const activePage = Number(page);

  return (
    <>
    </>
  );
}
