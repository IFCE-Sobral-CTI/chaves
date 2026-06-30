import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import Button from "@/Components/Form/Button";

function Index({ staff, count, filters }) {
    const { data, setData, get, processing } = useForm({
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
                            <Button href={route('reports.staff')} className="gap-2 py-2.5" processing={processing} color="red">Limpar</Button>
                        )}
                        <Button type="submit" className="gap-2 py-2.5" processing={processing}>Buscar</Button>
                    </div>
                </form>
            </Panel>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> registros
                </div>
                <ExportButtons routeName="reports.staff" params={data} />
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
