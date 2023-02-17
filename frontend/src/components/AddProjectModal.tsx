import { TabsInterface } from "flowbite"
import { Button, Modal, Tabs } from "flowbite-react"
import { useEffect, useRef, useState } from "react"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import { OnSelectionChangeParams } from "reactflow"
import Flow, { NewNode } from "./Flow"

interface AddProjectModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    project: any,
}
export const AddProjectModal = ({ isOpen, close, confirm, project }: AddProjectModal) => {
    const [activeTab, setActiveTab] = useState<number>(0)
    const [selectedNodes, setSelectedNodes] = useState<any[]>([])
    const [newSelectedNodes, setNewSelectedNodes] = useState<any[]>()
    const handleNodeSelect = (event: OnSelectionChangeParams) => {
        setNewSelectedNodes(event.nodes)
    }
    useEffect(() => {
        setSelectedNodes((selectedNodes) => [...selectedNodes, newSelectedNodes])
    }, [newSelectedNodes])
    
    console.log(selectedNodes)
    return (
        <Modal
            show={isOpen}
            size="7xl"
            popup={true}
            onClose={close}
        >
            <Modal.Header />
            <Modal.Body>
                <Tabs.Group
                    aria-label="Default tabs"
                    style="default"
                    onActiveTabChange={(index) => setActiveTab(index)}
                >
                    <Tabs.Item active title="Project Info">
                        Project Info
                    </Tabs.Item>
                    <Tabs.Item title="Rackspace">
                        <div className="w-full h-96 grid grid-cols-4">
                            <div className="col-span-3">
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
                    <Tabs.Item title="Infrastructure">Settings content</Tabs.Item>
                    <Tabs.Item title="Review">Contacts content</Tabs.Item>
                </Tabs.Group>
            </Modal.Body>
        </Modal>
    )
}