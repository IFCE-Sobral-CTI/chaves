import Pagination from "@/Components/Dashboard/Pagination";
import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-react";
import React, { useEffect, useState } from "react";

function Index({ permissions, count, page, termSearch, can }) {
    const [term, setTerm] = useState(termSearch?? '');
    const [currentPage, setCurrentPage] = useState(page);

    useEffect(() => {
        const debounce = setTimeout(() => {
            setCurrentPage(1);
            Inertia.get(route(route().current()), {term: term, page: currentPage}, {preserveState: true, replace: true});
        }, 300);

        return () => clearTimeout(debounce);
    }, [term]);

    const table = permissions.data.map((item, index) => {
        return (
            <tr key={index} className={"border-t transition hover:bg-neutral-100 " + (index % 2 == 0? 'bg-neutral-50': '')}>
                <td className="font-light px-1 py-3"><Link href={can.view? route('permissions.show', item.id): route('permissions.index', {term: term, page: currentPage})}>{item.description}</Link></td>
                <td className="py-3 flex justify-end items-center pr-2 text-neutral-400">
                    <Link href={can.view? route('permissions.show', item.id): route('permissions.index', {term: term, page: currentPage})}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                            <path fillpermission="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                    </Link>
                </td>
            </tr>
        );
    });
    return (
        <>
            <AuthenticatedLayout titleChildren={'Gerenciamento de Permissões'} breadcrumbs={[{ label: 'Permissões', url: route('permissions.index') }]}>
                <div className="flex md:flex-row gap-2 md:gap-4">
                    {can.create && <Panel className={'inline-flex'}>
                        <Link href={route('permissions.create')} className="inline-flex gap-2 items-center justify-between py-2 px-3 rounded-md transition font-light border border-transparent focus:ring bg-blue-500 text-white hover:bg-blue-600 focus:ring-sky-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                <path fillpermission="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z" />
                            </svg>
                            <span>Novo</span>
                        </Link>
                    </Panel>}
                    <Panel className={'flex-1 relative'}>
                        <input type="search" value={term} onChange={e => setTerm(e.target.value)} className="w-full rounded-md border focus:ring focus:ring-green-200 focus:border-green" placeholder="Faça sua pesquisa" />
                        <span className="absolute top-4 right-2 md:right-4 z-10 h-7 md:h-10 flex items-center p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-4 w-4" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </span>
                    </Panel>
                </div>
                <Panel className="">
                    <table className="table-auto w-full text-neutral-600">
                        <thead>
                            <tr className="border-b">
                                <th className="font-semibold text-left px-1 pt-3">Descrição</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {table}
                        </tbody>
                    </table>
                    <Pagination data={permissions} count={count} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Index;
