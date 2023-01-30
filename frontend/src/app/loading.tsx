"use client";
import { Spinner } from "flowbite-react";

export default function Loading() {
    // You can add any UI inside Loading, including a Skeleton.
    return (
        <div className="text-center max-w-5xl mx-auto">
            <Spinner aria-label="Extra large spinner example" size="xl" />
        </div>
    )
  }