"use server";

import { revalidateTag } from "next/cache";

export async function revalidateTagsAction(tags: string[]) {
  if (!Array.isArray(tags)) return;

  for (const tag of tags) {
    revalidateTag(tag);
  }
}

export async function revalidateTagAction(tag: string) {
  revalidateTag(tag);
}
