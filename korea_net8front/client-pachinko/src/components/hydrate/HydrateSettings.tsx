"use client";
import { useEffect } from "react";
import { SiteSettingsResponse } from "@/config/settings.config";
import { useSettingsStore } from "@/store/siteSettings.store";

interface HydrateSettingsProps {
  settings: SiteSettingsResponse;
}

export default function HydrateSettings({ settings }: HydrateSettingsProps) {
  const setSettings = useSettingsStore((store) => store.setSettings)

  useEffect(() => {
    setSettings(settings);
  }, [settings]);

  return null;
}
