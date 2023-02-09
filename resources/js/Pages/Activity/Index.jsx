import Pagination from "@/Components/Dashboard/Pagination";
import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-react";
import React, { useEffect, useState } from "react";
import Description from "./Components/Description";

function Index({ activities, count, page, termSearch, can }) {
    const [term, setTerm] = useState(termSearch?? '');
    const [currentPage, setCurrentPage] = useState(page);

    // console.log(activities.data);

    useEffect(() => {
        const debounce = setTimeout(() => {
            setCurrentPage(1);
            Inertia.get(route(route().current()), {term: term, page: currentPage}, {preserveState: true, replace: true});
        }, 300);

        return () => clearTimeout(debounce);
    }, [term]);

    const table = activities.data.map((item, index) => {
        return (
            <tr key={index} className={"border-t transition hover:bg-neutral-100 " + (index % 2 == 0? 'bg-neutral-50': '')}>
                <td className="px-1 py-3 font-light"><Link href={can.view? route('activities.show', item.id): route('activities.index', {term: term, page: currentPage})}><Description title={item.description} /></Link></td>
                <td className="px-1 py-3 font-light"><Link href={can.view? route('activities.show', item.id): route('activities.index', {term: term, page: currentPage})}>{item.causer?.name.split(' ').reverse().pop()?? '-'}</Link></td>
                <td className="px-1 py-3 font-light"><Link href={can.view? route('activities.show', item.id): route('activities.index', {term: term, page: currentPage})}>{item.subject_type.split('\\').pop()}</Link></td>
                <td className="px-1 py-3 font-light"><Link href={can.view? route('activities.show', item.id): route('activities.index', {term: term, page: currentPage})}>{item.created_at}</Link></td>
                <td className="flex justify-end py-3 pr-2 text-neutral-400">
                    <Link href={can.view? route('activities.show', item.id): route('activities.index', {term: term, page: currentPage})}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                    </Link>
                </td>
            </tr>
        );
    });

    return (
        <>
            <AuthenticatedLayout titleChildren={'Gerenciamento de Páginas'} breadcrumbs={[{ label: 'Páginas', url: route('activities.index') }]}>
                <div className="flex gap-2 md:flex-row md:gap-4">
                    <Panel className={'flex-1 relative'}>
                        <input type="search" value={term} onChange={e => setTerm(e.target.value)} className="w-full border rounded-md focus:ring focus:ring-green-200 focus:border-green" placeholder="Faça sua pesquisa" />
                        <span className="absolute z-10 flex items-center p-2 top-4 right-2 md:right-4 h-7 md:h-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-4 h-4" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </span>
                    </Panel>
                </div>
                <Panel className="">
                    <table className="w-full table-auto text-neutral-600">
                        <thead>
                            <tr className="border-b">
                                <th className="px-1 pt-3 font-semibold text-left">Ação</th>
                                <th className="px-1 pt-3 font-semibold text-left">Usuário</th>
                                <th className="px-1 pt-3 font-semibold text-left">Módulo afetado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {table}
                        </tbody>
                    </table>
                    <Pagination data={activities} count={count} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Index;
