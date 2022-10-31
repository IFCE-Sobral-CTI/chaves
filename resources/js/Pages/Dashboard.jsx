import Panel from '@/Components/Dashboard/Panel';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import React from 'react';
import 'tw-elements';

export default function Dashboard() {
    return (
        <>
            <AuthenticatedLayout breadcrumbs={[{label: 'Minha Página', url: route('admin')}]}>
                <div className="grid grid-cols-5 gap-4">
                    <div className="bg-emerald-500 p-2 rounded-lg text-center text-white shadow-md transition hover:bg-emerald-600">
                        <div className="font-bold text-xs uppercase tracking-widest">Salas</div>
                        <div className="font-bold text-4xl">256</div>
                    </div>
                    <div className="bg-indigo-400 p-2 rounded-lg text-center text-white shadow-md transition hover:bg-indigo-500">
                        <div className="font-bold text-xs uppercase tracking-widest">Blocos</div>
                        <div className="font-bold text-4xl">256</div>
                    </div>
                    <div className="bg-teal-500 p-2 rounded-lg text-center text-white shadow-md transition hover:bg-teal-600">
                        <div className="font-bold text-xs uppercase tracking-widest">Chaves</div>
                        <div className="font-bold text-4xl">256</div>
                    </div>
                    <div className="bg-amber-400 p-2 rounded-lg text-center text-white shadow-md transition hover:bg-amber-500">
                        <div className="font-bold text-xs uppercase tracking-widest">Servidores</div>
                        <div className="font-bold text-4xl">256</div>
                    </div>
                    <div className="bg-sky-500 p-2 rounded-lg text-center text-white shadow-md transition hover:bg-sky-600">
                        <div className="font-bold text-xs uppercase tracking-widest">Empréstimos</div>
                        <div className="font-bold text-4xl">256</div>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <Panel className={'flex-1 h-96'}>
                        <h1>Gráfico 1</h1>
                        <div id="chart1"></div>
                    </Panel>
                    <Panel className={'flex-1 h-96'}>
                        <h1>Gráfico 2</h1>
                        <div id="chart2"></div>
                    </Panel>
                </div>
                <div className="grid grid-cols-3 gap-4">
                    <Panel className={'col-span-2 min-h-52'}>
                        <h1>Últimos empréstimos</h1>
                        <table className="table table-auto w-full">
                            <thead>
                                <tr className="border-b">
                                    <th className="font-semibold text-left px-1.5 pt-1.5">Data</th>
                                    <th className="font-semibold text-left px-1.5 pt-1.5">Servidor</th>
                                    <th className="font-semibold text-left px-1.5 pt-1.5">Situação</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-t transition hover:bg-neutral-100">
                                    <td className="font-light p-1.5">26/10/2022 19:18:24</td>
                                    <td className="font-light p-1.5">Sr. Fábio Urias Toledo</td>
                                    <td className="font-light p-1.5"><span className="px-2 py-1 text-xs text-white rounded-lg bg-green">Devolvido</span></td>
                                    <td className="font-light p-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                            <path fillRule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr className="border-t transition hover:bg-neutral-100">
                                    <td className="font-light p-1.5">26/10/2022 19:18:24</td>
                                    <td className="font-light p-1.5">Sr. Fábio Urias Toledo</td>
                                    <td className="font-light p-1.5"><span className="px-2 py-1 text-xs text-white rounded-lg bg-green">Devolvido</span></td>
                                    <td className="font-light p-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                            <path fillRule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                                        </svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </Panel>
                    <Panel>
                        <h1>Atualizações</h1>
                    </Panel>
                </div>
            </AuthenticatedLayout>
        </>
    )
}
