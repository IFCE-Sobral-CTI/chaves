import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Panel from '@/Components/Dashboard/Panel';
import { Link } from '@inertiajs/react';

const reports = [
    {
        title: 'Empréstimos',
        description: 'Relatório detalhado de empréstimos com filtros por data, mutuário, situação, bloco, sala e chave. Inclui exportação CSV.',
        route: 'reports.borrows',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        ),
    },
    {
        title: 'Chaves em Atraso',
        description: 'Lista de chaves atualmente emprestadas e não devolvidas, com tempo fora e flag de atraso. Exportação CSV disponível.',
        route: 'reports.overdue',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        ),
    },
    {
        title: 'Uso por Sala / Bloco',
        description: 'Ranking de salas e blocos por número de empréstimos e chaves movimentadas no período. Com gráfico de barras.',
        route: 'reports.rooms',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        ),
    },
    {
        title: 'Uso por Mutuário',
        description: 'Ranking de mutuários por empréstimos, chaves movimentadas, atrasos e validade. Com gráfico pizza por tipo.',
        route: 'reports.employees',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        ),
    },
    {
        title: 'Produtividade por Recepcionista',
        description: 'Entregas e recebimentos registrados por cada usuário do sistema no período. Exportação CSV.',
        route: 'reports.staff',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        ),
    },
    {
        title: 'Tempo Médio de Devolução',
        description: 'Turnaround entre entrega e devolução, geral e por tipo de mutuário / sala. Com resumo de dentro vs fora do prazo.',
        route: 'reports.turnaround',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        ),
    },
    {
        title: 'Permissões Expirando',
        description: 'Discentes e externos com permissão de acesso próxima do vencimento ou já vencida. Filtrável por janela de dias e tipo.',
        route: 'reports.expiring-access',
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        ),
    },
];

export default function Index() {
    return (
        <AuthenticatedLayout titleChildren="Relatórios" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }]}>
            <Panel>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {reports.map((report) => (
                        <Link
                            key={report.route}
                            href={route(report.route)}
                            className="flex items-start gap-4 p-4 transition bg-white border border-neutral-200 rounded-lg shadow-sm hover:shadow-md hover:border-emerald-300 group"
                        >
                            <div className="shrink-0 p-2 text-emerald-600 bg-emerald-50 rounded-lg group-hover:bg-emerald-100 transition">
                                {report.icon}
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-neutral-700 group-hover:text-emerald-700 transition">{report.title}</h3>
                                <p className="mt-1 text-xs text-neutral-500 leading-relaxed">{report.description}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </Panel>
        </AuthenticatedLayout>
    );
}
