import Pagination from "@/Components/Dashboard/Pagination";
import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-react";
import moment from "moment/moment";
import React, { useEffect, useState } from "react";

function Index({ borrows, count, page, termSearch, can }) {
    const [term, setTerm] = useState(termSearch?? '');
    const [currentPage, setCurrentPage] = useState(page);

    useEffect(() => {
        const debounce = setTimeout(() => {
            setCurrentPage(1);
            Inertia.get(route(route().current()), {term: term, page: currentPage}, {preserveState: true, replace: true});
        }, 300);

        return () => clearTimeout(debounce);
    }, [term]);

    const status = (created_at, devolution) => {
        let start = moment(created_at, "DD/MM/YYYY hh:mm:ss");
        let end = moment(created_at, "DD/MM/YYYY hh:mm:ss").add(1, 'd');
        let now = moment();

        if (devolution)
            return <span className="px-2 py-1 text-xs text-white rounded-lg bg-green">Devolvido</span>

        if (now.isBetween(start, end) && !devolution)
            return <span className="px-2 py-1 text-xs text-white bg-yellow-500 rounded-lg">Aberto</span>

        if (now.isAfter(end) && !devolution)
            return <span className="px-2 py-1 text-xs text-white bg-red-500 rounded-lg">Atrasado</span>
    }

    const table = borrows.data.map((item, index) => {
        return (
            <tr key={index} className={"border-t transition hover:bg-neutral-100 " + (index % 2 == 0? 'bg-neutral-50': '')}>
                <td className="px-1 py-3 font-light">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
                        {item.created_at}
                    </Link>
                </td>
                <td className="px-1 py-3 font-light">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
                        {item.employee.name}
                    </Link>
                </td>
                <td className="hidden px-1 py-3 font-light md:table-cell">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
                        {item.user.name.split(' ')[0]}
                    </Link>
                </td>
                <td className="hidden px-1 py-3 font-light md:table-cell">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
                        {item.keys.map((it, i) => {
                            return <span className="px-1 mr-1 text-sm font-normal text-white rounded-md bg-sky-500" title={it.room.description} key={i}>{it.number}</span>
                        })}
                    </Link>
                </td>
                <td className="px-1 py-3 font-light">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
                        {status(item.created_at, item.devolution)}
                    </Link>
                </td>
                <td className="flex items-center justify-end py-3 pr-2 text-neutral-400">
                    <Link href={can.view? route('borrows.show', item.id): route('borrows.index', {term: term, page: currentPage})}>
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
            <AuthenticatedLayout titleChildren={'Gerenciamento de Empréstimos'} breadcrumbs={[{ label: 'Empréstimos', url: route('borrows.index') }]}>
                <div className="flex gap-2 md:flex-row md:gap-4">
                    {can.create && <Panel className={'inline-flex'}>
                        <Link href={route('borrows.create')} className="inline-flex items-center justify-between gap-2 px-3 py-2 font-light text-white transition bg-blue-500 border border-transparent rounded-md focus:ring hover:bg-blue-600 focus:ring-sky-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                <path fillRule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z" />
                            </svg>
                            <span>Novo</span>
                        </Link>
                    </Panel>}
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
                                <th className="px-1 pt-3 font-semibold text-left">Data</th>
                                <th className="px-1 pt-3 font-semibold text-left">Mutuário</th>
                                <th className="hidden px-1 pt-3 font-semibold text-left md:table-cell">Entregue por</th>
                                <th className="hidden px-1 pt-3 font-semibold text-left md:table-cell">Chaves</th>
                                <th className="px-1 pt-3 font-semibold text-left">Situação</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {table}
                        </tbody>
                    </table>
                    <Pagination data={borrows} count={count} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Index;
