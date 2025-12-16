import Image from "next/image";

import Marquee from "react-fast-marquee";

export default function ProvidersMarque() {
  return (
    <Marquee
      speed={50}
      gradient={false}
      className="h-[66px] rounded-2xl bg-foreground/5 border border-foreground/5"
    >
      <div className="flex items-center gap-[34px]">
        <Image
          src={`/imgs/provider-footer/footer-image-10.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-1.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-5.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-11.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-2.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-12.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-7.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-3.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-18.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-8.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-4.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
        <Image
          src={`/imgs/provider-footer/footer-image-9.svg`}
          alt="provider"
          width={100}
          height={60}
          className="w-full max-w-[100px] object-contain"
        />
      </div>
    </Marquee>
  );
}
