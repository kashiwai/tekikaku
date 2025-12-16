"use client";
import { useState, useEffect } from "react";

import Cropper from "react-easy-crop";

import { Button } from "@/components/ui/button";
import { Slider } from "@/components/ui/slider";
import { getCroppedImg } from "@/lib/utils";

interface Props {
  file: File;
  onSave: (file: File) => void;
}

export default function CropEditor({ file, onSave}: Props) {
  const [imageSrc, setImageSrc] = useState<string | null>(null);
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedAreaPixels, setCroppedAreaPixels] = useState<{
    x: number;
    y: number;
    width: number;
    height: number;
  } | null>(null);

  // Convert File to data URL for preview
  useEffect(() => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => {
      setImageSrc(reader.result as string);
    };
  }, [file]);

  const onCropComplete = (_: unknown, areaPixels: { x: number; y: number; width: number; height: number }) => {
    setCroppedAreaPixels(areaPixels);
  };

  const handleSave = async () => {
    if (!imageSrc || !croppedAreaPixels) return;
    const blob = await getCroppedImg(imageSrc, croppedAreaPixels);
    const croppedFile = new File([blob], file.name, { type: file.type });
    onSave(croppedFile);
  };

  if (!imageSrc) {
    return <div>Loading image...</div>;
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="relative w-full h-[274px] bg-black rounded-2xl overflow-hidden">
        <Cropper
          image={imageSrc}
          crop={crop}
          zoom={zoom}
          aspect={1}
          onCropChange={setCrop}
          cropShape="rect"
          onCropComplete={onCropComplete}
          onZoomChange={setZoom}
        />
      </div>

      <Slider
        min={1}
        max={3}
        step={0.1}
        value={[zoom]}
        onValueChange={([v]) => setZoom(v)}
        className="bg-foreground/10 rounded-full"
      />

      <Button onClick={handleSave} variant="primary" className="w-full">
        Save
      </Button>
    </div>
  );
}
