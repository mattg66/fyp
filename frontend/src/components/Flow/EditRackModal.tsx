import { Button, Label, Modal, Select, TextInput } from "flowbite-react"
import { useState } from "react"
import { HiOutlineExclamationCircle } from "react-icons/hi"

interface DeleteModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    id: string,
    data: Data
}
interface Data {
    label: string,
    onChange: (data: any, id: string) => void,
    tor?: number,
    ts?: number,
}
export const EditModal = ({ isOpen, close, data, id }: DeleteModal) => {
    const [tempData, setTempData] = useState<Data>(data)
    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        close
    }
    const save = () => {
        data.onChange(tempData, id)
        close()
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
                    <form onSubmit={submit}>
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
                            required={true}
                            value={tempData.label}
                            onChange={(e) => setTempData({ ...data, label: e.target.value })}
                        />
                        <div id="select">
                            <div className="mb-2 mt-2 block">
                                <Label
                                    htmlFor="ToR"
                                    value="ToR Leaf/FEX"
                                />
                            </div>
                            <Select
                                id="ToR"
                                required={true}
                                value={tempData.tor}
                                onChange={(e) => setTempData({ ...data, tor: parseInt(e.target.value) })}
                            >
                                <option disabled value=""> -- Select a Leaf/FEX -- </option>
                                <option value={1}>
                                    United States
                                </option>
                                <option value={2}>
                                    Canada
                                </option>
                                <option value={3}>
                                    France
                                </option>
                                <option value={4}>
                                    Germany
                                </option>
                            </Select>
                        </div>
                        <div id="select">
                            <div className="mb-2 mt-2 block">
                                <Label
                                    htmlFor="TS"
                                    value="Terminal Server"
                                />
                            </div>
                            <Select
                                id="TS"
                                required={true}
                                value={tempData.ts}
                                onChange={(e) => setTempData({ ...data, ts: parseInt(e.target.value) })}
                            >
                                <option disabled value=""> -- Select a TS -- </option>
                                <option value={1}>
                                    United States
                                </option>
                                <option value={2}>
                                    Canada
                                </option>
                                <option value={3}>
                                    France
                                </option>
                                <option value={4}>
                                    Germany
                                </option>
                            </Select>
                        </div>
                    </form>

                    <div className="flex justify-center gap-4 mt-2">
                        <Button
                            onClick={save}
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
                </div>
            </Modal.Body>
        </Modal>
    )
}