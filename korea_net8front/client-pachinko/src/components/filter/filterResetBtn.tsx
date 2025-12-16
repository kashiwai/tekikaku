"use client";

import React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";

type Props = {
  filters: string[];
};

export default function FilterResetBtn({ filters }: Props) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const handleReset = () => {
    const params = new URLSearchParams(searchParams.toString());

    filters.forEach((key) => {
      params.delete(key);
    });

    router.push(`?${params.toString()}`);
  };

  return (
    <Button variant="default" onClick={handleReset}>
      <IconBase icon={ICONS.RESET} />
    </Button>
  );
}
