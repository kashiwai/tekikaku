import { Suspense } from "react";

import PagePagination from "@/components/common/page/pagePagination";
import IconBase from "@/components/icon/iconBase";
import ContentLoader from "@/components/loader/contentLoader";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import TabWrapper from "@/components/wrapper/tabWrapper";
import { noticeConfig } from "@/config/notice.config";
import { ICONS } from "@/constants/icons";
import { noticeApi } from "@/lib/api/notice.api";
import { searchParamUtils } from "@/utils/searchparam.utils";
import { getLocale } from "next-intl/server";

type Props = {
  searchParams: Promise<{
    page?: string;
  }>;
};

export default async function Page({ searchParams }: Props) {
  const queryParams = await searchParams;
  const { page } = searchParamUtils.getParams(queryParams, {
    page: "1",
  });

  return (
    <TabWrapper>
      <Suspense
        key={`${page}`}
        fallback={<ContentLoader className="w-full h-[350px]" />}
      >
        <Dynamic page={page} />
      </Suspense>
    </TabWrapper>
  );
}

async function Dynamic({ page }: { page: string }) {
  const locale = await getLocale();
  const { list, total } = await noticeApi.faqList({
    page,
    limit: "16",
    type: "bonus",
  });

  return (
    <>
      <Accordion type="single" collapsible className="divide-foreground/10">
        {list.map((data, index) => (
          <AccordionItem value={index.toString()} key={index}>
            <AccordionTrigger className="">
              <div className="flex items-center gap-1.5">
                <IconBase
                  icon={ICONS.CHEVRON_LEFT}
                  className="-rotate-180 group-data-[state=open]:-rotate-90 size-5"
                />
                <h6 className="text-base font-medium">{data.title[locale]}</h6>
              </div>
            </AccordionTrigger>

            <AccordionContent>
              <div
                dangerouslySetInnerHTML={{ __html: data.content[locale] }}
                className="flex flex-col gap-3 text-xs text-foreground/70"
              ></div>
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>

      <PagePagination
        activePage={Number(page)}
        total={total}
        limit={noticeConfig.pagination.limit}
      />
    </>
  );
}
