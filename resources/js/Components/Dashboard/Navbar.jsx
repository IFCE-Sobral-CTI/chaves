import React from "react";
import {Link, useForm, usePage} from "@inertiajs/react";
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/react';

function Navbar({ sidebarOpen, onToggleSidebar }) {
    const { auth, title } = usePage().props;
    const { post } = useForm();

    const onHandleLogout = () => {
        post(route('logout'));
    }

    return (
        <nav className="grid w-full grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-2 gap-y-0.5 bg-green px-2 py-1 text-white shadow-lg transition sm:px-3">
            <h1 className="col-span-3 row-start-1 min-w-0 truncate px-2 text-center text-base font-semibold sm:hidden">
                {title}
            </h1>
            <div className="col-start-1 row-start-2 flex min-w-0 items-center sm:row-start-1">
                <div className="flex shrink-0 items-center">
                    <button
                        type="button"
                        className="rounded-md p-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        onClick={onToggleSidebar}
                        aria-label={sidebarOpen ? 'Fechar menu lateral' : 'Abrir menu lateral'}
                        aria-expanded={sidebarOpen}
                        aria-controls="sidebar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-7 w-7" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                        </svg>
                    </button>
                    <Link className="min-w-0" href={route('admin')}>
                        <img src={route().t.url + "/img/logo_branco.svg"} className="h-10 w-auto max-w-28 sm:h-12 sm:max-w-none" alt="IFCE - Campus Sobral" />
                    </Link>
                </div>
            </div>
            <h1 className="col-start-2 row-start-1 hidden min-w-0 truncate px-2 text-center text-lg font-semibold sm:block">
                {title}
            </h1>
            <div className="col-start-3 row-start-2 sm:row-start-1">
                    <Menu as="div" className="relative">
                        {({ open }) => <>
                        <MenuButton
                            aria-label={`Menu do usuário: ${auth.user.name}`}
                            className="flex max-w-full items-center gap-2 rounded-md p-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-7 w-7" viewBox="0 0 16 16">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path fillRule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                            <span className="hidden max-w-32 truncate sm:inline">{auth.user.name.split(' ')[0]}</span>
                            <svg aria-hidden="true" className={`h-4 w-4 transition-transform ${open ? 'rotate-180' : ''}`} viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clipRule="evenodd" />
                            </svg>
                        </MenuButton>
                        <MenuItems className="absolute right-0 z-[1000] mt-2 w-56 max-w-[calc(100vw-2rem)] list-none overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/10 focus:outline-hidden">
                            <MenuItem>
                                {({ focus }) => (
                                    <Link
                                        href={route('profile')}
                                        className={`flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 ${focus ? 'bg-gray-100' : ''}`}
                                    >
                                        <svg aria-hidden="true" className="h-5 w-5 shrink-0 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a6.32 6.32 0 0 1 13.07 0A8.32 8.32 0 0 1 10 18a8.32 8.32 0 0 1-6.535-3.507Z" />
                                        </svg>
                                        Perfil
                                    </Link>
                                )}
                            </MenuItem>
                            <MenuItem>
                                {({ focus }) => (
                                    <button
                                        type="button"
                                        onClick={onHandleLogout}
                                        className={`flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 ${focus ? 'bg-gray-100' : ''}`}
                                    >
                                        <svg aria-hidden="true" className="h-5 w-5 shrink-0 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5a.75.75 0 0 0 .75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2a2.25 2.25 0 0 1-2.25 2.25h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Zm10.22 2.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H8a.75.75 0 0 1 0-1.5h4.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clipRule="evenodd" />
                                        </svg>
                                        Sair
                                    </button>
                                )}
                            </MenuItem>
                        </MenuItems>
                        </>}
                    </Menu>
            </div>
        </nav>
    )
}

export default Navbar;
