'use client';

import clsx from "clsx";
import { Accordion, Button } from "flowbite-react";
import React, { useState } from "react";
import { toast } from "react-toastify";
import { DeleteModal } from "./DeleteModal";
import { RenderTable } from "./RenderTable";
import { StatusDot } from "./StatusDot";

export interface TerminalServer {
    label: string;
    id: number;
    model: string;
    status: string;
    version: string;
    serial: string;
    ip: string;
    rack_id: number;
    rack?: Rack;
}
interface Rack {
    label: string;
}

export const TSAccordion = (props: { data: Array<TerminalServer>, className?: string, update: React.Dispatch<React.SetStateAction<Array<TerminalServer>>> }) => {

    const [deleteOpen, setDeleteOpen] = useState(false)
    const [deleteId, setDeleteId] = useState(0)

    const deleteTS = () => {
        fetch('/api/ts/' + deleteId, { method: 'DELETE' }).then((response) => {
            if (response.status === 200) {
                props.update(props.data.filter(element => element.id !== deleteId))
                setDeleteOpen(false)
            } else {
                response.json().then((data) => {
                    toast.warn(data.message)
                })
            }
        })
    }
    const deleteModal = (id: number) => {
        setDeleteId(id)
        setDeleteOpen(true)
    }
    const rowText = (element: TerminalServer): string => {
        if (element.rack === null && element.label === null) {
            return 'Terminal Server'
        } else if (element.rack === null) {
            return element.label
        } else if (element.label === null) {
            return element.rack?.label + ' - Terminal Server'
        }
        return element.rack?.label + ' - ' + element.label
    }
    return (
        <>
                <RenderTable className="mb-10" data={props.data.map(element => ({
            rowText: rowText(element),
            element: <div key={element.id}>
                <Accordion className={clsx(props.className, '!mt-0')} alwaysOpen={true} key={element.id}>
                <Accordion.Panel isOpen={false} >
                    <Accordion.Title className="focus:ring-0">
                        <StatusDot color={element.status === 'ok' ? 'green-500' : 'red-600'} />
                    </Accordion.Title>
                    <Accordion.Content>
                        <RenderTable data={[
                            {
                                rowText: 'Model',
                                element: <p>{element.model}</p>
                            },
                            {
                                rowText: 'Serial',
                                element: <p>{element.serial}</p>
                            },
                            {
                                rowText: 'Version',
                                element: <p>{element.version}</p>
                            },
                            {
                                rowText: 'Address',
                                element: <p>{element.ip}</p>
                            }
                        ]} />
                    </Accordion.Content>
                </Accordion.Panel>
            </Accordion>
            <Button color='failure' className="float-right mt-2" onClick={() => deleteModal(element.id)} >Delete</Button>
            </div>
        }))} />
        <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteTS} label={props?.data?.find(element => element.id === deleteId)?.label}/>
        </>
    )

}