"use client";
import { memo } from "react";

import Image from "next/image";

import { ROUTES } from "@/config/routes.config";
import { cn } from "@/lib/utils";
import { useThemeState } from "@/store/theme.store";
import { SettingsType } from "@/types/settings.types";
import { Link } from "@/i18n/navigation";

type Props = {
  logo: SettingsType["site"]["logo"];
  withTitle?: boolean;
  className?: string;
  parentClassName?: string;
  hasHref?: boolean;
};

function Logo({
  logo,
  parentClassName = "",
  className = "",
  withTitle = false,
  hasHref = true,
}: Props) {
  const mode = useThemeState((store) => store.mode);

  const currentLogo = logo[mode === "dark" ? "light" : "dark"];

  return (
    <Link
      href={hasHref ? ROUTES.HOME : "#"}
      className={cn(
        `flex items-center gap-2 ml-1 ${withTitle ? "" : ""}`,
        parentClassName
      )}
    >
      <Image
        key={mode}
        src={currentLogo.icon}
        alt={"Casino Logo"}
        width={30}
        height={26}
        className={cn("w-[30px] h-[26px]", className)}
        priority={true}
      />
      {withTitle && (
        <Image
          key={`${mode}-title`}
          src={currentLogo.title}
          alt={"Casino Logo"}
          width={30}
          height={26}
          className={cn("w-[146px] h-[15px]", className)}
          priority={true}
        />
      )}
    </Link>
  );
}

export default memo(Logo);
