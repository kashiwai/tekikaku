import { HugeiconsIcon } from "@hugeicons/react";

export type IconSvgObject = ([string, {
  [key: string]: string | number;
}])[] | readonly (readonly [string, {
  readonly [key: string]: string | number;
}])[];

type Props = {
  icon: IconSvgObject;
  className?: string;
};

export default function IconBase({ icon, className = "" }: Props) {
  return <HugeiconsIcon icon={icon} className={className} />;
}
