import { hasLocale } from 'next-intl';
import { getRequestConfig } from 'next-intl/server';

import { routing } from './routing';

export type Locale = {
    key: string;
    image: string;
    label: string;
    shortLabel: string;
};

export type Langs = {
    [K in LocaleKey]: string;
};

export const localesStrings = ['en', 'ko', 'zh', 'ja'] as const;
export type LocaleKey = (typeof localesStrings)[number];

export const locales: Locale[] = [
    { key: "en", image: "uk.svg", label: "English", shortLabel: "En" },
    { key: "ko", image: "ko.svg", label: "Korean", shortLabel: "Ko" },
    { key: "zh", image: "ch.svg", label: "Chinese", shortLabel: "Zh" },
    { key: "ja", image: "jp.svg", label: "Japanese", shortLabel: "Ja" },
];

export default getRequestConfig(async ({ requestLocale }) => {
    const requested = await requestLocale;
    const locale = hasLocale(routing.locales, requested)
        ? requested
        : routing.defaultLocale;

    return {
        locale,
        messages: (await import(`../messages/${locale}.json`)).default
    };
});
