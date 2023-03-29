'use client';
import useSWR from 'swr'
import { AddProjectModal } from "@/components/AddProjectModal";
import { DeleteModal } from "@/components/DeleteModal";
import Flow from "@/components/Flow";
import { Button, Table, Tooltip } from "@alfiejones/flowbite-react";
import { useEffect, useState } from "react";
import { fetcher } from '../utils/Fetcher';
import { EditProjectModal } from '@/components/EditProjectModal';
import dynamic from 'next/dynamic';
import clsx from 'clsx';
export interface Project {
    id: string,
    name: string,
    description: string,
    network: string,
    subnet_mask: string,
    racks: Racks[]
}
interface Racks {
    id: number,
    label: string,
    node_id: number,
}
interface ProjectRouter {
    ip: string,
    subnet_mask: string,
    gateway: string,
}
export interface EditProject extends Project {
    racks: Racks[]
    project_router: ProjectRouter
}
const NoSSR = () => {
    const [addProjectOpen, setAddProjectOpen] = useState(false)
    const [editProjectOpen, setEditProjectOpen] = useState(false)
    const [editProjectId, setEditProjectId] = useState('')
    const { data } = useSWR('/api/project', fetcher, { suspense: true })
    const { data: status } = useSWR('/api/project/status', fetcher, { suspense: true, refreshInterval: 5000 })

    const [projects, setProjects] = useState<EditProject[]>([])
    const [projectStatus, setProjectStatus] = useState<Status[]>([])
    const [deleteOpen, setDeleteOpen] = useState(false)
    const [deleteId, setDeleteId] = useState('')
    const addProject = (project: EditProject) => {
        setProjects([...projects, project])
        setProjectStatus([...projectStatus, { status: 'ACI' }])
        setAddProjectOpen(false)
    }
    interface Status {
        status: string | null
    }
    useEffect(() => {
        setProjectStatus(status.json)
    }, [status])

    useEffect(() => {
        setProjects(data?.json)
    }, [data])

    const updateProject = (project: EditProject) => {
        setProjects(projects.map(project2 => {
            if (project2.id === project.id) {
                return project
            } else {
                return project2
            }
        }))
        setEditProjectOpen(false)
    }

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
    console.log(projects)
    return (
        <>
            <Button className="float-right" onClick={() => setAddProjectOpen(true)}>Add Project</Button>
            <div className="w-full flex justify-between mb-5">
                <AddProjectModal isOpen={addProjectOpen} close={() => setAddProjectOpen(false)} confirm={addProject} />
                <EditProjectModal isOpen={editProjectOpen} close={() => setEditProjectOpen(false)} confirm={updateProject} project={projects?.filter((project) => project.id === editProjectId)[0]} />
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
                        Project WAN IP
                    </Table.HeadCell>
                    <Table.HeadCell>
                    </Table.HeadCell>
                    <Table.HeadCell>
                    </Table.HeadCell>
                    <Table.HeadCell>
                    </Table.HeadCell>
                </Table.Head>
                <Table.Body className="divide-y">
                    {projects?.map((project: any, key) => (
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
                                {project?.project_router?.ip}
                            </Table.Cell>
                            <Table.Cell>
                                <Button onClick={() => { setEditProjectOpen(true); setEditProjectId(project.id) }} color="warning">Edit</Button>

                            </Table.Cell>
                            <Table.Cell>
                                <Button onClick={() => { setDeleteOpen(true); setDeleteId(project.id) }} color="failure">Delete</Button>
                            </Table.Cell>
                            <Table.Cell>
                                <Tooltip content={projectStatus[key]?.status === 'Provisioned' ? 'Provisioned' : 'Provisioning ' + projectStatus[key]?.status} trigger='hover'>
                                    <span className="relative flex h-3 w-3">
                                        <span className={clsx("animate-ping absolute inline-flex h-full w-full rounded-full opacity-75", projectStatus[key]?.status == null || projectStatus[key] == undefined && 'bg-sky-400', projectStatus[key]?.status === 'ACI' && 'bg-amber-400', projectStatus[key]?.status === 'VMware' && 'bg-blue-400', projectStatus[key]?.status === 'Provisioned' && 'bg-green-500', projectStatus[key]?.status === 'Error' && 'bg-red-500')}></span>
                                        <span className={clsx("relative inline-flex rounded-full h-3 w-3", projectStatus[key]?.status == null || projectStatus[key] == undefined && 'bg-sky-500', projectStatus[key]?.status === 'ACI' && 'bg-amber-500', projectStatus[key]?.status === 'VMware' && 'bg-blue-500', projectStatus[key]?.status === 'Provisioned' && 'bg-green-500', projectStatus[key]?.status === 'Error' && 'bg-red-500')}></span>
                                    </span>
                                </Tooltip>
                            </Table.Cell>
                        </Table.Row>
                    ))}

                </Table.Body>
            </Table>
            <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteProject} label={projects?.filter((project) => project.id === deleteId)[0]?.name} />
        </>
    )
}
const Projects = dynamic(() => Promise.resolve(NoSSR), {
    ssr: false
})
export default Projects