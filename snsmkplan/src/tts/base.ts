export interface TTSOptions {
  speed?: number;
  pitch?: number;
  volume?: number;
}

export interface TTSProvider {
  generate(text: string, outputPath: string, options?: TTSOptions): Promise<void>;
}
