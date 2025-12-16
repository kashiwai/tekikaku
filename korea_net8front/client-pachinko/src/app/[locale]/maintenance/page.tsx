import Maintenance from "@/components/pages/maintenance";
import { cookies } from "next/headers";

export default async function Page() {
  const cookie = await cookies();
  const title = cookie.get("maintenance.title")?.value ?? "Maintenance";
  const description = cookie.get("maintenance.description")?.value ?? "Website is under Maintenance";
  return <Maintenance title={title} description={description} />;
}
