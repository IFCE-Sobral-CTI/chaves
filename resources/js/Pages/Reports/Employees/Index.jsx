import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import ChartCard from "@/Components/Dashboard/ChartCard";
import EmployeeTypePie from "@/Components/Dashboard/EmployeeTypePie";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import Select from "@/Components/Form/Select";
import Button from "@/Components/Form/Button";

function Index({ employees, count, chart, filters }) {
    const { data, setData, get, processing } = useForm({
        start: filters.start ?? '',
        end: filters.end ?? '',
        type: filters.type ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.employees'), { data });
    };

    const list = employees.data.map((employee) => (
        <tr key={employee.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
            <td className="px-2 py-3 font-light">{employee.name}</td>
            <td className="px-2 py-3 font-light">{employee.type}</td>
            <td className="px-2 py-3 font-light">{employee.borrow_count}</td>
            <td className="px-2 py-3 font-light">{employee.key_count}</td>
            <td className="px-2 py-3 font-light">{employee.overdue_count}</td>
            <td className="px-2 py-3 font-light">{employee.valid_until ?? '-'}</td>
        </tr>
    ));

    return (
        <AuthenticatedLayout titleChildren="Uso por Mutuário" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Uso por Mutuário', url: route('reports.employees') }]}>
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
                    <div className="flex-1">
                        <label htmlFor="type" className="text-xs font-light">Tipo</label>
                        <Select value={data.type} name="type" handleChange={onHandleChange}>
                            <option value="">Todos</option>
                            <option value="1">Servidor</option>
                            <option value="2">Colaborador</option>
                            <option value="3">Discente</option>
                            <option value="4">Externo</option>
                        </Select>
                    </div>
                    <div className="flex items-end gap-2">
                        {(filters.start || filters.end || filters.type) && (
                            <Button href={route('reports.employees')} className="gap-2 py-2.5" processing={processing} color="red">Limpar</Button>
                        )}
                        <Button type="submit" className="gap-2 py-2.5" processing={processing}>Buscar</Button>
                    </div>
                </form>
            </Panel>

            <div className="flex items-center justify-between print:hidden mb-4">
                <div className="text-sm text-neutral-600">
                    Total: <strong>{count}</strong> registros
                </div>
                <ExportButtons routeName="reports.employees" params={data} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <ChartCard title="Distribuição por tipo de mutuário" className="h-80">
                    <EmployeeTypePie data={chart} />
                </ChartCard>
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Nome</th>
                            <th className="px-2 pt-3 font-semibold text-left">Tipo</th>
                            <th className="px-2 pt-3 font-semibold text-left">Empréstimos</th>
                            <th className="px-2 pt-3 font-semibold text-left">Chaves movimentadas</th>
                            <th className="px-2 pt-3 font-semibold text-left">Atrasos</th>
                            <th className="px-2 pt-3 font-semibold text-left">Válido até</th>
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
