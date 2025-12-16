"use client";
import { useState } from "react";

export type SelectControls = {
  isOpen: boolean;
  onOpenChange: (isOpen: boolean)=> void;
  onOpen: ()=> void;
  onClose: ()=> void;
};

export const useSelect = (): SelectControls => {
  const [isOpen, setIsOpen] = useState(false);

  const onOpenChange = (open: boolean) => {
    setIsOpen(open);
  };

  const onOpen = () => setIsOpen(true);
  const onClose = () => setIsOpen(false);

  return { isOpen, onOpenChange, onOpen, onClose };
};
