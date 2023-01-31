"use client";

import { useEffect, useState } from "react";
import useSWR from 'swr'
import { fetcher } from "../utils/Fetcher";
import { RenderTable, RenderTableProps } from "@/components";
import { TSAccordion } from "@/components/TSAccordion";

export default function TerminalServers() {

    const { data } = useSWR('/api/ts', fetcher, { suspense: true })

    const [tableContent, setTableContent] = useState<RenderTableProps[]>([])
    useEffect(() => {
        if (data?.status) {
            setTableContent(tableContent)
        } else {
            setTableContent([
                {
                    rowText: 'No Terminal Servers Exist', element: <></>
                }
            ])
        }
    }, [data])

    return (
        <>
            <div>
                {data?.status && <TSAccordion data={data?.json} className='mt-10'/>}
            </div>
        </>
    )
}
