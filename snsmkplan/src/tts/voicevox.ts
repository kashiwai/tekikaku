import type { TTSProvider, TTSOptions } from "./base.ts";
import { writeFileSync } from "fs";

export class VoicevoxProvider implements TTSProvider {
  private host: string;
  private speakerId: number;

  constructor() {
    this.host = process.env.VOICEVOX_HOST ?? "http://localhost:50021";
    this.speakerId = parseInt(process.env.VOICEVOX_SPEAKER_ID ?? "8", 10);
  }

  async generate(text: string, outputPath: string, options: TTSOptions = {}): Promise<void> {
    const speed = options.speed ?? 1.1;
    const pitch = options.pitch ?? 0;
    const volume = options.volume ?? 1.0;

    const queryRes = await fetch(
      `${this.host}/audio_query?text=${encodeURIComponent(text)}&speaker=${this.speakerId}`,
      { method: "POST" },
    );
    if (!queryRes.ok) throw new Error(`VOICEVOX audio_query エラー: ${queryRes.status}`);

    const query = await queryRes.json() as Record<string, unknown>;
    query.speedScale = speed;
    query.pitchScale = pitch;
    query.volumeScale = volume;

    const synthRes = await fetch(`${this.host}/synthesis?speaker=${this.speakerId}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(query),
    });
    if (!synthRes.ok) throw new Error(`VOICEVOX synthesis エラー: ${synthRes.status}`);

    const buffer = await synthRes.arrayBuffer();
    writeFileSync(outputPath, Buffer.from(buffer));
  }
}
