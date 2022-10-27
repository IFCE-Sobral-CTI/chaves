import React, { useEffect, useState } from "react";
import { Link } from "@inertiajs/inertia-react";
import 'tw-elements';

function Sidebar({ can }) {
    const [width, setWidth] = useState(window.innerWidth);

    const [accessCollapse, setAccessCollapse] = useState(
        route().current('users.*') ||
        route().current('permissions.*') ||
        route().current('rules.*') ||
        route().current('groups.*')
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
            {/* <nav id="sidebar" className={"mr-2 p-2"}> */}
                <div className="flex flex-col w-48 gap-3 md:w-64">
                    <Link
                        href={route('admin')}
                        className={((route().current('admin'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-6 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
                            <path fillRule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"/>
                        </svg>
                        Principal
                    </Link>
                    {can.borrows_viewAny && <Link
                        href={route('borrows.index')}
                        className={((route().current('borrows.*'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.371 2.371 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976l2.61-3.045zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0zM1.5 8.5A.5.5 0 0 1 2 9v6h12V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5zm2 .5a.5.5 0 0 1 .5.5V13h8V9.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                        Empréstimos
                    </Link>}
                    {can.blocks_viewAny && <Link
                        href={route('blocks.index')}
                        className={((route().current('blocks.*'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path d="M6 1v3H1V1h5zM1 0a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zm14 12v3h-5v-3h5zm-5-1a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-5zM6 8v7H1V8h5zM1 7a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1H1zm14-6v7h-5V1h5zm-5-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1h-5z"/>
                        </svg>
                        Blocos
                    </Link>}
                    {can.rooms_viewAny && <Link
                        href={route('rooms.index')}
                        className={((route().current('rooms.*'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1z"/>
                            <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117zM11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5zM4 1.934V15h6V1.077l-6 .857z"/>
                        </svg>
                        Salas
                    </Link>}
                    {can.keys_viewAny && <Link
                        href={route('keys.index')}
                        className={((route().current('keys.*'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5z"/>
                            <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        Chaves
                    </Link>}
                    {can.employees_viewAny && <Link
                        href={route('employees.index')}
                        className={((route().current('employees.*'))? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0h-7zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795a4.2 4.2 0 0 0-.776-.492C11.392 12.387 10.063 12 8 12s-3.392.387-4.224.803a4.2 4.2 0 0 0-.776.492V2.5z"/>
                        </svg>
                        Servidores
                    </Link>}
                    {(can.users_viewAny || can.permissions_viewAny || can.rules_viewAny || can.groups_viewAny) && <>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
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
                            className={'flex flex-col gap-1 collapse pl-2 ' + (chevronAccess? 'show': '')}
                            id="accessCollapse"
                        >
                            {can.users_viewAny && <Link
                                href={route('users.index', {term: '', page: 1})}
                                className={(route().current('users.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                </svg>
                                Usuários
                            </Link>}
                            {can.groups_viewAny && <Link
                                href={route('groups.index', {term: '', page: 1})}
                                className={(route().current('groups.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                    <path d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4c0 .667.083 1.167.25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75c.167-.333.25-.833.25-1.5H2s-2 0-2-2V4zm1.398-.855a.758.758 0 0 0-.254.302A1.46 1.46 0 0 0 1 4.01V10c0 .325.078.502.145.602.07.105.17.188.302.254a1.464 1.464 0 0 0 .538.143L2.01 11H14c.325 0 .502-.078.602-.145a.758.758 0 0 0 .254-.302 1.464 1.464 0 0 0 .143-.538L15 9.99V4c0-.325-.078-.502-.145-.602a.757.757 0 0 0-.302-.254A1.46 1.46 0 0 0 13.99 3H2c-.325 0-.502.078-.602.145z"/>
                                </svg>
                                Páginas
                            </Link>}
                            {can.permissions_viewAny && <Link
                                href={route('permissions.index', {term: '', page: 1})}
                                className={(route().current('permissions.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M4 16s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H4Zm4-5.95a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                    <path d="M2 1a2 2 0 0 0-2 2v9.5A1.5 1.5 0 0 0 1.5 14h.653a5.373 5.373 0 0 1 1.066-2H1V3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v9h-2.219c.554.654.89 1.373 1.066 2h.653a1.5 1.5 0 0 0 1.5-1.5V3a2 2 0 0 0-2-2H2Z"/>
                                </svg>
                                Permissões
                            </Link>}
                            {can.rules_viewAny && <Link
                                href={route('rules.index', {term: '', page: 1})}
                                className={(route().current('rules.*')? 'bg-gray-50 shadow-md ': '') + `text-gray-600 p-3 rounded-lg hover:bg-white hover:shadow-md transition flex gap-4`}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M2 4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v11.5a.5.5 0 0 1-.777.416L7 13.101l-4.223 2.815A.5.5 0 0 1 2 15.5V4zm2-1a1 1 0 0 0-1 1v10.566l3.723-2.482a.5.5 0 0 1 .554 0L11 14.566V4a1 1 0 0 0-1-1H4z"/>
                                    <path d="M4.268 1H12a1 1 0 0 1 1 1v11.768l.223.148A.5.5 0 0 0 14 13.5V2a2 2 0 0 0-2-2H6a2 2 0 0 0-1.732 1z"/>
                                </svg>
                                Regras
                            </Link>}
                        </div>
                    </>}
                </div>
            </nav>
        </>
    )
}

export default Sidebar;
