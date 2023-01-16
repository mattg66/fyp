'use client';

import clsx from "clsx";
import React from "react";

export const StatusDot = (props: {color: string}) => {
    return (
        <div className={clsx("h-5 w-5 rounded-full mx-auto", 'bg-' + props.color)}></div>
    )

}