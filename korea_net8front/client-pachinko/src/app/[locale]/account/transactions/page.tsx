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
import { Button } from "@/components/ui/button";
import FilterResetBtn from "@/components/filter/filterResetBtn";
import { Suspense } from "react";
import ContentLoader from "@/components/loader/contentLoader";

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
      type: "",
    }
  );

  return (
    <TabWrapper
      title={t("TITLE")}
      endContent={
        <FilterResetBtn
          filters={["startDate", "endDate", "type", "limit", "page"]}
        />
      }
      className="grid"
    >
      <TransactionsFilter pageName="transactions" />
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
  const [t, { list, total }] = await Promise.all([
    getTranslations("TRANSACTIONS"),
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
      <Table className="overflow-auto">
        <TableHeader>
          <TableRow className="!bg-transparent">
            <TableHead>{t("TYPE")}</TableHead>
            <TableHead>{t("ORDER_NUMBER")}</TableHead>
            <TableHead>{t("AMOUNT")}</TableHead>
            <TableHead>{t("TYPE")}</TableHead>
            <TableHead>{t("DATE")}</TableHead>
            <TableHead>{t("STATUS")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {list.map((item: TransactionItem, index: number) => (
            <TableRow key={index}>
              <TableCell>{t(item.balanceHistory.type)}</TableCell>
              <TableCell>{item.balanceHistory.orderNumber}</TableCell>
              <TableCell>
                {item.balanceHistory.paidType.money
                  ? item.balanceHistory.money.amount
                  : item.balanceHistory.paidType.locked ||
                    item.balanceHistory.paidType.unlocked
                  ? (item.balanceHistory.paidType.locked
                      ? item.balanceHistory.locked.amount
                      : 0) +
                    (item.balanceHistory.paidType.unlocked
                      ? item.balanceHistory.unlocked.amount
                      : 0)
                  : item.balanceHistory.paidType.losing
                  ? item.balanceHistory.losing.amount
                  : item.balanceHistory.paidType.rolling
                  ? item.balanceHistory.rolling.amount
                  : 0}
              </TableCell>
              <TableCell>
                {item.balanceHistory.paidType.money
                  ? t("MONEY")
                  : item.balanceHistory.paidType.locked
                  ? t("LOCKED")
                  : item.balanceHistory.paidType.unlocked
                  ? t("UNLOCKED")
                  : item.balanceHistory.paidType.losing
                  ? t("LOSING")
                  : t("ROLLING")}
              </TableCell>
              <TableCell>{formatDate(item.balanceHistory.createdAt)}</TableCell>
              <TableCell
                className={
                  item.balanceHistory.status === "complete"
                    ? "text-success"
                    : "text-danger"
                }
              >
                {t(item.balanceHistory.status)}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>

      <PagePagination
        activePage={activePage}
        total={total}
        limit={Number(limit)}
      />
    </>
  );
}
