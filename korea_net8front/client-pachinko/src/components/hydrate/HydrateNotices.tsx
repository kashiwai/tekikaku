"use client";
import { useEffect } from "react";

import { useNoticeStore } from "@/store/notice.store";
import { PageNoticeItem } from "@/types/notice.types";

type Props = {
  initialNotices: PageNoticeItem[];
};

export default function HydrateNotification({ initialNotices }: Props) {
  const setNotices = useNoticeStore((store) => store.setNotices);

  useEffect(() => {
    if (initialNotices) {
      setNotices(initialNotices);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialNotices]);

  return null;
}
