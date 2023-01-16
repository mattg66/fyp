'use client';

import { Table } from "flowbite-react";
import React from "react";
export interface RenderTableProps {
    rowText: string,
    element: React.ReactElement
}
export const RenderTable = (props: { data: RenderTableProps[] }) => {
    return (
            <Table className="rounded-lg">
                <Table.Body className="divide-y">
                    {props.data?.map((row, i) => (
                        <Table.Row key={i} className="bg-white dark:border-gray-700 dark:bg-gray-800">
                            <Table.Cell className="whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                {row.rowText}
                            </Table.Cell>
                            <Table.Cell className="text-center">
                                {row.element}
                            </Table.Cell>
                        </Table.Row>
                    ))}
                </Table.Body>
            </Table>
    )

}