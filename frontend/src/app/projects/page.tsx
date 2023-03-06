"use client";
import useSWR from 'swr'
import { AddProjectModal } from "@/components/AddProjectModal";
import { DeleteModal } from "@/components/DeleteModal";
import Flow from "@/components/Flow";
import { Button, Table } from "flowbite-react";
import { useEffect, useState } from "react";
import { fetcher } from '../utils/Fetcher';
export interface Project {
    id: string,
    name: string,
    description: string,
    network: string,
    subnet_mask: string
}
export default function Rackspace() {
    const [addProjectOpen, setAddProjectOpen] = useState(false)
    const { data } = useSWR('/api/project', fetcher, { suspense: true })

    const [projects, setProjects] = useState<Project[]>([])
    const [deleteOpen, setDeleteOpen] = useState(false)
    const [deleteId, setDeleteId] = useState('')
    const addProject = (project: Project) => {
        setProjects([...projects, project])
        setAddProjectOpen(false)
    }
    useEffect(() => {
        setProjects(data?.json)
    }, [data])

    const deleteProject = () => {
        const requestOptions = {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
        }
        fetch(`/api/project/${deleteId}`, requestOptions).then((response) => {
            if (response.status === 200) {
                setProjects(projects.filter((project) => project.id !== deleteId))
                setDeleteOpen(false)
            }
        })
    }
    return (
        <>
            <Button className="float-right" onClick={() => setAddProjectOpen(true)}>Add Project</Button>
            <div className="w-full flex justify-between mb-5">
                <AddProjectModal isOpen={addProjectOpen} close={() => setAddProjectOpen(false)} confirm={addProject} />
            </div>
            <Table>
                <Table.Head>
                    <Table.HeadCell>
                        Project Name
                    </Table.HeadCell>
                    <Table.HeadCell>
                        Project description
                    </Table.HeadCell>
                    <Table.HeadCell>
                        Project Network
                    </Table.HeadCell>
                    <Table.HeadCell>
                        Project Subnet Mask
                    </Table.HeadCell>
                    <Table.HeadCell>
                    </Table.HeadCell>
                </Table.Head>
                <Table.Body className="divide-y">
                    {projects?.map((project: any) => (
                        <Table.Row key={project.id}>
                            <Table.Cell>
                                {project.name}
                            </Table.Cell>
                            <Table.Cell>
                                {project.description}
                            </Table.Cell>
                            <Table.Cell>
                                {project.network}
                            </Table.Cell>
                            <Table.Cell>
                                {project.subnet_mask}
                            </Table.Cell>
                            <Table.Cell>
                                <Button onClick={() => { setDeleteOpen(true); setDeleteId(project.id) }} color="failure">Delete</Button>
                            </Table.Cell>
                        </Table.Row>
                    ))}
                </Table.Body>
            </Table>
            <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteProject} label={projects?.filter((project) => project.id === deleteId)[0]?.name} />
        </>
    )
}
