'use client';

import clsx from "clsx";
import { Accordion } from "@alfiejones/flowbite-react";
import React from "react";
import { RenderTable } from "./RenderTable";
import { StatusDot } from "./StatusDot";

interface FabricNode {
    fabricNode: {
        attributes: {
            adSt: string,
            address: string,
            annotation: string,
            apicType: string,
            childAction: string,
            delayedHeartbeat: string,
            dn: string,
            extMngdBy: string,
            fabricSt: string,
            id: string,
            lastStateModTs: string,
            lcOwn: string,
            modTs: string,
            model: string,
            monPolDn: string,
            name: string,
            nameAlias: string,
            nodeType: string,
            role: string,
            serial: string,
            status: string,
            uid: string,
            userdom: string,
            vendor: string,
            version: string
        }
    }
}
export const FabricAccordion = (props: { data: Array<FabricNode>, className?: string }) => {
    return (
        <Accordion className={clsx(props.className)} alwaysOpen={true} >
            <Accordion.Panel isOpen={false} >
                <Accordion.Title className="focus:ring-0">
                    Fabric Nodes
                </Accordion.Title>
                <Accordion.Content>
                    <RenderTable data={props.data?.sort((a, b) => parseInt(a.fabricNode.attributes.id) - parseInt(b.fabricNode.attributes.id)).map(element => ({
                        rowText: element.fabricNode.attributes.name,
                        element: <Accordion className={clsx(props.className)} alwaysOpen={true} >
                        <Accordion.Panel isOpen={false} >
                            <Accordion.Title className="focus:ring-0">
                                <StatusDot color={element.fabricNode.attributes.fabricSt === 'active' ? 'green-500' : 'red-600'} />
                            </Accordion.Title>
                            <Accordion.Content>
                                <RenderTable data={[
                                    {
                                        rowText: 'Model',
                                        element: <p>{element.fabricNode.attributes.model}</p>
                                    },
                                    {
                                        rowText: 'Serial',
                                        element: <p>{element.fabricNode.attributes.serial}</p>
                                    },
                                    {
                                        rowText: 'Version',
                                        element: <p>{element.fabricNode.attributes.version}</p>
                                    },
                                    {
                                        rowText: 'Address',
                                        element: <p>{element.fabricNode.attributes.address}</p>
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