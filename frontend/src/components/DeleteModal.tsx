'use client';
import { Button, Modal } from "@alfiejones/flowbite-react"
import { HiOutlineExclamationCircle } from "react-icons/hi"
import { NewNode } from "./Flow"

interface DeleteModal {
    isOpen: boolean,
    close: () => void,
    confirm: () => void,
    node?: any,
    label?: string | undefined
}
export const DeleteModal = ({ isOpen, close, confirm, node, label }: DeleteModal) => {
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
                    <HiOutlineExclamationCircle className="mx-auto mb-4 h-14 w-14 text-gray-400 dark:text-gray-200" />
                    <h3 className="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                        Are you sure you want to delete {label ? label : node?.data?.label}?
                    </h3>
                    <div className="flex justify-center gap-4">
                        <Button
                            color="failure"
                            onClick={confirm}
                        >
                            Delete
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