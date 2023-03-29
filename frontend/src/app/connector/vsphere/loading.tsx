'use client';
import { Spinner } from "@alfiejones/flowbite-react";

export default function Loading() {
    // You can add any UI inside Loading, including a Skeleton.
    return (
        <div className="container mx-auto mt-10">
        <div className="max-w-5xl mx-auto ">
            <div className="text-center">
                <Spinner aria-label="Extra large spinner example" size="xl" />
            </div>
        </div>
    </div>
    )
  }