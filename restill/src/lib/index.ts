export const ROUTE_PATHS = {
  HOME: '/',
  ABOUT: '/about',
  CONTACT: '/contact',
} as const;

export interface Client {
  id: string;
  name: string;
  industry: string;
  logoKey?: string;
}

export interface Stat {
  label: string;
  value: string;
  description: string;
}

export interface Service {
  id: string;
  title: string;
  description: string;
  category: 'AI Consulting' | 'AI Orchestration' | 'Digital Sovereignty';
  iconName: string;
}

export interface TeamMember {
  id: string;
  name: string;
  role: string;
  bio: string;
  imageKey: string;
}

export function formatStatValue(value: string | number): string {
  if (typeof value === 'number') {
    return value.toLocaleString();
  }
  return value;
}

export function getYear(): number {
  return 2026;
}

export type NavigationItem = {
  label: string;
  path: string;
  isExternal?: boolean;
};

export const NAV_ITEMS: NavigationItem[] = [
  { label: 'Intelligence', path: ROUTE_PATHS.HOME },
  { label: 'Orchestra', path: ROUTE_PATHS.ABOUT },
  { label: 'Interface', path: ROUTE_PATHS.CONTACT },
];