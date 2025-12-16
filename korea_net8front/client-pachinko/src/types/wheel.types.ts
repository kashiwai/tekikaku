// eslint-disable-next-line @typescript-eslint/triple-slash-reference
/// <reference types="react" />

import React from "react";

interface ImagePropsLocal extends ImageProps {
    _imageHTML?: HTMLImageElement;
}
export interface WheelData {
    image?: ImagePropsLocal;
    option?: string;
    style?: StyleType;
    optionSize?: number;
    bonus: number;
    isLock: boolean;
}
export interface StyleType {
    backgroundColor?: string;
    textColor?: string;
    fontFamily?: string;
    fontSize?: number;
    fontWeight?: number | string;
    fontStyle?: string;
}
export interface PointerProps {
    src?: string;
    style?: React.CSSProperties;
}
export interface ImageProps {
    uri: string;
    offsetX?: number;
    offsetY?: number;
    sizeMultiplier?: number;
    landscape?: boolean;
}
export {};
