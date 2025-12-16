import IconBase from "@/components/icon/iconBase";
import TabWrapper from "@/components/wrapper/tabWrapper";
import { ICONS } from "@/constants/icons";

export default function Page() {
  return (
    <TabWrapper>
      <div className="w-full flex gap-2 flex-col items-center">
        <div className="grid place-content-center size-24 rounded-full bg-primary text-white">
          <IconBase icon={ICONS.HEADPHONES} className="size-12" />
        </div>
        <p className="text-[13px] font-medium text-foreground/90 px-8 text-center">
          Have a question or can`t find the information you need on our website?
          No worries! Feel free to reach out to our 24-hour online customer
          <button className="text-primary cursor-pointer">support</button> team
          at any time. We are always happy to assist you with any questions or
          concerns you may have.
        </p>
      </div>
    </TabWrapper>
  );
}
