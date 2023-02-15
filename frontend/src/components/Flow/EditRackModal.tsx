import { fetcher } from "@/app/utils/Fetcher"
import { Button, Label, Modal, Select, TextInput } from "flowbite-react"
import { FormEvent, useState } from "react"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import useSWR from 'swr'
 
interface DeleteModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    id: string,
    data: Data
}
interface Data {
    label: string,
    onChange: (data: any, id: string) => Promise<void>,
    fn?: number,
    ts?: number,
}
export const EditModal = ({ isOpen, close, data, id }: DeleteModal) => {
    const [tempData, setTempData] = useState<Data>(data)
    const { data: terminalServers } = useSWR('/api/ts?withoutRack&rackId=' + id, fetcher, { suspense: true })
    const { data: fabricNodes } = useSWR('/api/aci/fabric?withoutRack&rackId=' + id, fetcher, { suspense: true })
    const save = (e: FormEvent) => {
        e.preventDefault()
        data.onChange(tempData, id).then((result: any) => {
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
                            value={tempData.label}
                            onChange={(e) => setTempData({ ...data, label: e.target.value })}
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
                                value={tempData.fn?.toString()}
                                onChange={(e) => setTempData({ ...data, fn: parseInt(e.target.value) })}
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
                                value={tempData.ts?.toString()}
                                onChange={(e) => setTempData({ ...data, ts: parseInt(e.target.value) })}
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