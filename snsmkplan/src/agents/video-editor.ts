import { execSync, spawnSync } from "child_process";
import { existsSync, readdirSync, mkdirSync, copyFileSync, writeFileSync } from "fs";
import { join, extname } from "path";
import type { GeneratedScript, SceneTemplate } from "../config/types.ts";
import type { GeneratedAudio } from "./voice-generator.ts";

const MATERIALS_DIR = process.env.MATERIALS_DIR ?? "./materials";

export interface VideoResult {
  videoPath: string;
  thumbnailPath: string;
}

export async function editVideo(
  script: GeneratedScript,
  sceneTemplate: SceneTemplate,
  audioFiles: GeneratedAudio[],
  outputDir: string,
): Promise<VideoResult> {
  mkdirSync(outputDir, { recursive: true });

  const timestamp = Date.now();
  const videoPath = join(outputDir, `video_${timestamp}.mp4`);
  const thumbnailPath = join(outputDir, `thumbnail_${timestamp}.jpg`);
  const compositionDir = join(outputDir, `composition_${timestamp}`);
  mkdirSync(compositionDir, { recursive: true });

  const compositionData = buildCompositionData(script, sceneTemplate, audioFiles, compositionDir);
  const dataPath = join(compositionDir, "composition.json");
  writeFileSync(dataPath, JSON.stringify(compositionData, null, 2));

  console.log("[動画編集] Remotionで動画を合成中...");

  const remotionDir = join(process.cwd(), "remotion");
  if (!existsSync(remotionDir)) {
    console.warn("[動画編集] Remotionディレクトリが見つかりません。FFmpegフォールバックを使用します");
    return fallbackFFmpeg(script, sceneTemplate, audioFiles, outputDir, videoPath, thumbnailPath);
  }

  const result = spawnSync("bun", ["run", "render", dataPath, videoPath], {
    cwd: remotionDir,
    stdio: "inherit",
    timeout: 300000,
  });

  if (result.status !== 0) {
    console.warn("[動画編集] Remotionレンダリング失敗。FFmpegフォールバックを使用します");
    return fallbackFFmpeg(script, sceneTemplate, audioFiles, outputDir, videoPath, thumbnailPath);
  }

  extractThumbnail(videoPath, thumbnailPath);
  return { videoPath, thumbnailPath };
}

function buildCompositionData(
  script: GeneratedScript,
  template: SceneTemplate,
  audioFiles: GeneratedAudio[],
  compositionDir: string,
): object {
  const fps = 30;
  return {
    width: 1080,
    height: 1920,
    fps,
    scenes: template.scenes.map((scene) => {
      const audio = audioFiles.find((a) => a.sceneId === scene.id);
      const scriptScene = script.scenes.find((s) => s.id === scene.id);
      const material = findMaterial(scene.material_folder, scene.id);

      return {
        id: scene.id,
        durationInFrames: scene.duration * fps,
        material,
        materialType: scene.material_type,
        zoomEffect: scene.zoom_effect,
        caption: scriptScene?.caption ?? "",
        captionPosition: scene.caption_position,
        captionSize: scene.caption_size,
        captionStyle: scene.caption_style,
        audioPath: audio?.path ?? null,
      };
    }),
    bgm: findBGM(template.bgm.folder),
    bgmVolume: template.bgm.volume,
    narrationVolume: template.narration.volume,
  };
}

function findMaterial(folder: string, sceneId: number): string | null {
  const dir = join(MATERIALS_DIR, folder);
  if (!existsSync(dir)) return null;

  const files = readdirSync(dir).filter((f) => {
    const ext = extname(f).toLowerCase();
    return [".jpg", ".jpeg", ".png", ".mp4", ".mov"].includes(ext);
  });

  const numbered = files.find((f) => f.startsWith(`${sceneId}_`) || f.startsWith(`0${sceneId}_`));
  if (numbered) return join(dir, numbered);

  const fallback = files[Math.floor(Math.random() * files.length)];
  return fallback ? join(dir, fallback) : null;
}

function findBGM(folder: string): string | null {
  if (!existsSync(folder)) return null;
  const files = readdirSync(folder).filter((f) => [".mp3", ".wav"].includes(extname(f).toLowerCase()));
  if (files.length === 0) return null;
  const picked = files[Math.floor(Math.random() * files.length)];
  return picked ? join(folder, picked) : null;
}

function fallbackFFmpeg(
  script: GeneratedScript,
  template: SceneTemplate,
  audioFiles: GeneratedAudio[],
  outputDir: string,
  videoPath: string,
  thumbnailPath: string,
): VideoResult {
  console.log("[動画編集] FFmpegで仮動画を生成...");

  try {
    const listPath = join(outputDir, "concat_list.txt");
    const entries: string[] = [];

    for (const scene of template.scenes) {
      const material = findMaterial(scene.material_folder, scene.id);
      const audio = audioFiles.find((a) => a.sceneId === scene.id);

      if (material) {
        const sceneVideo = join(outputDir, `scene${scene.id}_tmp.mp4`);
        const ext = extname(material).toLowerCase();
        const isVideo = [".mp4", ".mov"].includes(ext);

        if (isVideo && audio?.path) {
          execSync(
            `ffmpeg -y -i "${material}" -i "${audio.path}" -c:v libx264 -c:a aac -shortest -t ${scene.duration} "${sceneVideo}" 2>/dev/null`,
          );
        } else if (!isVideo && audio?.path) {
          execSync(
            `ffmpeg -y -loop 1 -i "${material}" -i "${audio.path}" -c:v libx264 -c:a aac -shortest -t ${scene.duration} -vf scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2 "${sceneVideo}" 2>/dev/null`,
          );
        } else {
          execSync(
            `ffmpeg -y -loop 1 -i "${material}" -c:v libx264 -t ${scene.duration} -vf scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2 "${sceneVideo}" 2>/dev/null`,
          );
        }
        entries.push(`file '${sceneVideo}'`);
      }
    }

    if (entries.length > 0) {
      writeFileSync(listPath, entries.join("\n"));
      execSync(`ffmpeg -y -f concat -safe 0 -i "${listPath}" -c copy "${videoPath}" 2>/dev/null`);
    } else {
      execSync(
        `ffmpeg -y -f lavfi -i color=c=black:s=1080x1920:d=30 -c:v libx264 "${videoPath}" 2>/dev/null`,
      );
    }

    extractThumbnail(videoPath, thumbnailPath);
  } catch (e) {
    console.error("[動画編集] FFmpegエラー:", e);
    writeFileSync(videoPath, "");
    writeFileSync(thumbnailPath, "");
  }

  return { videoPath, thumbnailPath };
}

function extractThumbnail(videoPath: string, thumbnailPath: string): void {
  try {
    execSync(
      `ffmpeg -y -i "${videoPath}" -vframes 1 -q:v 2 "${thumbnailPath}" 2>/dev/null`,
    );
  } catch {
    // サムネイル生成失敗は致命的でない
  }
}
