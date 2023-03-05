import { Project } from "@/app/projects/page"
import { TabsInterface } from "flowbite"
import { Alert, Button, Label, Modal, Tabs, TabsRef, Textarea, TextInput } from "flowbite-react"
import { useEffect, useRef, useState } from "react"
import { useForm } from "react-hook-form"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import { toast } from "react-toastify"
import { OnSelectionChangeParams } from "reactflow"
import Flow, { NewNode } from "./Flow"

interface AddProjectModal {
    isOpen: boolean,
    close: () => void,
    confirm: (project: Project) => void,
}
export const AddProjectModal = ({ isOpen, close, confirm }: AddProjectModal) => {
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

    return (
        <Modal
            show={isOpen}
            size="7xl"
            popup={true}
            onClose={close}
        >
            <Modal.Header />
            <Modal.Body className="h-[50vh]">
                <form onSubmit={onSubmit} className="h-full flex flex-1 flex-col">
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
                            <TextInput id="name" placeholder="Name" maxLength={50} {...register('name', { required: true })} />
                            {errors?.name && <Alert color="failure" className="mt-2">
                                {errors?.name?.type === 'required' && <p>Name is required</p>}
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
                                        if (node?.type === "rackNode") {
                                            return <p key={node.id}>{node.data.label}</p>
                                        }
                                    })}
                                </div>
                            </div>
                        </Tabs.Item>
                        <Tabs.Item title="Infrastructure">
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
                        </Tabs.Item>
                    </Tabs.Group>
                    <div className="flex w-full justify-end mt-auto">
                        {activeTab !== 0 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab - 1)}>Back</Button>}
                        {activeTab !== 2 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab + 1)}>Next</Button>}
                        {activeTab === 2 && <Button type="submit" className="ml-2">Create Project</Button>}
                    </div>
                </form>
            </Modal.Body>
        </Modal>
    )
}