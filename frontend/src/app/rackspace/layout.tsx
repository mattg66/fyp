import { Suspense } from "react";
import Loading from "./loading";

export default function RackspaceLayout({
  children,
}: {
  children: React.ReactNode,
}) {

  return (
    <>
      <div className="flex flex-grow">
          <Suspense fallback={<Loading />}>
            {children}
          </Suspense>
      </div>
    </>
  )
}
