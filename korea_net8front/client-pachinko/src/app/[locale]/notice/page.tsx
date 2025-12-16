import { Suspense } from "react";

import { getLocale, getTranslations } from "next-intl/server";

import NoticeCard from "@/components/cards/notice-card/NoticeCard";
import NoResults from "@/components/common/page/NoResults";
import PagePagination from "@/components/common/page/pagePagination";
import PageTitle from "@/components/common/page/pageTitle";
import TabFilter from "@/components/filter/tabFilter";
import ContentLoader from "@/components/loader/contentLoader";
import { noticeConfig } from "@/config/notice.config";
import { noticeApi } from "@/lib/api/notice.api";
import { formatDate } from "@/lib/utils";
import { PageNoticeItem } from "@/types/notice.types";
import { searchParamUtils } from "@/utils/searchparam.utils";
import { LocaleKey } from "@/i18n/request";

type Props = {
  searchParams: Promise<{
    tab?: string;
    page?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const [t, queryParams] = await Promise.all([
    getTranslations("NOTICE"),
    searchParams,
  ]);

  const { page, tab: isUse } = searchParamUtils.getParams(queryParams, {
    page: "1",
    tab: "all",
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2.5">
        <PageTitle>{t("NOTICE")}</PageTitle>
        <div className="flex md:w-[443px]">
          <TabFilter
            tabs={["all", "active", "inactive"]}
            value={isUse}
            searchParam="tab"
            pageName="NOTICE"
          />
        </div>
      </div>
      <Suspense
        key={`${page}-${isUse}`}
        fallback={<ContentLoader className="w-full h-[350px]" />}
      >
        <Dynamic page={page} isUse={isUse} />
      </Suspense>
    </div>
  );
}

async function Dynamic({ page, isUse }: { page: string; isUse: string }) {
  const [t, locale, { list, total }] = await Promise.all([
    getTranslations("NOTICE"),
    getLocale() as Promise<LocaleKey>,
    noticeApi.noticeList({
      page,
      limit: noticeConfig.pagination.limit.toString(),
      isUse: isUse === "all" ? "" : isUse === "active" ? "true" : "false",
    }),
  ]);

  return (
    <div className="space-y-2 md:space-y-4">
      {list.map((item: PageNoticeItem, index: number) => (
        <NoticeCard
          key={index}
          title={item.title}
          date={formatDate(item.startDate)}
          status={item.isUse}
          description={item.content}
          locale={locale}
        />
      ))}

      {list.length == 0 && (
        <NoResults className="py-28">
          <p className="text-foreground/60 max-w-[178px] text-center">
            {t("NO_NOTICE_YET")}
          </p>
        </NoResults>
      )}

      <PagePagination
        activePage={Number(page)}
        total={total}
        limit={noticeConfig.pagination.limit}
      />
    </div>
  );
}
