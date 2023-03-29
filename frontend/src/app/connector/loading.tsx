'use client';
import { Spinner } from "@alfiejones/flowbite-react";

export default function Loading() {
    // You can add any UI inside Loading, including a Skeleton.
    return (
        <div className="text-center">
            <Spinner aria-label="Extra large spinner example" size="xl" />
        </div>
    )
  }