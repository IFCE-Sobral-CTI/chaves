import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import Select from "@/Components/Form/Select";
import Button from "@/Components/Form/Button";

function Index({ employees, count, filters }) {
    const { data, setData, get, processing } = useForm({
        window: filters.window ?? 30,
        type: filters.type ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.expiring-access'), { data });
    };

    const list = employees.data.map((employee) => (
        <tr key={employee.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
            <td className="px-2 py-3 font-light">{employee.name}</td>
            <td className="px-2 py-3 font-light">{employee.registry}</td>
            <td className="px-2 py-3 font-light">{employee.valid_until}</td>
            <td className="px-2 py-3 font-light">{employee.type}</td>
        </tr>
    ));

    return (
        <AuthenticatedLayout titleChildren="Permissões Expirando" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Permissões Expirando', url: route('reports.expiring-access') }]}>
            <Panel>
                <form onSubmit={handleSubmit} className="flex items-end gap-4">
                    <div className="flex-1">
                        <label htmlFor="window" className="text-xs font-light">Próximos dias</label>
                        <Input value={data.window} type="number" name="window" handleChange={onHandleChange} min="1" />
                    </div>
                    <div className="flex-1">
                        <label htmlFor="type" className="text-xs font-light">Tipo</label>
                        <Select value={data.type} name="type" handleChange={onHandleChange}>
                            <option value="">Todos</option>
                            <option value="3">Discente</option>
                            <option value="4">Externo</option>
                        </Select>
                    </div>
                    <div className="flex items-end gap-2">
                        {filters.window && (
                            <Button href={route('reports.expiring-access')} className="gap-2 py-2.5" processing={processing} color="red">
                                Limpar
                            </Button>
                        )}
                        <Button type="submit" className="gap-2 py-2.5" processing={processing}>
                            Buscar
                        </Button>
                    </div>
                </form>
            </Panel>

            <div className="flex items-center justify-between print:hidden">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> permissões próximas do vencimento
                </div>
                <ExportButtons routeName="reports.expiring-access" params={data} />
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Nome</th>
                            <th className="px-2 pt-3 font-semibold text-left">Matrícula</th>
                            <th className="px-2 pt-3 font-semibold text-left">Válido até</th>
                            <th className="px-2 pt-3 font-semibold text-left">Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        {list}
                    </tbody>
                </table>
                <Pagination data={employees} count={count} />
            </Panel>
        </AuthenticatedLayout>
    );
}

export default Index;
