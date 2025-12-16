import { Suspense } from "react";

import Image from "next/image";

import { getLocale, getTranslations } from "next-intl/server";

import BannerWrapper from "@/components/banners/BannerWrapper";
import PromotionCard from "@/components/cards/promotion/PromotionCard";
import NoResults from "@/components/common/page/NoResults";
import PagePagination from "@/components/common/page/pagePagination";
import PageTitle from "@/components/common/page/pageTitle";
import TabFilter from "@/components/filter/tabFilter";
import ContentLoader from "@/components/loader/contentLoader";
import { promotionConfig } from "@/config/promotion.config";
import { promotionApi } from "@/lib/api/promotion.api";
import { formatDate } from "@/lib/utils";
import { searchParamUtils } from "@/utils/searchparam.utils";

type Props = {
  searchParams: Promise<{
    filter?: string;
    promotionId?: string;
    page?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const t = await getTranslations("PROMOTIONS");

  const queryParams = await searchParams;
  const { page, limit, isUse } = searchParamUtils.getParams(queryParams, {
    page: "1",
    limit: promotionConfig.pagination.limit.toString(),
    isUse: "processing",
  });

  return (
    <>
      <div className="space-y-4">
        <PageTitle>{t("PROMOTIONS")}</PageTitle>

        <BannerWrapper className="h-[240px] md:px-9 px-4 md:h-[320px] pr-4 text-white from-primary/80 overflow-hidden rounded-3xl">
          <div>{""}</div>
          <Image
            src={`https://storage.goodfriendszone.com/uploads/permanent/1760672687693_7a6b3176-f919-47e2-bcf9-17f7ceebddd1.png`}
            alt="promotion-banner"
            fill
            className="object-cover"
          />
        </BannerWrapper>

        <TabFilter
          value={isUse}
          tabs={["processing", "ended"]}
          searchParam="isUse"
          className="w-full md:max-w-[307px] bg-foreground/5"
          pageName="PROMOTIONS"
        />

        <Suspense
          key={`${page}-${limit}-${isUse}`}
          fallback={<ContentLoader className="w-full h-[350px]" />}
        >
          <Dynamic
            page={page}
            limit={limit}
            isUse={isUse}
            searchParams={new URLSearchParams(queryParams)}
          />
        </Suspense>
      </div>
    </>
  );
}

async function Dynamic({
  page,
  isUse,
  limit,
  searchParams,
}: {
  page: string;
  isUse: string;
  limit: string;
  searchParams: URLSearchParams;
}) {
  const [t, { list, total }] = await Promise.all([
    getTranslations("PROMOTIONS"),
    promotionApi.promotions({
      page,
      limit,
      isUse: isUse === "processing",
    }),
  ]);

  console.log("!!!!!!!!!!!!!!!!", list, total);
  const activePage = Number(page);

  return (
    <>
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
        {list.length > 0 &&
          list.map((item, index) => (
            <PromotionCard
              key={index}
              promotionId={item.id.toString()}
              src={item.thumbnail}
              title={item.title}
              endDate={formatDate(item.endDate)}
              status={item.isUse}
              searchParams={searchParams}
            />
          ))}
      </div>

      {list.length == 0 && (
        <NoResults className="py-28">
          <p className="text-foreground/60 max-w-[178px] text-center">
            {t("NO_PROMOTIONS_YET")}
          </p>
        </NoResults>
      )}

      <PagePagination
        activePage={activePage}
        total={total}
        limit={promotionConfig.pagination.limit}
      />
    </>
  );
}
