'use client';

import clsx from "clsx";
import { Accordion } from "flowbite-react";
import React from "react";
import { RenderTable } from "./RenderTable";
import { StatusDot } from "./StatusDot";

interface Service {
    name_key: string;
    startup_type: string;
    health_messages: {
        args: string[];
        default_message: string;
        id: string;
    }[];
    health: string;
    description_key: string;
    state: string;
    name: string;
}[]

export const ServiceAccordion = (props: { data: Array<Service>, className?: string }) => {
    return (
        <Accordion className={clsx(props.className)} alwaysOpen={true} >
            <Accordion.Panel isOpen={false} >
                <Accordion.Title className="focus:ring-0">
                    Services
                </Accordion.Title>
                <Accordion.Content>
                    <RenderTable data={props.data?.map(element => ({
                        rowText: element.name,
                        element: <Accordion className={clsx(props.className)} alwaysOpen={true} >
                        <Accordion.Panel isOpen={false} >
                            <Accordion.Title className="focus:ring-0">
                                <StatusDot color={element.health === 'HEALTHY' ? 'green-500' : 'red-600'} />
                            </Accordion.Title>
                            <Accordion.Content>
                                <RenderTable data={[
                                    {
                                        rowText: 'Startup',
                                        element: <p>{element.startup_type}</p>
                                    },
                                    {
                                        rowText: 'Description Key',
                                        element: <p>{element.description_key}</p>
                                    },
                                    {
                                        rowText: 'State',
                                        element: <p>{element.state}</p>
                                    },
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