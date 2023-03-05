"use client";

import useSWR from 'swr'
import { useEffect, useState } from "react";
import { FabricAccordion, RenderTable, RenderTableProps, StatusDot } from "@/components";
import { Progress, Select } from 'flowbite-react';
import { fetcher } from '@/app/utils/Fetcher';
import { toast } from 'react-toastify';


export default function Aci() {
    const { data } = useSWR('/api/aci/health', fetcher, { suspense: true })

    const { data: vlanPools } = useSWR('/api/aci/fabric/vlan-pools', fetcher, { suspense: true })

    useEffect(() => {
        setVlanPool(vlanPools?.json?.filter((vp: any) => vp.project_pool == true)[0]?.id)
    }, [vlanPools])

    const setVlanPoolReq = (id: string) => {
        const requestOptions = {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        };
        fetch('/api/aci/fabric/vlan-pools', requestOptions).then((response) => {
            if (response.status === 200) {
                setVlanPool(id)
                toast.success('Vlan Pool set')
            } else {
                toast.error('Something went wrong')
            }
        })
    }

    const [vlanPool, setVlanPool] = useState<string>()
    const [tableContent, setTableContent] = useState<RenderTableProps[]>([])

    useEffect(() => {
        if (data?.status) {
            setTableContent([
                {
                    rowText: 'Connection Status', element: <StatusDot color="green-500" />
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
                    rowText: 'Connection Status', element: <StatusDot color="red-600" />
                }
            ])
        }
    }, [data])
    return (
        <>
            <div>
                <RenderTable data={tableContent} />
                <Select
                    id="vlanPool"
                    className="mt-5"
                    value={vlanPool?.toString()}
                    onChange={(e) => setVlanPoolReq(e.target.value)}
                >
                    <option value=" "> -- Select a Vlan Pool -- </option>
                    {vlanPools?.json
                        .filter((vp: any) => vp.alloc_mode !== 'dynamic')
                        .map((vp: any) => (
                            <option value={vp.id}>
                                {vp.name + ' - ' + vp.start + '-' + vp.end}
                            </option>
                        ))
                    }
                </Select>
                {data?.status && <FabricAccordion data={data?.json?.fabricNodes} className='mt-10' />}
                <h1></h1>
            </div>
        </>
    )
}
