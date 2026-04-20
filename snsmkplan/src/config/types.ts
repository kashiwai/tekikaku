export interface Product {
  id: string;
  name: string;
  category: string;
  price: number;
  affiliate_url: string;
  reward: number;
  description: string;
  target_audience: string;
  keywords: string[];
  hashtags: { instagram: string[]; tiktok: string[] };
  active: boolean;
}

export interface SceneConfig {
  id: number;
  name: string;
  duration: number;
  material_folder: string;
  material_type: "video" | "image";
  zoom_effect: string;
  caption_position: "top" | "center" | "bottom";
  caption_size: "small" | "medium" | "large";
  caption_style: string;
}

export interface SceneTemplate {
  id: string;
  name: string;
  category: string;
  total_duration: number;
  resolution: string;
  scenes: SceneConfig[];
  bgm: { volume: number; folder: string; random: boolean };
  narration: { volume: number; speed: number };
}

export interface SceneCaption {
  narration: string;
  caption: string;
}

export interface CaptionTemplate {
  id: string;
  name: string;
  scenes: Record<number, SceneCaption>;
  caption_instagram: string;
  caption_tiktok: string;
}

export interface GeneratedScript {
  product: Product;
  template: CaptionTemplate;
  scenes: Array<{
    id: number;
    narration: string;
    caption: string;
  }>;
  captions: {
    instagram: string;
    tiktok: string;
  };
  created_at: string;
}

export interface PostResult {
  platform: "instagram" | "tiktok";
  post_id: string;
  url?: string;
  created_at: string;
}

export interface PerformanceMetrics {
  platform: "instagram" | "tiktok";
  post_id: string;
  views: number;
  likes: number;
  comments: number;
  shares: number;
  profile_visits: number;
  link_clicks: number;
  measured_at: string;
}
