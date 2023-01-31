"use client";

import useSWR from 'swr'
import { useEffect, useState } from "react";
import { FabricAccordion, RenderTable, RenderTableProps, StatusDot } from "@/components";
import { Progress } from 'flowbite-react';
import { fetcher } from '@/app/utils/Fetcher';


export default function Aci() {
    const { data } = useSWR('/api/aci/health', fetcher, { suspense: true })

    const [tableContent, setTableContent] = useState<RenderTableProps[]>([])
    useEffect(() => {
        if (data?.status) {
            setTableContent([
                {
                    rowText: 'Connection Status', element: <StatusDot color="green-500"/>
                },
                {
                    rowText: 'Version', element: <p>{data?.json?.version}</p>
                },
                {
                    rowText: 'Health', element: <Progress progress={data?.json?.health} />
                },
            ])
        } else {
            setTableContent([
                {
                    rowText: 'Connection Status', element: <StatusDot color="red-600"/>
                }
            ])
        }
    }, [data])

    return (
        <>
            <div>
                <RenderTable data={tableContent} />
                {data?.status && <FabricAccordion data={data?.json?.fabricNodes} className='mt-10'/>}
                <h1></h1>
            </div>
        </>
    )
}
