"use client";
import { useEffect, useState } from "react";

import { useDropzone } from "react-dropzone";

import IconBase from "@/components/icon/iconBase";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { ICONS } from "@/constants/icons";

type Props = {
  initialFile?: File;
  onChange: (file: File) => void;
};

export default function AvatarUploader({ initialFile, onChange }: Props) {
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  const { getRootProps, getInputProps } = useDropzone({
    accept: { "image/*": [] },
    maxFiles: 1,
    onDrop: (acceptedFiles) => {
      const file = acceptedFiles[0];
      if (file) {
        const url = URL.createObjectURL(file);
        setPreviewUrl(url);
        onChange(file);
      }
    },
  });

  // Set preview from initialFile
  useEffect(() => {
    if (initialFile) {
      const url = URL.createObjectURL(initialFile);
      setPreviewUrl(url);
      return () => URL.revokeObjectURL(url);
    }
  }, [initialFile]);

  return (
    <div className="relative w-full h-hull">
      <input {...getInputProps()} />
      <div
        {...getRootProps()}
        className="absolute size-8 right-0 bg-[#262E3C] rounded-full cursor-pointer hover:opacity-90 group transition-all bottom-0 z-10 grid place-content-center"
      >
        <IconBase icon={ICONS.EDIT_PENCIL} className="group-hover:scale-105 transition-all size-4 text-success" />
      </div>
      {previewUrl ? (
        <Avatar className="w-full h-full border-[1.5px] border-primary p-0.5">
          <AvatarImage src={previewUrl} className="bg-black rounded-full object-cover" />
          <AvatarFallback>FN</AvatarFallback>
        </Avatar>
      ) : (
        <p className="text-sm text-gray-500 text-center">Click or Drag Image</p>
      )}
    </div>
  );
}
