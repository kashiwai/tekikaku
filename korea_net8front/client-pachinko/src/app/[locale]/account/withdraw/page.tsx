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
import WithdrawalForm from "@/components/forms/wallet/withdrawalForm";
import { Suspense } from "react";
import ContentLoader from "@/components/loader/contentLoader";
import FilterResetBtn from "@/components/filter/filterResetBtn";
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
      type: "withdraw",
    }
  );

  return (
    <>
      <TabWrapper
        title={t("WITHDRAW")}
        className="grid"
        endContent={
          <FilterResetBtn
            filters={["startDate", "endDate", "type", "limit", "page"]}
          />
        }
      >
        <WithdrawalForm col={false} />
      </TabWrapper>

      <TabWrapper title={t("WITHDRAW_HISTORY")} className="grid">
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
      <TransactionsFilter pageName="withdraw" />

      <PagePagination
        activePage={activePage}
        total={total}
        limit={Number(limit)}
      />
    </>
  );
}
