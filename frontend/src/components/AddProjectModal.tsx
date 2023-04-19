'use client';
import { EditProject, Project } from "@/app/projects/page"
import { TabsInterface } from "flowbite"
import { Alert, Button, Label, Modal, Tabs, TabsRef, Textarea, TextInput } from "@alfiejones/flowbite-react"
import { useEffect, useRef, useState } from "react"
import { useForm } from "react-hook-form"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import { toast } from "react-toastify"
import { OnSelectionChangeParams } from "reactflow"
import Flow, { NewNode } from "./Flow"

interface AddProjectModal {
    isOpen: boolean,
    close: () => void,
    confirm: (project: EditProject) => void,
}
export const AddProjectModal = ({ isOpen, close, confirm }: AddProjectModal) => {

    return (
        <Modal
            show={isOpen}
            size="7xl"
            popup={true}
            onClose={close}
        >
            <Modal.Header />
            <Form isOpen={isOpen} confirm={confirm} />
        </Modal>
    )
}
function Form({ isOpen, confirm }: { isOpen: boolean, confirm: (project: EditProject) => void }) {
    const [activeTab, setActiveTab] = useState<number>(0)
    const tabsRef = useRef<TabsRef>(null);

    const [selectedNodes, setSelectedNodes] = useState<any[]>([])
    const handleNodeSelect = (event: OnSelectionChangeParams) => {
        setSelectedNodes(event.nodes)
    }
    interface NewProject {
        name: string,
        description: string,
        network: string,
        subnet_mask: string,
        wan_ip: string,
        wan_gateway: string,
        wan_subnet_mask: string,
    }
    const { register, handleSubmit, formState: { errors, submitCount }, getValues, reset } = useForm<NewProject>();
    useEffect(() => {
        if (errors?.description || errors?.name) {
            tabsRef.current?.setActiveTab(0)
        }
        if (errors?.network || errors?.subnet_mask) {
            tabsRef.current?.setActiveTab(2)
        }
    }, [submitCount, errors])

    useEffect(() => {
        reset();
    }, [isOpen])

    const onSubmit = handleSubmit((data) => {
        const requestOptions = {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...data, racks: selectedNodes.map((node) => node.id) })
        };
        fetch('/api/project/', requestOptions).then((response) => {
            if (response.status === 201) {
                response.json().then((data) => {
                    toast.success(data.message)
                    reset()
                    tabsRef.current?.setActiveTab(0)
                    confirm(data.project)
                })
            } else {
                response.json().then((data) => {
                    toast.warn(data.message)
                })
            }
        })
    })
    const validateNetworkAndSubnetMask = (network: string, subnetMask: string) => {
        const networkRegex = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        const subnetMaskRegex = /^255\.255\.(255|254|252|248|240|224|192|128|0)\.0$/;
        if (!network && !subnetMask) {
            return true;
        }
        // Validate network address
        if (!networkRegex.test(network)) {
            return 'Invalid network address';
        }

        // Validate subnet mask
        if (!subnetMaskRegex.test(subnetMask)) {
            return 'Invalid subnet mask';
        }

        // Calculate the network address and validate it against the given network address
        const networkOctets = network.split('.').map(Number);
        const subnetMaskOctets = subnetMask.split('.').map(Number);
        const networkAddressOctets = networkOctets.map((octet, index) => octet & subnetMaskOctets[index]);
        const networkAddress = networkAddressOctets.join('.');
        if (network !== networkAddress) {
            return 'Invalid network address and subnet mask combination';
        }

        // Return true if both network address and subnet mask are valid and valid together
        return true;
    };

    const validateWanNetwork = (ipAddress: string, subnetMask: string, gateway: string) => {
        // Validate IP address
        if (!/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(ipAddress)) {
            return "Invalid IP address";
        }

        // Validate subnet mask
        if (!/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(subnetMask)) {
            return "Invalid subnet mask";
        }

        // Validate gateway
        if (!/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(gateway)) {
            return "Invalid gateway";
        }

        // Check if gateway is on the same subnet as IP address using subnet mask
        const ipLong = ipToLong(ipAddress);
        const gatewayLong = ipToLong(gateway);
        const subnetMaskLong = ipToLong(subnetMask);
        if ((ipLong & subnetMaskLong) !== (gatewayLong & subnetMaskLong)) {
            return "Gateway is not on the same subnet as IP address";
        }

        // All checks passed
        return true;
    }

    const ipToLong = (ip: string) => {
        const parts: any = ip.split(".");
        return (parts[0] << 24) + (parts[1] << 16) + (parts[2] << 8) + parseInt(parts[3]);
    }
    return (
        <form onSubmit={onSubmit} className="h-full flex flex-1 flex-col">
            <Modal.Body className="min-h-[50vh]">
                <Tabs.Group
                    aria-label="Default tabs"
                    style="default"
                    onActiveTabChange={(index) => setActiveTab(index)}
                    ref={tabsRef}
                >
                    <Tabs.Item active title="Project Info">
                        <div className="mt-2">
                            <Label
                                htmlFor="name"
                                value="Name"
                            />
                        </div>
                        <TextInput id="name" placeholder="Name" maxLength={15} {...register('name', { required: true, pattern: /^[a-zA-Z0-9]+$/ })} />
                        {errors?.name && <Alert color="failure" className="mt-2">
                            {errors?.name?.type === 'required' ? <p>Name is required</p> : <p>Name must not have spaces</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="description"
                                value="Description"
                            />
                        </div>
                        <Textarea id="description" placeholder="Description" rows={5} maxLength={500} {...register('description', { required: true })} />
                        {errors?.description && <Alert color="failure" className="mt-2">
                            {errors?.description?.type === 'required' && <p>Description is required</p>}
                        </Alert>}
                    </Tabs.Item>
                    <Tabs.Item title="Rackspace">
                        <div className="w-full h-96 grid grid-cols-4">
                            <div className="col-span-3">
                                <h2>Shift + Click to select multiple racks</h2>
                                <Flow displayOnly={true} selectedNodesCallback={handleNodeSelect} />
                            </div>
                            <div className="w-32 text-center">
                                <h2>Selected Racks</h2>
                                {selectedNodes?.map((node) => {
                                    if ((node?.type === "rackNode" || node?.type === undefined) && (node.data.project === null)) {
                                        return <p key={node.id}>{node.data.label}</p>
                                    }
                                })}
                            </div>
                        </div>
                    </Tabs.Item>
                    <Tabs.Item title="Infrastructure">
                        <div className="border-dashed border-2 p-5">
                            <h1>If left blank, a /16 subnet will be automatically assigned (recommended)</h1>
                            <div className="mt-2">
                                <Label
                                    htmlFor="network"
                                    value="Network"
                                />
                            </div>
                            <TextInput
                                id="network"
                                placeholder="Network"
                                maxLength={50}
                                {...register('network', {
                                    validate: value => validateNetworkAndSubnetMask(value, getValues('subnet_mask'))
                                })}
                            />
                            {errors?.network && <Alert color="failure" className="mt-2">
                                {<p>{errors?.network.message}</p>}
                            </Alert>}
                            <div className="mt-2">
                                <Label

                                    htmlFor="subnet_mask"
                                    value="Subnet Mask"
                                />
                            </div>
                            <TextInput
                                id="subnet_mask"
                                placeholder="Subnet Mask"
                                maxLength={50}
                                {...register('subnet_mask', {
                                    validate: value => validateNetworkAndSubnetMask(getValues('network'), value)
                                })}
                            />
                            {errors?.subnet_mask && <Alert color="failure" className="mt-2">
                                {<p>{errors?.subnet_mask.message}</p>}
                            </Alert>}
                        </div>
                        <div className="mt-2">
                            <Label
                                htmlFor="wan_ip"
                                value="WAN IP"
                            />
                        </div>
                        <TextInput
                            id="wan_ip"
                            placeholder="WAN IP"
                            maxLength={50}
                            {...register('wan_ip', {
                                validate: value => validateWanNetwork(value, getValues('wan_subnet_mask'), getValues('wan_gateway'))
                            })}
                        />
                        {errors?.wan_ip && <Alert color="failure" className="mt-2">
                            {<p>{errors?.wan_ip.message}</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label

                                htmlFor="wan_subnet_mask"
                                value="WAN Subnet Mask"
                            />
                        </div>
                        <TextInput
                            id="wan_subnet_mask"
                            placeholder="WAN Subnet Mask"
                            maxLength={50}
                            {...register('wan_subnet_mask', {
                                validate: value => validateWanNetwork(getValues('wan_ip'), value, getValues('wan_gateway'))
                            })}
                        />
                        {errors?.wan_subnet_mask && <Alert color="failure" className="mt-2">
                            {<p>{errors?.wan_subnet_mask.message}</p>}
                        </Alert>}
                        <div className="mt-2">
                            <Label
                                htmlFor="wan_gateway"
                                value="WAN Gateway IP"
                            />
                        </div>
                        <TextInput
                            id="wan_gateway"
                            placeholder="WAN Gateway IP"
                            maxLength={50}
                            {...register('wan_gateway', {
                                validate: value => validateWanNetwork(getValues('wan_ip'), getValues('wan_subnet_mask'), value)
                            })}
                        />
                        {errors?.wan_gateway && <Alert color="failure" className="mt-2">
                            {<p>{errors?.wan_gateway.message}</p>}
                        </Alert>}
                    </Tabs.Item>
                </Tabs.Group>

            </Modal.Body>
            <Modal.Footer>
                <div className="flex w-full justify-end mt-auto">
                    {activeTab !== 0 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab - 1)}>Back</Button>}
                    {activeTab !== 2 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab + 1)}>Next</Button>}
                    {activeTab === 2 && <Button type="submit" className="ml-2">Create Project</Button>}
                </div>
            </Modal.Footer>
        </form>
    )
}