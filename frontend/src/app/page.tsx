'use client';

import Flow from "@/components/Flow";
import dynamic from "next/dynamic";

const NoSSR = () => {
    return (
        <>
            <Flow displayOnly={false}/>
        </>
    )
}
const Rackspace = dynamic(() => Promise.resolve(NoSSR), {
    ssr: false
})
export default Rackspace