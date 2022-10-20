import React, { useEffect, useState } from "react";
import { Link } from "@inertiajs/inertia-react";

function Sidebar() {
    const [width, setWidth] = useState(window.innerWidth);
    const [accessCollapse, setAccessCollapse] = useState(
        route().current('users.*') ||
        route().current('permissions.*') ||
        route().current('rules.*')
    );
    const [chevronAccess, setChevronAccess] = useState(accessCollapse);

    const toggleChevronAccess = () => {
        setChevronAccess(!chevronAccess);
    }

    useEffect(() => {
        setWidth(window.innerWidth);
    }, [window.innerWidth]);

    return (
        <>
            <nav id="sidebar" className={"collapse collapse-horizontal mr-2 p-2 " + (width >= 1024? 'show': '')}>
                <div className="flex flex-col gap-3 w-48 md:w-64">
                    <Link
                        href={route('admin')}
                        className={((route().current('admin'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-2 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-6" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
                            <path fillRule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"/>
                        </svg>
                        Principal
                    </Link>
                    <button
                        className={
                            (
                                accessCollapse
                                ? 'bg-gray-50 shadow-md '
                                : ''
                            ) + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex items-center gap-4 focus:ring-0`}
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#accessCollapse"
                        aria-expanded="false"
                        aria-controls="accessCollapse"
                        onClick={toggleChevronAccess}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                        </svg>
                        Acesso
                        <span className={"flex-1 flex justify-end "}>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                className={"h-5 w-5 transition " + (!chevronAccess? ' -rotate-90': '')}
                                viewBox="0 0 16 16"
                            >
                                <path fillRule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </span>
                    </button>
                    <div
                        className={'flex flex-col gap-1 collapse pl-2 ' + ((route().current('users.*') || route().current('permissions.*') || route().current('rules.*'))? 'show': '')}
                        id="accessCollapse"
                    >
                        <Link
                            href={route('users.index', {term: '', page: 1})}
                            className={(route().current('users.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                            </svg>
                            Usuários
                        </Link>
                        <Link
                            href={route('permissions.index', {term: '', page: 1})}
                            className={(route().current('permissions.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                <path d="M4 16s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H4Zm4-5.95a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                <path d="M2 1a2 2 0 0 0-2 2v9.5A1.5 1.5 0 0 0 1.5 14h.653a5.373 5.373 0 0 1 1.066-2H1V3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v9h-2.219c.554.654.89 1.373 1.066 2h.653a1.5 1.5 0 0 0 1.5-1.5V3a2 2 0 0 0-2-2H2Z"/>
                            </svg>
                            Permissões
                        </Link>
                        <Link
                            href={route('rules.index', {term: '', page: 1})}
                            className={(route().current('rules.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                <path d="M2 4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v11.5a.5.5 0 0 1-.777.416L7 13.101l-4.223 2.815A.5.5 0 0 1 2 15.5V4zm2-1a1 1 0 0 0-1 1v10.566l3.723-2.482a.5.5 0 0 1 .554 0L11 14.566V4a1 1 0 0 0-1-1H4z"/>
                                <path d="M4.268 1H12a1 1 0 0 1 1 1v11.768l.223.148A.5.5 0 0 0 14 13.5V2a2 2 0 0 0-2-2H6a2 2 0 0 0-1.732 1z"/>
                            </svg>
                            Regras
                        </Link>
                    </div>
                </div>
            </nav>
        </>
    )
}

export default Sidebar;
