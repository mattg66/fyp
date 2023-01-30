'use client';

import clsx from "clsx";
import { Accordion } from "flowbite-react";
import React from "react";
import { RenderTable } from "./RenderTable";
import { StatusDot } from "./StatusDot";

interface TerminalServer {
    label: string;
    id: number;
    model: string;
    status: string;
    version: string;
    serial: string;
    ip: string;
    rack_id: number;
}

export const TSAccordion = (props: { data: Array<TerminalServer>, className?: string }) => {
    return (
        <Accordion className={clsx(props.className)} alwaysOpen={true} >
            <Accordion.Panel isOpen={false} >
                <Accordion.Title className="focus:ring-0">
                    Terminal Servers
                </Accordion.Title>
                <Accordion.Content>
                    <RenderTable data={props.data.map(element => ({
                        rowText: element.label,
                        element: <Accordion className={clsx(props.className)} alwaysOpen={true} >
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
                    }))} />
                </Accordion.Content>
            </Accordion.Panel>
        </Accordion>
    )

}