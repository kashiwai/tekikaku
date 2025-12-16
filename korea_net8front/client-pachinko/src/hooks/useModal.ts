"use client";

import { useCallback, useEffect, useState, useRef } from "react";

import { useSearchParams } from "next/navigation";

import { ModalParams, ModalType } from "@/types/modal.types";
import { searchParamUtils } from "@/utils/searchparam.utils";

export type ModalControls<T extends ModalType = ModalType> = {
  isOpen: boolean;
  onOpen: (params?: ModalParams<T>) => void;
  onClose: () => void;
  getParam: <K extends keyof ModalParams<T>>(
    key: K,
    fallback: ModalParams<T>[K]
  ) => ModalParams<T>[K];
  setParam: <K extends keyof ModalParams<T>>(key: K, value: ModalParams<T>[K]) => void;
  removeParam: <K extends keyof ModalParams<T>>(key: K) => void;
  closeWithParams: (keys?: (keyof ModalParams<T>)[]) => void;
};

export const useModal = <T extends ModalType>(modalType: T): ModalControls<T> => {
  const searchParams = useSearchParams();
  const [isOpen, setIsOpen] = useState(false);
  const prevModalParam = useRef<string | null>(null);

  // Keep isOpen in sync with router query string - optimized to only update when actually changed
  useEffect(() => {
    const modalParam = searchParams.get("modal");
    
    // Only update state if the modal param actually changed
    if (prevModalParam.current !== modalParam) {
      setIsOpen(modalParam === modalType);
      prevModalParam.current = modalParam;
    }
  }, [searchParams, modalType]);

  const onOpen = useCallback((params?: ModalParams<T>) => {
    const sp = new URLSearchParams(window.location.search);
    sp.set("modal", modalType);
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value != null) sp.set(key, String(value));
      });
    }
    const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
    window.history.replaceState(null, "", `?${sortedQuery}`);
  }, [modalType]);
  
  const onClose = useCallback(() => {
    setTimeout(()=> {
      const sp = new URLSearchParams(window.location.search);
      sp.delete("modal");
      ["tab", "id"].forEach((key) => sp.delete(key));
      const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
      window.history.replaceState(null, "", `?${sortedQuery}`);
    }, 0)
  }, []);

  const getParam = useCallback(<K extends keyof ModalParams<T>>(key: K, fallback: ModalParams<T>[K]) => {
    const param = searchParams.get(String(key));
    return param !== null ? param as ModalParams<T>[K] : fallback;
  }, [searchParams]);

  const setParam = useCallback(<K extends keyof ModalParams<T>>(key: K, value: ModalParams<T>[K]) => {
    const sp = new URLSearchParams(window.location.search);
    sp.set(String(key), String(value));
    const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
    window.history.replaceState(null, "", `?${sortedQuery}`);
  }, []);

  const removeParam = useCallback<<K extends keyof ModalParams<T>>(key: K) => void>((key) => {
    const sp = new URLSearchParams(window.location.search);
    sp.delete(String(key));
    const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
    
    window.history.replaceState(null, "", `?${sortedQuery}`);
  }, []);

  const closeWithParams = (keys: (keyof ModalParams<T>)[] = []) => {
    const sp = new URLSearchParams(window.location.search);
    sp.delete("modal");
    keys.forEach((key) => sp.delete(String(key)));
    const sortedQuery = searchParamUtils.updateParamsSorted(sp, {});
    window.history.replaceState(null, "", `?${sortedQuery}`);
  };

  return { isOpen, onOpen, onClose, getParam, setParam, removeParam, closeWithParams };
};