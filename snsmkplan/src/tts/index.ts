import type { TTSProvider } from "./base.ts";
import { FishAudioProvider } from "./fish-audio.ts";
import { VoicevoxProvider } from "./voicevox.ts";
import { ElevenLabsProvider } from "./elevenlabs.ts";

export function createTTSProvider(): TTSProvider {
  const provider = process.env.TTS_PROVIDER ?? "fishaudio";
  switch (provider) {
    case "fishaudio": return new FishAudioProvider();
    case "voicevox": return new VoicevoxProvider();
    case "elevenlabs": return new ElevenLabsProvider();
    default: throw new Error(`未対応の TTS プロバイダー: ${provider}`);
  }
}

export type { TTSProvider };
