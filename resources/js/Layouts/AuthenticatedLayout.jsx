import React from 'react';
import 'tw-elements';
import { Head, Link } from '@inertiajs/inertia-react';
import Footer from '@/Components/Public/Footer';
import Sidebar from '@/Components/Dashboard/Sidebar';
import Breadcrumbs from '@/Components/Dashboard/Breadcrumbs';

export default function AuthenticatedLayout({breadcrumbs, children}) {
    return (
        <>
            <Head title='Dashboard' />
            <div className="flex flex-col min-h-screen w-screen bg-neutral-100">
                <nav className="relative w-full flex flex-wrap items-center justify-between py-0.5 md:py-1 bg-green text-white shadow-lg transition">
                    <div className="w-full flex flex-wrap items-center justify-between">
                        <div className="flex">
                            <button type="button" className="p-2" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-expanded="false" aria-controls="sidebar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-7 w-7" viewBox="0 0 16 16">
                                    <path fillRule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                            </button>
                            <Link className="text-xl" href="">
                                <img src={route().t.url + "/img/logo_branco.svg"} className="h-14" alt="IFCE - Campus Sobral" />
                            </Link>
                        </div>
                        <div className="px-4">
                            <button data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-7 w-7" viewBox="0 0 16 16">
                                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                    <path fillRule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                </svg>
                            </button>
                            <ul
                                className="w-36 dropdown-menu min-w-max absolute hidden bg-white text-base z-50 float-left py-2 list-none text-left rounded-lg shadow-lg mt-1 m-0 bg-clip-padding border-none"
                                aria-labelledby="dropdownMenuButton2"
                            >
                                <li>
                                    <Link
                                        className="dropdown-item text-sm py-2 px-4 font-normal block w-full whitespace-nowrap bg-transparent text-gray-700 hover:bg-gray-100"
                                        href="#profile"
                                    >
                                        Perfil
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        className="dropdown-item text-sm py-2 px-4 font-normal block w-full whitespace-nowrap bg-transparent text-gray-700 hover:bg-gray-100"
                                        href={route('logout')}
                                    >
                                        Sair
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                <div className="flex flex-1">
                    <Sidebar />
                    <main className="flex flex-col gap-2 w-full px-0.5 md:pr-2 py-1 md:py-2">
                        {breadcrumbs && <Breadcrumbs href={breadcrumbs} />}
                        {children}
                    </main>
                </div>
                <Footer />
            </div>
        </>
    );
}
