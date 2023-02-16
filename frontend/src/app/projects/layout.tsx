import { Suspense } from "react";
import Loading from "./loading";

export default function TerminalServersLayout({
  children,
}: {
  children: React.ReactNode,
}) {

  return (
    <>
      <div className="container mx-auto mt-10">
        <div className="max-w-5xl mx-auto ">
          <Suspense fallback={<Loading />}>
            {children}
          </Suspense>
        </div>
      </div>
    </>
  )
}
