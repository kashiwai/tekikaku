import { Loader } from "lucide-react";

export default function FormLoader({ loading }: { loading: boolean }) {
  return (
    loading && (
      <div className="absolute z-10 top-0 left-0 w-full h-full grid bg-background/40 place-content-center">
        <Loader className="animate-spin" />
      </div>
    )
  );
}
