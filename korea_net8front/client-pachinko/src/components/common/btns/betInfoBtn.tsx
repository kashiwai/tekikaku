"use client";

import { useModal } from "@/hooks/useModal";

export default function BetInfoBtn({ id }: { id: string }) {
  const betInfoModal = useModal("betInfo");
  return (
    <button
      className="cursor-pointer text-success"
      onClick={() => {
        betInfoModal.onOpen({ id });
      }}
    >
      {id}
    </button>
  );
}
