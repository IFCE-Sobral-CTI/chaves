import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import Button from "@/Components/Form/Button";

function Index({ turnaround, summary, filters }) {
    const { data, setData, get, processing } = useForm({
        start: filters.start ?? '',
        end: filters.end ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.turnaround'), { data });
    };

    const list = turnaround.map((item) => (
        <tr key={`${item.category}-${item.dimension}`} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
            <td className="px-2 py-3 font-light">
                <span className="px-2 py-0.5 text-xs rounded bg-neutral-100 text-neutral-600">{item.category}</span>
            </td>
            <td className="px-2 py-3 font-light">{item.dimension}</td>
            <td className="px-2 py-3 font-light">{item.avg_hours}h</td>
            <td className="px-2 py-3 font-light">{item.count}</td>
        </tr>
    ));

    return (
        <AuthenticatedLayout titleChildren="Tempo Médio de Devolução" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Turnaround', url: route('reports.turnaround') }]}>
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
                            <Button href={route('reports.turnaround')} className="gap-2 py-2.5" processing={processing} color="red">Limpar</Button>
                        )}
                        <Button type="submit" className="gap-2 py-2.5" processing={processing}>Buscar</Button>
                    </div>
                </form>
            </Panel>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                <div className="flex flex-col items-center justify-center p-3 text-white transition rounded-lg shadow-sm bg-neutral-500">
                    <span className="text-xs font-bold tracking-wider uppercase">Total devolvidos</span>
                    <span className="text-2xl font-bold">{summary.total}</span>
                </div>
                <div className="flex flex-col items-center justify-center p-3 text-white transition rounded-lg shadow-sm bg-sky-500">
                    <span className="text-xs font-bold tracking-wider uppercase">Tempo médio</span>
                    <span className="text-2xl font-bold">{summary.avg_hours}h</span>
                </div>
                <div className="flex flex-col items-center justify-center p-3 text-white transition rounded-lg shadow-sm bg-emerald-500">
                    <span className="text-xs font-bold tracking-wider uppercase">Dentro de 24h</span>
                    <span className="text-2xl font-bold">{summary.within_24h}</span>
                </div>
            </div>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Em atraso: <strong>{summary.overdue}</strong>
                </div>
                <ExportButtons routeName="reports.turnaround" params={data} />
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Categoria</th>
                            <th className="px-2 pt-3 font-semibold text-left">Dimensão</th>
                            <th className="px-2 pt-3 font-semibold text-left">Tempo médio (horas)</th>
                            <th className="px-2 pt-3 font-semibold text-left">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        {list}
                    </tbody>
                </table>
            </Panel>
        </AuthenticatedLayout>
    );
}

export default Index;
