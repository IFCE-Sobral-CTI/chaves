import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ChartCard from "@/Components/Dashboard/ChartCard";
import TopRoomsBarChart from "@/Components/Dashboard/TopRoomsBarChart";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import Button from "@/Components/Form/Button";

function Index({ rooms, count, chart, filters }) {
    const { data, setData, get, processing } = useForm({
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
                <form onSubmit={handleSubmit} className="flex items-end gap-4">
                    <div className="flex-1">
                        <label htmlFor="start" className="text-xs font-light">Início</label>
                        <Input value={data.start} type="date" name="start" handleChange={onHandleChange} />
                    </div>
                    <div className="flex-1">
                        <label htmlFor="end" className="text-xs font-light">Final</label>
                        <Input value={data.end} type="date" name="end" handleChange={onHandleChange} />
                    </div>
                    <div className="flex items-end gap-2">
                        {(filters.start || filters.end) && (
                            <Button href={route('reports.rooms')} className="gap-2 py-2.5" processing={processing} color="red">Limpar</Button>
                        )}
                        <Button type="submit" className="gap-2 py-2.5" processing={processing}>Buscar</Button>
                    </div>
                </form>
            </Panel>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> registros
                </div>
                <ExportButtons routeName="reports.rooms" params={data} />
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
