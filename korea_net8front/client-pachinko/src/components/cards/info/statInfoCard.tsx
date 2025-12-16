import { HTMLAttributes } from "react";

import IconBase, { IconSvgObject } from "@/components/icon/iconBase";
import { cn } from "@/lib/utils";

type Props = {
  icon?: IconSvgObject;
  title?: string;
  titleDesc?: string;
  description?: string | number;
  titleClassName?: string;
} & HTMLAttributes<HTMLDivElement>;

export default function InfoCard({
  icon,
  title,
  titleDesc,
  titleClassName,
  description,
  ...props
}: Props) {
  return (
    <div
      className={`${cn(
        "p-4 flex flex-col gap-1 rounded-2xl bg-foreground/5 border-neutral/10",
        props.className
      )}`}
    >
      {icon && <IconBase icon={icon} className="size-5 text-foreground/60" />}
      <div className="flex flex-col gap-1">
        <p className={`${titleClassName} text-sm font-semibold leading-[130%] text-foreground/30`}>
          {title}
        </p>
        {titleDesc && <p className="text-xs font-normal leading-[130%]">{titleDesc}</p>}
      </div>

      {description && (
        <div className="text-base font-bold text-foreground">{description}</div>
      )}

      {props.children && props.children}
    </div>
  );
}
