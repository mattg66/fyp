'use client';
import useSWR from 'swr'
import { useEffect, useState } from "react";
import { FabricAccordion, RenderTable, RenderTableProps, StatusDot } from "@/components";
import { Button, Label, Modal, Progress, Select } from 'flowbite-react';
import { fetcher, poster } from '@/app/utils/Fetcher';
import { toast } from 'react-toastify';
import { useFieldArray, useForm } from 'react-hook-form';


export default function Aci() {
    const { data } = useSWR('/api/aci/health', fetcher, { suspense: true, fallbackData: {status: false, json: {}} })
    const { data: nodes } = useSWR('/api/aci/fabric', fetcher, { suspense: true, fallbackData: {status: false, json: [{}]} })
    const { data: interfaceProfiles } = useSWR('/api/aci/fabric/interface-profiles', fetcher, { suspense: true, fallbackData: {status: false, json: [{}]} })

    const { data: vlanPools } = useSWR('/api/aci/fabric/vlan-pools', fetcher, { suspense: true, fallbackData: {status: false, json: [{}]} })
    const { control, register, handleSubmit } = useForm();
    const { fields, append, prepend, remove, swap, move, insert, replace } = useFieldArray({
        control,
        name: "interfaceProfiles"
    })
    const onSubmit = (data: any) => {
        let json: any[] = []
        data.interfaceProfiles.map((ip: any, index: number) => {
            json.push({ id: nodes?.json[index]?.id, dn: ip.value })
        })
        poster('/api/aci/fabric/interface-profiles', json).then((response) => {
            if (response.status) {
                toast.success('Interface Profiles set')
                setInterfaceProfileOpen(false)
            }
        })
    }

    useEffect(() => {
        replace(nodes?.json.map((node: any) => {
            return { value: node.int_profile }
        }))
    }, [nodes])

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
                response.json().then((json: any) => toast.error(json.message))
            }
        })
    }

    const [vlanPool, setVlanPool] = useState<string>()
    const [tableContent, setTableContent] = useState<RenderTableProps[]>([])
    const [interfaceProfileOpen, setInterfaceProfileOpen] = useState(false)

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
                    <option value=""> -- Select a Vlan Pool -- </option>
                    {vlanPools?.json?.filter((vp: any) => vp.alloc_mode !== 'dynamic')
                        .map((vp: any) => (
                            <option value={vp.id} key={vp.id}>
                                {vp.name + ' - ' + vp.start + '-' + vp.end}
                            </option>
                        ))
                    }
                </Select>
                <Button onClick={() => setInterfaceProfileOpen(true)} className="mt-5">Set Interface Profiles</Button>
                {data?.status && <FabricAccordion data={data?.json?.fabricNodes} className='mt-10' />}
                <h1></h1>
            </div>
            <Modal
                show={interfaceProfileOpen}
                onClose={() => setInterfaceProfileOpen(false)}
            >
                <Modal.Header>
                    Assign Interface Profile to Nodes
                </Modal.Header>
                <form onSubmit={handleSubmit(onSubmit)}>
                    <Modal.Body>
                        <div className="grid grid-flow-row grid-cols-2 gap-4">
                            {fields.map((field, index) => (
                                <>
                                    <div>
                                        <Label key={field.id} htmlFor={`interfaceProfiles.${index}.value`}>
                                            {nodes?.json[index]?.description}
                                        </Label>
                                    </div>
                                    <div>
                                        <Select
                                            key={field.id}
                                            {...register(`interfaceProfiles.${index}.value`)}
                                        >
                                            <option value=""> -- Select a Interface Profile -- </option>
                                            {nodes?.json[index]?.role === 'leaf' && interfaceProfiles?.json?.leaf?.map((ip: any) => (

                                                <option value={ip.infraAccPortP.attributes.dn} key={ip.infraAccPortP.attributes.dn}>
                                                    {ip.infraAccPortP.attributes.name}
                                                </option>
                                            ))}
                                            {nodes?.json[index]?.role === 'fex' && interfaceProfiles?.json?.fex?.map((ip: any) => (
                                                <option value={ip.infraFexP.attributes.dn} key={ip.infraFexP.attributes.dn}>
                                                    {ip.infraFexP.attributes.name}
                                                </option>
                                            ))}

                                        </Select>

                                    </div>
                                </>
                            ))}
                        </div>
                    </Modal.Body>
                    <Modal.Footer>
                        <div className='flex flex-row justify-end w-full'>
                            <Button type="submit">
                                Save
                            </Button>
                            <Button
                                color="gray"
                                onClick={() => setInterfaceProfileOpen(false)}
                                className="ml-2"
                            >
                                Cancel
                            </Button>
                        </div>
                    </Modal.Footer>
                </form>
            </Modal>
        </>
    )
}
