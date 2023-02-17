import { TabsInterface } from "flowbite"
import { Button, Modal, Tabs } from "flowbite-react"
import { useRef, useState } from "react"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import Flow, { NewNode } from "./Flow"

interface AddProjectModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    project: any,
}
export const AddProjectModal = ({ isOpen, close, confirm, project }: AddProjectModal) => {
    const [activeTab, setActiveTab] = useState<number>(0)
    const tabsRef = useRef<TabsInterface>(null);
    console.log(activeTab)
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
                        <div className="w-full h-96">
                            <Flow/>
                        </div>
                    </Tabs.Item>
                    <Tabs.Item title="Infrastructure">Settings content</Tabs.Item>
                    <Tabs.Item title="Review">Contacts content</Tabs.Item>
                </Tabs.Group>
            </Modal.Body>
        </Modal>
    )
}