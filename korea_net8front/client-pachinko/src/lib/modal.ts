import { ModalParams, ModalType } from "@/types/modal.types";
import { searchParamUtils } from "@/utils/searchparam.utils";

export function generateModalPath<T extends ModalType>(
  currentParams: URLSearchParams,
  modal: T,
  params?: ModalParams<T>
): string {
  const updatedParams = { modal, ...(params ?? {}) };
  return `?${searchParamUtils.updateParamsSorted(currentParams, updatedParams)}`;
}