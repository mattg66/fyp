'use client';
import { EditProject } from "@/app/projects/page"
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
    project: EditProject
}
export const EditProjectModal = ({ isOpen, close, confirm, project }: AddProjectModal) => {
    return (
        <Modal
            show={isOpen}
            size="7xl"
            popup={true}
            onClose={close}
        >
            <Modal.Header />
            <EditProjectForm project={project} isOpen={isOpen} />
        </Modal>
    )
}
function EditProjectForm({ project, isOpen }: { project: EditProject, isOpen: boolean }) {
    const [activeTab, setActiveTab] = useState<number>(0)
    const tabsRef = useRef<TabsRef>(null);
    const [existingNodes, setExistingNodes] = useState<string[]>([])
    const [selectedNodes, setSelectedNodes] = useState<any[]>([])
    const handleNodeSelect = (event: OnSelectionChangeParams) => {
        setSelectedNodes(event.nodes)
        console.log(event.nodes)
        console.log(project?.racks)
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

    const { register, handleSubmit, formState: { errors, submitCount }, reset, setValue } = useForm<NewProject>()

    useEffect(() => {
        if (existingNodes !== project?.racks.map(rack => String(rack.id))) {
            setExistingNodes(project?.racks.map(rack => String(rack.id)))
        }
        setSelectedNodes(project?.racks.map(rack => { return { data: rack } }))
        setValue('name', project?.name)
        setValue('description', project?.description)
        setValue('network', project?.network)
        setValue('subnet_mask', project?.subnet_mask)
        setValue('wan_gateway', project?.project_router?.gateway)
        setValue('wan_ip', project?.project_router?.ip)
        setValue('wan_subnet_mask', project?.project_router?.subnet_mask)
    }, [project, isOpen])

    useEffect(() => {
        if (errors?.description) {
            tabsRef.current?.setActiveTab(0)
        }
    }, [submitCount, errors])

    const onSubmit = handleSubmit((data) => {
        const requestOptions = {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...data, racks: selectedNodes.filter((node) => (node.data.project === null || node.data.project?.id === project?.id)).map((node) => node.id) })
        };
        fetch('/api/project/' + project.id, requestOptions).then((response) => {
            if (response.status === 200) {
                response.json().then((data) => {
                    confirm(data.project)
                    toast.success(data.message)
                    reset()
                    tabsRef.current?.setActiveTab(0)
                })
            } else {
                response.json().then((data) => {
                    toast.warn(data.message)
                })
            }
        })
    })
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
                        <TextInput id="name" placeholder="Name" maxLength={15} {...register('name', { required: true, pattern: /^[a-zA-Z0-9]+$/ })} disabled />
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
                                <Flow displayOnly={true} selectedNodesCallback={handleNodeSelect} selectNodes={existingNodes} />
                            </div>
                            <div className="w-32 text-center">
                                <h2>Selected Racks</h2>
                                {selectedNodes?.map((node) => {
                                    if ((node?.type === "rackNode" || node?.type === undefined) && (node.data.project === null || node.data.project?.id === project.id)) {
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
                            {...register('network')}
                            disabled
                        />
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
                            {...register('subnet_mask')}
                            disabled
                        />

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
                            {...register('wan_ip')}
                            disabled
                        />
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
                            {...register('wan_subnet_mask')}
                            disabled
                        />
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
                            {...register('wan_gateway')}
                            disabled
                        />
                    </Tabs.Item>
                </Tabs.Group>

            </Modal.Body>
            <Modal.Footer>
                <div className="flex w-full justify-end mt-5">
                    {activeTab !== 0 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab - 1)}>Back</Button>}
                    {activeTab !== 2 && <Button type="button" className="ml-2" onClick={() => tabsRef.current?.setActiveTab(activeTab + 1)}>Next</Button>}
                    {activeTab === 2 && <Button type="submit" className="ml-2">Update Project</Button>}
                </div>
            </Modal.Footer>
        </form>
    )
}