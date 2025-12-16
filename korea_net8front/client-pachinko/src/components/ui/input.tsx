"use client";
import * as React from "react";

import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";
import { cn } from "@/lib/utils";

function Input({
  className,
  type,
  render,
  ...props
}: React.ComponentProps<"input"> & { render?: React.ReactNode }) {
  const [visible, setVisible] = React.useState(false);
  const inputType = type === "password" && visible ? "text" : type;
  return (
    <div className="relative w-full">
      {type === "search" && (
        <IconBase
          icon={ICONS.SEARCH}
          className="absolute left-3 size-5 text-foreground/60 top-1/2 -translate-y-1/2"
        />
      )}
      <input
        type={inputType}
        data-slot="input"
        className={cn(
          type === "search" ? "!pl-10" : "",
          "selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-foreground/5  flex h-[41px] w-full min-w-0 rounded-xl border bg-black/10 px-3 py-1 text-[13px] font-medium  shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-60",
          "focus:border-foreground/15 outline-none placeholder:text-foreground/40",
          "aria-invalid:border-danger/60",
          className
        )}
        {...props}
      />

      {render && render}

      {type === "password" && (
        <button
          onClick={() => setVisible((state) => !state)}
          type="button"
          className="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer"
        >
          {!visible ? (
            <IconBase icon={ICONS.EYE_OPEN} className="size-5" />
          ) : (
            <IconBase icon={ICONS.EYE_CLOSED} className="size-5" />
          )}
        </button>
      )}
    </div>
  );
}

interface InputControlsProps {
  values: string[];
  value: string;
  onChange: (val: string) => void;
  elementToRender: React.ReactElement<React.HTMLAttributes<HTMLDivElement>>;
}

function InputControls({
  values,
  value,
  onChange,
  elementToRender,
}: InputControlsProps) {
  const normalizedValue = Number(value.replace(/,/g, "") || 0);

  return (
    <div className="w-full flex flex-wrap justify-end gap-1">
      {values.map((val, idx) => {
        const isSelected = normalizedValue === Number(val.replace(/,/g, ""));
        return React.cloneElement(
          elementToRender,
          {
            key: idx,
            onClick: () => onChange(val),
            className: `${elementToRender.props.className} cursor-pointer ${
              isSelected ? "bg-primary text-white" : ""
            }`,
          },
          val
        );
      })}

      {React.cloneElement(
        elementToRender,
        {
          key: "reset",
          className: `${elementToRender.props.className} !bg-danger text-white cursor-pointer`,
          onClick: () => onChange(""),
        },
        `Reset`
      )}
    </div>
  );
}
export { Input, InputControls };
