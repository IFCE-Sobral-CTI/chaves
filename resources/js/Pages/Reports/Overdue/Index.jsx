import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ReportSummary from "@/Pages/Reports/Components/ReportSummary";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";

function Index({ borrows, count, summary }) {
    const situationBadge = (isOverdue) => {
        return isOverdue
            ? <span className="px-2 py-0.5 text-xs text-white rounded bg-red-500">Atrasado</span>
            : <span className="px-2 py-0.5 text-xs text-white rounded bg-yellow-500">Aberto</span>;
    };

    const list = borrows.data.map((borrow) => {
        const hoursOut = Math.floor(
            (new Date() - new Date(borrow.created_at.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$2/$1/$3'))) / 3600000
        );
        const isOverdue = borrow.situation === 'atrasado';

        return (
            <tr key={borrow.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
                <td className="px-2 py-3 font-light">
                    <div className="flex flex-wrap gap-1">
                        {borrow.keys.map((key) => (
                            <span key={`key-${key.id}`} className="px-1.5 py-0.5 text-xs font-semibold text-white rounded bg-sky-400">
                                {key.number}
                            </span>
                        ))}
                    </div>
                </td>
                <td className="px-2 py-3 font-light">
                    {borrow.keys.map((key) => (
                        <div key={`room-${key.id}`}>{key.room?.description} / {key.room?.block?.description}</div>
                    ))}
                </td>
                <td className="px-2 py-3 font-light">{borrow.employee?.name}</td>
                <td className="px-2 py-3 font-light">{borrow.user?.name}</td>
                <td className="px-2 py-3 font-light">{borrow.created_at}</td>
                <td className="px-2 py-3 font-light">{hoursOut}h</td>
                <td className="px-2 py-3 font-light">{situationBadge(isOverdue)}</td>
            </tr>
        );
    });

    return (
        <AuthenticatedLayout titleChildren="Chaves em Atraso / Em Poder" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Chaves em Atraso', url: route('reports.overdue') }]}>
            <div className="flex items-center justify-between print:hidden">
                <ReportSummary summary={{
                    total: summary.total,
                    open: summary.total - summary.overdue,
                    overdue: summary.overdue,
                }} />
                <ExportButtons routeName="reports.overdue" params={{}} />
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Chaves</th>
                            <th className="px-2 pt-3 font-semibold text-left">Sala / Bloco</th>
                            <th className="px-2 pt-3 font-semibold text-left">Mutuário</th>
                            <th className="px-2 pt-3 font-semibold text-left">Entregue por</th>
                            <th className="px-2 pt-3 font-semibold text-left">Data Entrega</th>
                            <th className="px-2 pt-3 font-semibold text-left">Tempo fora</th>
                            <th className="px-2 pt-3 font-semibold text-left">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        {list}
                    </tbody>
                </table>
                <Pagination data={borrows} count={count} />
            </Panel>
        </AuthenticatedLayout>
    );
}

export default Index;
