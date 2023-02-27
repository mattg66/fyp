"use client";

import { AddProjectModal } from "@/components/AddProjectModal";
import Flow from "@/components/Flow";
import { Button } from "flowbite-react";
import { useState } from "react";

export default function Rackspace() {
    const [addProjectOpen, setAddProjectOpen] = useState(false)
    

    return (
        <>
            <Button className="float-right" onClick={() => setAddProjectOpen(true)}>Add Project</Button>
            <AddProjectModal isOpen={addProjectOpen} close={() => setAddProjectOpen(false)} confirm={() => {}} />
        </>
    )
}
