'use client';
import { usePathname } from 'next/navigation'
import Link from 'next/link'
import { Navbar } from 'flowbite-react';
import { Expand } from "@theme-toggles/react"
import { useTheme } from 'next-themes'
import "@theme-toggles/react/css/Expand.css"

export const Nav = () => {
    const pathName = usePathname()
    const { theme, setTheme } = useTheme()
    const routes = [
        { path: '/', name: 'Overview' },
        { path: '/projects', name: 'Projects' },
        { path: '/rackspace', name: 'Rackspace' },
        { path: '/aci', name: 'ACI' },
        { path: '/vcenter', name: 'vCenter' },
    ]
    console.log(theme)

    return (
        <Navbar
            fluid={false}
            rounded={true}
        >
            <Navbar.Brand>
                <h1 className="text-2xl">DC Orchestrator</h1>
            </Navbar.Brand>
            <Navbar.Toggle />
            <Navbar.Collapse>
                {routes.map((route, i) => (
                    <li>
                        <Link key={i} href={route.path} className={pathName === route.path ? 'block py-2 pl-3 pr-4 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-700 md:p-0 dark:text-white' : 'block py-2 pl-3 pr-4 text-gray-700 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-gray-400 md:dark:hover:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent'}>{route.name}</Link>
                    </li>
                ))}
                {theme && <Expand toggle={() => setTheme(theme === 'dark' ? 'light' : 'dark')} toggled={theme === 'dark'} className='text-2xl pl-3' />}
            </Navbar.Collapse>
        </Navbar>
    )

}