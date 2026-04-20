import { join } from "path";
import { mkdirSync } from "fs";
import { createTTSProvider } from "../tts/index.ts";
import type { GeneratedScript } from "../config/types.ts";

export interface GeneratedAudio {
  sceneId: number;
  path: string;
  text: string;
}

export async function generateVoices(
  script: GeneratedScript,
  outputDir: string,
): Promise<GeneratedAudio[]> {
  const tts = createTTSProvider();
  mkdirSync(outputDir, { recursive: true });

  const results: GeneratedAudio[] = [];
  const speed = parseFloat(process.env.TTS_SPEED ?? "1.1");

  for (const scene of script.scenes) {
    const filename = `scene${scene.id}.mp3`;
    const outputPath = join(outputDir, filename);

    console.log(`[音声生成] シーン${scene.id}: "${scene.narration.slice(0, 30)}..."`);
    await tts.generate(scene.narration, outputPath, { speed });

    results.push({ sceneId: scene.id, path: outputPath, text: scene.narration });
  }

  return results;
}
