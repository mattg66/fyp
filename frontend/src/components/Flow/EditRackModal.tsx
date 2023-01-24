import { Button, Modal } from "flowbite-react"
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
                <div className="text-center">
                    <form onSubmit={submit}>
                    <input type="text" className="dark:text-black nodrag" value={tempData.label} onChange={(e) => setTempData({...data, label: e.target.value})} />
                    </form>

                    <div className="flex justify-center gap-4">
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