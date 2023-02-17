import { fetcher } from "@/app/utils/Fetcher"
import { Button, Label, Modal, Select, TextInput } from "flowbite-react"
import { FormEvent, useEffect, useState } from "react"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import { Node } from "reactflow"
import useSWR from 'swr'
import { NewNode } from "."
 
interface EditModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    node: NewNode
}
interface Data {
    label?: string;
    fn?: string | undefined;
    ts?: string | undefined;
    onChange? : (data: Data, id: string) => Promise<any>;
    delete?: (node: NewNode) => void;
}
export const EditModal = ({ isOpen, close, node }: EditModal) => {
    useEffect(() => {
        setTempData(node?.data)
    }, [node])
    const [tempData, setTempData] = useState<Data>(node?.data)
    const { data: terminalServers } = useSWR('/api/ts?withoutRack&rackId=' + node?.id, fetcher)
    const { data: fabricNodes } = useSWR('/api/aci/fabric?withoutRack&rackId=' + node?.id, fetcher)
    const save = (e: FormEvent) => {
        e.preventDefault()
        node.data.onChange(tempData, node.id).then((result: boolean | undefined) => {
            if (result) {
                close()
            }
        })
    }
    return (
        <Modal
            show={isOpen}
            size="md"
            popup={true}
            onClose={close}
        >
            <Modal.Header />
            <Modal.Body>
                <div className="">
                    <form onSubmit={save}>
                        <div className="mb-2 block">
                            <Label
                                htmlFor="rackName"
                                value="Rack Name"
                            />
                        </div>
                        <TextInput
                            id="rackName"
                            type="text"
                            placeholder="Rack"
                            maxLength={50}
                            required={true}
                            value={tempData?.label}
                            onChange={(e) => setTempData({ ...node.data, label: e.target.value })}
                        />
                        <div>
                            <div className="mb-2 mt-2 block">
                                <Label
                                    htmlFor="ToR"
                                    value="ToR Leaf/FEX"
                                />
                            </div>
                            <Select
                                id="FN"
                                value={tempData?.fn?.toString()}
                                onChange={(e) => setTempData({ ...node.data, fn: e.target.value })}
                            >
                                <option value=" "> -- Select a Fabric Node -- </option>
                                {fabricNodes?.json.map((fn: any) => (
                                    <option value={fn.id}>
                                        {fn.description}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div>
                            <div className="mb-2 mt-2 block">
                                <Label
                                    htmlFor="TS"
                                    value="Terminal Server"
                                />
                            </div>
                            <Select
                                id="TS"
                                value={tempData?.ts?.toString()}
                                onChange={(e) => setTempData({ ...node.data, ts: e.target.value })}
                            >
                                <option value=" "> -- Select a TS -- </option>
                                {terminalServers?.json.map((ts: any) => (
                                    <option value={ts.id}>
                                        {ts.label}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div className="flex justify-center gap-4 mt-2">
                            <Button
                                type="submit"
                            >
                                Save
                            </Button>
                            <Button
                                color="gray"
                                onClick={close}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>

                </div>
            </Modal.Body>
        </Modal>
    )
}