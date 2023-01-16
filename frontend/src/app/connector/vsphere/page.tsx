"use client";

import useSWR from 'swr'
import { useEffect, useState } from "react";
import { FabricAccordion, RenderTable, RenderTableProps, StatusDot } from "@/components";
import { Progress } from 'flowbite-react';
import { fetcher } from '@/app/utils/Fetcher';
import { ServiceAccordion } from '@/components/ServiceAccordion';



export default function Vsphere() {
  const { data } = useSWR('/api/vsphere/health', fetcher, { suspense: true })

  const [tableContent, setTableContent] = useState<RenderTableProps[]>([])
  useEffect(() => {
    if (data?.status) {
      setTableContent([
        {
          rowText: 'Connection Status', element: <StatusDot color="green-500" />
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
        {data?.status && <ServiceAccordion data={data?.json?.services} className='mt-10' />}
        <h1></h1>
      </div>
    </>
  )
}
