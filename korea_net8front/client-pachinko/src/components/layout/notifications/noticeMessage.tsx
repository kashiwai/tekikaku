import { LocaleKey } from "@/i18n/request";
import { NoticeState } from "@/store/notice.store";
import { PageNoticeItem } from "@/types/notice.types";
import { format } from "date-fns";
type StoreActionProps = Pick<NoticeState, "removeNotice">;

type Props = {
  notice: PageNoticeItem;
  locale: LocaleKey;
} & StoreActionProps;

export default function NoticeMessage({
  notice: { id, title, content, startDate },
  locale,
}: Props) {
  return (
    <div className="flex flex-col p-2 bg-neutral/5 rounded-xl hover:bg-primary hover:text-white cursor-pointer">
      <div className="flex flex-col gap-3">
        <div className="flex flex-col gap-1">
          <span className="text-xs font-medium text-foreground/60">
            {format(startDate, "yyyy-MM-dd HH:mm:a")}
          </span>
          <h6 className="text-sm font-medium">{title[locale]}</h6>
        </div>
        <div
          className="flex flex-col gap-3 text-[13px] font-normal text-foreground/60"
          dangerouslySetInnerHTML={{ __html: content[locale] }}
        ></div>
      </div>
    </div>
  );
}
