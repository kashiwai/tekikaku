import { getTranslations } from "next-intl/server";

import PagePagination from "@/components/common/page/pagePagination";
import TransactionsFilter from "@/components/filter/transactionsFileter";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import TabWrapper from "@/components/wrapper/tabWrapper";
import { transactionApi } from "@/lib/api/transaction.api";
import { TransactionItem } from "@/types/transaction";
import { paginationUtils } from "@/utils/pagination.utils";
import { searchParamUtils } from "@/utils/searchparam.utils";
import { formatDate } from "@/lib/utils";
import DepositForm from "@/components/forms/wallet/depositForm";
import FilterResetBtn from "@/components/filter/filterResetBtn";
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
    getTranslations("TRANSACTIONS"),
    searchParams,
  ]);

  const { page, limit, startDate, endDate, type } = searchParamUtils.getParams(
    queryParams,
    {
      page: "1",
      limit: paginationUtils.checkLimit(queryParams.limit, "25"),
      startDate: "",
      endDate: "",
      type: "deposit",
    }
  );

  return (
    <>
      <TabWrapper title={t("DEPOSIT")} className="grid">
        <DepositForm col={false} />

        <div className="mt-4"></div>
      </TabWrapper>

      <TabWrapper
        title={t("DEPOSIT_HISTORY")}
        endContent={
          <FilterResetBtn
            filters={["startDate", "endDate", "type", "limit", "page"]}
          />
        }
      >
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
    </>
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
  const [t, { list, total }] = await Promise.all([
    getTranslations("BET_HISTORY"),
    transactionApi.transactions({
      page,
      limit,
      startDate,
      endDate,
      type,
    }),
  ]);

  const activePage = Number(page);

  return (
    <>
      <TransactionsFilter pageName="deposit" />

      <PagePagination
        activePage={activePage}
        total={total}
        limit={Number(limit)}
      />
    </>
  );
}
