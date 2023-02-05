'use client';

import clsx from "clsx";
import { Table } from "flowbite-react";
import React from "react";
export interface RenderTableProps {
    rowText: string,
    element: React.ReactElement
}
export const RenderTable = (props: { data: RenderTableProps[], className?: string }) => {
    return (
        <Table className={clsx("rounded-lg table-fixed", props.className)}>
            <Table.Body className="divide-y">
                {props.data?.map((row, i) => (
                    <Table.Row key={i} className="bg-white dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex">
                            <div className="w-2/12 text-center whitespace-nowrap font-medium text-gray-900 dark:text-white m-auto">{row.rowText}</div>
                            <div className="w-10/12 m-auto p-5">{row.element}</div>
                        </div>
                    </Table.Row>
                ))}
            </Table.Body>
        </Table>
    )

}