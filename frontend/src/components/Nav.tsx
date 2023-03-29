'use client';
import { usePathname } from 'next/navigation'
import Link from 'next/link'
import { Navbar } from '@alfiejones/flowbite-react';
import { Expand } from "@theme-toggles/react"
import { useTheme } from 'next-themes'
import "@theme-toggles/react/css/Expand.css"

export const Nav = () => {
    const pathName = usePathname()
    const { resolvedTheme, setTheme } = useTheme()
    const routes = [
        { path: '/', name: 'Overview' },
        { path: '/projects', name: 'Projects' },
        { path: '/rackspace', name: 'Rackspace' },
        { path: '/connector/aci', name: 'ACI' },
        { path: '/connector/vsphere', name: 'vSphere' },
        { path: '/terminal-server', name: 'Terminal Servers' },
    ]

    return (
        <Navbar
            fluid={false}
            rounded={false}
        >
            <Navbar.Brand>
                <p className="text-2xl">DC Orchestrator</p>
            </Navbar.Brand>
            <Navbar.Toggle />
            <Navbar.Collapse>
                {routes.map((route, i) => (
                    <li key={i} className="m-auto	">
                        <Link key={i} href={route.path} className={pathName === route.path ? 'block py-2 pl-3 pr-4 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-700 md:p-0 dark:text-white' : 'block py-2 pl-3 pr-4 text-gray-700 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-gray-400 md:dark:hover:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent'}>{route.name}</Link>
                    </li>
                ))}
                {resolvedTheme && <Expand toggle={() => setTheme(resolvedTheme === 'dark' ? 'light' : 'dark')} toggled={resolvedTheme === 'dark'} className='text-2xl pl-3' />}
            </Navbar.Collapse>
        </Navbar>
    )

}