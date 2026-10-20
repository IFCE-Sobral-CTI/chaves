import PeriodFilterForm from "@/Pages/Reports/Components/PeriodFilterForm";
import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";

function Index({ staff, count, filters }) {
    const { data, setData, get, processing, errors } = useForm({
        start: filters.start ?? '',
        end: filters.end ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.staff'), { data });
    };

    const list = staff.data.map((item) => (
        <tr key={item.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
            <td className="px-2 py-3 font-light">{item.name}</td>
            <td className="px-2 py-3 font-light">{item.delivery_count}</td>
            <td className="px-2 py-3 font-light">{item.receipt_count}</td>
        </tr>
    ));

    return (
        <AuthenticatedLayout titleChildren="Produtividade por Recepcionista" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Produtividade', url: route('reports.staff') }]}>
            <Panel>
                <PeriodFilterForm data={data} onChange={onHandleChange} onSubmit={handleSubmit} processing={processing} errors={errors} filters={filters} clearUrl={route('reports.staff')} />
            </Panel>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> registros
                </div>
                <ExportButtons routeName="reports.staff" params={filters} />
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Nome</th>
                            <th className="px-2 pt-3 font-semibold text-left">Entregas</th>
                            <th className="px-2 pt-3 font-semibold text-left">Recebimentos</th>
                        </tr>
                    </thead>
                    <tbody>
                        {list}
                    </tbody>
                </table>
                <Pagination data={staff} count={count} />
            </Panel>
        </AuthenticatedLayout>
    );
}

export default Index;
