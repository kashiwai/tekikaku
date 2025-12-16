import { HTMLAttributes } from "react";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";


export default function HeaderSearchBtn({
  ...props
}: HTMLAttributes<HTMLButtonElement>) {
  const searchModal = useModal("search");

  return (
    <Button
      onClick={() => searchModal.onOpen()}
      size={"icon_default"}
      {...props}
    >
      <IconBase icon={ICONS.SEARCH} />
    </Button>
  );
}
