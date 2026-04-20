import type { TTSProvider, TTSOptions } from "./base.ts";
import { writeFileSync } from "fs";

export class FishAudioProvider implements TTSProvider {
  private apiKey: string;
  private voiceId: string;

  constructor() {
    this.apiKey = process.env.FISH_AUDIO_API_KEY ?? "";
    this.voiceId = process.env.FISH_AUDIO_VOICE_ID ?? "";
    if (!this.apiKey) throw new Error("FISH_AUDIO_API_KEY が未設定です");
  }

  async generate(text: string, outputPath: string, options: TTSOptions = {}): Promise<void> {
    const speed = options.speed ?? 1.1;

    const response = await fetch("https://api.fish.audio/v1/tts", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${this.apiKey}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        text,
        reference_id: this.voiceId,
        format: "mp3",
        mp3_bitrate: 128,
        normalize: true,
        latency: "normal",
        prosody: { speed },
      }),
    });

    if (!response.ok) {
      const err = await response.text();
      throw new Error(`FishAudio API エラー: ${response.status} ${err}`);
    }

    const buffer = await response.arrayBuffer();
    writeFileSync(outputPath, Buffer.from(buffer));
  }
}
