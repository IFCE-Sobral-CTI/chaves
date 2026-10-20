import PeriodFilterForm from "@/Pages/Reports/Components/PeriodFilterForm";
import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ChartCard from "@/Components/Dashboard/ChartCard";
import TopRoomsBarChart from "@/Components/Dashboard/TopRoomsBarChart";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";

function Index({ rooms, count, chart, filters }) {
    const { data, setData, get, processing, errors } = useForm({
        start: filters.start ?? '',
        end: filters.end ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.rooms'), { data });
    };

    const list = rooms.data.map((room) => (
        <tr key={room.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
            <td className="px-2 py-3 font-light">{room.room}</td>
            <td className="px-2 py-3 font-light">{room.block}</td>
            <td className="px-2 py-3 font-light">{room.borrow_count}</td>
            <td className="px-2 py-3 font-light">{room.key_count}</td>
        </tr>
    ));

    return (
        <AuthenticatedLayout titleChildren="Uso por Sala / Bloco" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Uso por Sala', url: route('reports.rooms') }]}>
            <Panel>
                <PeriodFilterForm data={data} onChange={onHandleChange} onSubmit={handleSubmit} processing={processing} errors={errors} filters={filters} clearUrl={route('reports.rooms')} />
            </Panel>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> registros
                </div>
                <ExportButtons routeName="reports.rooms" params={filters} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <ChartCard title="Empréstimos por sala" className="h-80">
                    <TopRoomsBarChart data={chart} />
                </ChartCard>
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Sala</th>
                            <th className="px-2 pt-3 font-semibold text-left">Bloco</th>
                            <th className="px-2 pt-3 font-semibold text-left">Empréstimos</th>
                            <th className="px-2 pt-3 font-semibold text-left">Chaves movimentadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        {list}
                    </tbody>
                </table>
                <Pagination data={rooms} count={count} />
            </Panel>
        </AuthenticatedLayout>
    );
}

export default Index;
