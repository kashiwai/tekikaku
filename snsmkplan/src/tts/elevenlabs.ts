import type { TTSProvider, TTSOptions } from "./base.ts";
import { writeFileSync } from "fs";

export class ElevenLabsProvider implements TTSProvider {
  private apiKey: string;
  private voiceId: string;

  constructor() {
    this.apiKey = process.env.ELEVENLABS_API_KEY ?? "";
    this.voiceId = process.env.ELEVENLABS_VOICE_ID ?? "";
    if (!this.apiKey) throw new Error("ELEVENLABS_API_KEY が未設定です");
  }

  async generate(text: string, outputPath: string, options: TTSOptions = {}): Promise<void> {
    const speed = options.speed ?? 1.1;

    const response = await fetch(
      `https://api.elevenlabs.io/v1/text-to-speech/${this.voiceId}`,
      {
        method: "POST",
        headers: {
          "xi-api-key": this.apiKey,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          text,
          model_id: "eleven_multilingual_v2",
          voice_settings: {
            stability: 0.5,
            similarity_boost: 0.75,
            style: 0.0,
            speed,
          },
        }),
      },
    );

    if (!response.ok) {
      const err = await response.text();
      throw new Error(`ElevenLabs API エラー: ${response.status} ${err}`);
    }

    const buffer = await response.arrayBuffer();
    writeFileSync(outputPath, Buffer.from(buffer));
  }
}
