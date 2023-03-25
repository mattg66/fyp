"use client";

import { useEffect, useState } from "react";
import useSWR, { SWRResponse } from 'swr'
import { fetcher } from "../utils/Fetcher";
import { RenderTable } from "@/components";
import { TerminalServer, TSAccordion } from "@/components/TSAccordion";
import { Alert, Button, Label, Modal, Select, TextInput } from "flowbite-react";
import { useForm } from "react-hook-form";
import { toast } from "react-toastify";

export default function TerminalServers() {
    interface NewTerminalServer {
        label: string,
        ip: string,
        model: string,
        username: string,
        password: string,
        rack_id: string,
        node_id: string,
        interface_id: string,
        uplink_port: string,
    }
    const { register, handleSubmit, watch, formState: { errors }, reset } = useForm<NewTerminalServer>();
    const watchNode = watch("node_id");
    const { data } = useSWR('/api/ts', fetcher, { suspense: true })
    const [terminalServers, setTerminalServers] = useState<TerminalServer[]>([])

    useEffect(() => {
        setTerminalServers(data?.json)
    }, [data])

    const { data: racks } = useSWR('/api/rack?withoutTS', fetcher, { suspense: true })
    const { data: nodes } = useSWR('/api/aci/fabric', fetcher, { suspense: true })
    const { data: interfaces } = useSWR(watchNode ? '/api/aci/fabric/node/' + watchNode + '/interfaces' : null, fetcher)


    // useEffect(() => {
    //     if (watchNode !== "") {
    //         const { data: interfaces } = useSWR('/api/aci/fabric/node/' + watchNode + '/interface', fetcher)
    //         setInterfaces(interfaces?.json)
    //     }
    // }, [watchNode])

    const [addModal, setAddModal] = useState(false)
    const openModal = () => {
        reset()
        setAddModal(true)
    }

    const onSubmit = handleSubmit((data) => {
        const requestOptions = {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        };
        fetch('/api/ts/', requestOptions).then((response) => {
            if (response.status === 201) {
                response.json().then((data) => {
                    setTerminalServers([...terminalServers, data[0]])
                    setAddModal(false)
                })
            } else {
                response.json().then((data) => {
                    toast.warn(data.message)
                })
            }
        })
    });

    return (
        <>
            <div className='flow-root'>
                <Button className="float-right" onClick={openModal}>Add Terminal Server</Button>
            </div>
            <div className='mt-5'>
                {terminalServers.length === 0 && <RenderTable data={[{ rowText: 'No Terminal Servers Exist', element: <></> }]} />}
                {terminalServers.length > 0 && <TSAccordion data={terminalServers} update={setTerminalServers} className='mt-10' />}
            </div>

            <Modal
                show={addModal}
                onClose={() => setAddModal(false)}
            >
                <Modal.Header>
                    Add Terminal Server
                </Modal.Header>
                <form onSubmit={onSubmit}>
                    <Modal.Body>
                        <div className="mt-2">
                            <Label
                                htmlFor="label"
                                value="Label"
                            />
                        </div>
                        <TextInput id="label" placeholder="Label" maxLength={50} {...register('label', { required: true })} />
                        {errors?.label && <Alert color="failure" className="mt-2">
                            {errors?.label?.type === 'required' && <p>Label is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="model"
                                value="Model"
                            />
                        </div>
                        <TextInput id="model" placeholder="Model" maxLength={50} {...register('model', { required: true })} />
                        {errors?.model && <Alert color="failure" className="mt-2">
                            {errors?.model?.type === 'required' && <p>Model is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="ip"
                                value="IP Address"
                            />
                            {errors.ip && <p role="alert">{errors.ip?.message}</p>}
                        </div>
                        <TextInput id="ip" placeholder="IP Address" {...register('ip', { required: true, pattern: /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/i })} />
                        {errors?.ip && <Alert color="failure" className="mt-2">
                            {errors?.ip?.type === 'pattern' && <p>Must be a valid IP address</p>}
                            {errors?.ip?.type === 'required' && <p>IP Address is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="username"
                                value="Username"
                            />
                        </div>
                        <TextInput id="username" placeholder="Username" maxLength={50} {...register('username', { required: true })} />
                        {errors?.username && <Alert color="failure" className="mt-2">
                            {errors?.username?.type === 'required' && <p>Username is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="password"
                                value="Password"
                            />
                        </div>
                        <TextInput id="password" placeholder="Password" maxLength={50} {...register('password', { required: true })} />
                        {errors?.password && <Alert color="failure" className="mt-2">
                            {errors?.password?.type === 'required' && <p>Password is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="rack_id"
                                value="Rack Location"
                            />
                        </div>
                        <Select id="rack_id" defaultValue="" placeholder="" {...register('rack_id')}>
                            <option value=""> -- Select a Rack -- </option>
                            {racks?.json?.map((rack: any) =>
                                <option key={rack.id} value={rack.id}>{rack.label}</option>
                            )}
                        </Select>
                        <div className="mt-2">
                            <Label
                                htmlFor="uplink_port"
                                value="Uplink Port For Subinterface"
                            />
                        </div>
                        <TextInput id="uplink_port" placeholder="0/0/0" maxLength={50} {...register('uplink_port', { required: true })} />
                        {errors?.uplink_port && <Alert color="failure" className="mt-2">
                            {errors?.uplink_port?.type === 'required' && <p>Uplink port is required</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="rack_id"
                                value="Uplink Fabric Node"
                            />
                        </div>
                        <Select id="node_id" defaultValue="" placeholder="" {...register('node_id', { required: true })}>
                            <option value=""> -- Select a Node -- </option>
                            {nodes?.json?.map((node: any) =>
                                <option key={node.id} value={node.id}>{node.description}</option>
                            )}
                        </Select>
                        {errors?.node_id && <Alert color="failure" className="mt-2">
                            {errors?.node_id?.type === 'required' && <p>Node is required</p>}
                        </Alert>}
                        {watchNode ? <Select id="interface_id" defaultValue="" placeholder="" className="mt-2" {...register('interface_id')} required>
                            <option value=""> -- Select an Interface -- </option>
                            {interfaces?.json?.map((interfaceObj: any) =>
                                <option key={interfaceObj.id} value={interfaceObj.id}>{interfaceObj.aci_id}</option>
                            )}
                        </Select> : null}

                    </Modal.Body>
                    <Modal.Footer>
                        <div className='flex flex-row justify-end w-full'>

                            <Button type="submit">
                                Add
                            </Button>
                            <Button
                                color="gray"
                                onClick={() => setAddModal(false)}
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
