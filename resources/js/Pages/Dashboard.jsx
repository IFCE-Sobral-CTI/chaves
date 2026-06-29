import React from 'react';
import { usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import KpiCard from '@/Components/Dashboard/KpiCard';
import ChartCard from '@/Components/Dashboard/ChartCard';
import BorrowsBarChart from '@/Components/Dashboard/BorrowsBarChart';
import KeysAreaChart from '@/Components/Dashboard/KeysAreaChart';
import EmployeeTypePie from '@/Components/Dashboard/EmployeeTypePie';
import TopRoomsBarChart from '@/Components/Dashboard/TopRoomsBarChart';
import BorrowsTable from '@/Components/Dashboard/BorrowsTable';
import ExpiringList from '@/Components/Dashboard/ExpiringList';

const KeyIcon = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
    </svg>
);

const AvailableIcon = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
    </svg>
);

const OpenIcon = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
);

const AlertIcon = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
);

export default function Dashboard({
    kpis,
    totals,
    charts,
    recentBorrows,
    overdueList,
    expiringEmployees,
    can,
}) {
    const { authorizations } = usePage().props;
    const canViewAny = authorizations?.['borrows_viewAny'] ?? false;

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'Minha Página', url: route('admin') }]}>
            {/* KPIs Operacionais */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <KpiCard title="Chaves em poder" value={kpis.keysOut} icon={KeyIcon} variant="amber" />
                <KpiCard title="Chaves disponíveis" value={kpis.keysAvailable} icon={AvailableIcon} variant="emerald" />
                <KpiCard title="Empréstimos abertos" value={kpis.openBorrows} icon={OpenIcon} variant="sky" />
                <KpiCard title="Empréstimos em atraso" value={kpis.overdueBorrows} icon={AlertIcon} variant="red" />
            </div>

            {/* Totais de cadastro */}
            <div className="flex flex-wrap justify-end gap-2 mb-6">
                {[
                    { label: 'Salas', value: totals.countRooms },
                    { label: 'Blocos', value: totals.countBlocks },
                    { label: 'Chaves', value: totals.countKeys },
                    { label: 'Servidores', value: totals.countEmployees },
                    { label: 'Empréstimos', value: totals.countBorrows },
                ].map((item) => (
                    <span key={item.label} className="inline-flex items-center gap-2 pl-3 pr-1 py-1 text-xs font-medium text-gray-600 bg-blue-300 rounded-full">
                        {item.label}
                        <span className="inline-flex items-center justify-center min-w-5 px-2 py-0.5 text-xs font-semibold text-white bg-gray-500 rounded-full">
                            {item.value}
                        </span>
                    </span>
                ))}
            </div>

            {/* Gráficos */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <ChartCard title="Empréstimos por dia (últimos 7 dias)" className="h-80">
                    <BorrowsBarChart data={charts.borrowsPerDay} />
                </ChartCard>
                <ChartCard title="Chaves emprestadas por dia (últimos 7 dias)" className="h-80">
                    <KeysAreaChart data={charts.keysPerDay} />
                </ChartCard>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <ChartCard title="Distribuição por tipo de mutuário" className="h-80">
                    <EmployeeTypePie data={charts.byEmployeeType} />
                </ChartCard>
                <ChartCard title="Top 5 salas mais emprestadas" className="h-80">
                    <TopRoomsBarChart data={charts.topRooms} />
                </ChartCard>
            </div>

            {/* Listas detalhadas - apenas para quem tem borrows.viewAny */}
            {canViewAny && (
                <>
                    <div className="mb-4">
                        <BorrowsTable
                            title="Últimos empréstimos"
                            rows={recentBorrows}
                            canView={can.borrowView}
                            emptyMessage="Nenhum empréstimo registrado."
                        />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <BorrowsTable
                            title="Empréstimos em atraso"
                            rows={overdueList}
                            canView={can.borrowView}
                            emptyMessage="Nenhum empréstimo em atraso."
                        />
                        <ExpiringList employees={expiringEmployees} />
                    </div>
                </>
            )}
        </AuthenticatedLayout>
    );
}
