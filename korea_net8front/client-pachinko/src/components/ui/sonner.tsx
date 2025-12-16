"use client"
import { useTheme } from "next-themes"
import { Toaster as Sonner, toast, ToasterProps } from "sonner"

import IconBase from "@/components/icon/iconBase"
import { ICONS } from "@/constants/icons"

const Toaster = ({ ...props }: ToasterProps) => {
  const { theme = "system" } = useTheme()

  return (
    <Sonner
      theme={theme as ToasterProps["theme"]}
      className="toaster group"
      closeButton={true}
      icons={{
        close: <IconBase icon={ICONS.CLOSE_X} className="!size-[14px] !text-foreground" />
      }}
      toastOptions={{
        classNames: {
          toast: "group toast !rounded-2xl group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg",
          title: "text-sm font-medium !pr-[18px]",
          description: "text-xs opacity-90",
          closeButton: "!w-[26px] !h-[26px] !rounded-[10px] !bg-foreground/5 !hover:bg-foreground/10 !text-foreground !left-[94%] !top-[14px] !border-none",
        },
      }}
      {...props}
    />
  )
}

// Success Toast
const successToast = (message: string, description?: string) => {
  toast(message, {
    description,
    icon: <IconBase icon={ICONS.CHECKMARK} className="text-success size-5" />,
    classNames: {
      toast: "!bg-background !border-foreground/10 shadow-xl",
      title: "!text-success",
      description: "!text-foreground",
      closeButton: "!text-green-500",
    },
    duration: 4000
  })
}

// Danger Toast
const dangerToast = (message: string, description?: string) => {
  toast(message, {
    description,
    icon: <IconBase icon={ICONS.WARNING} className="text-danger size-5" />,
    classNames: {
      toast: "!bg-background !border-foreground/10 shadow-xl",
      title: "!text-danger",
      description: "!text-foreground",
    },
    duration: 4000
  })
}

export { Toaster, successToast as toastSuccess, dangerToast as toastDanger }