import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Button from "@/Components/Form/Button";
import { useForm } from "@inertiajs/react";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Pagination from "@/Components/Dashboard/Pagination";
import Select from "@/Components/Form/Select";
import SelectEmployee from "@/Pages/Keys/Report/Components/SelectEmployee";
import ReportSummary from "@/Pages/Reports/Components/ReportSummary";
import ExportButtons from "@/Pages/Reports/Components/ExportButtons";

function Index({ errors, borrows, count, filter, filters, users, employees, blocks, rooms, keys, summary }) {
    const { data, setData, get, processing } = useForm({
        start: filters.start ?? '',
        end: filters.end ?? '',
        employee: filters.employee ?? '',
        user: filters.user ?? '',
        situation: filters.situation ?? '',
        block: filters.block ?? '',
        room: filters.room ?? '',
        key: filters.key ?? '',
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.borrows'), { data });
    };

    const situationBadge = (situation) => {
        const classes = {
            devolvido: 'px-2 py-0.5 text-xs text-white rounded bg-green-500',
            aberto: 'px-2 py-0.5 text-xs text-white rounded bg-yellow-500',
            atrasado: 'px-2 py-0.5 text-xs text-white rounded bg-red-500',
        };
        const labels = {
            devolvido: 'Devolvido',
            aberto: 'Aberto',
            atrasado: 'Atrasado',
        };

        return <span className={classes[situation] ?? 'px-2 py-0.5 text-xs text-white rounded bg-neutral-400'}>{labels[situation] ?? situation}</span>;
    };

    const list = borrows.data.map((borrow) => {
        return (
            <tr key={borrow.id} className="border-t border-neutral-200 text-sm transition hover:bg-neutral-100">
                <td className="px-2 py-3 font-light">{borrow.created_at}</td>
                <td className="px-2 py-3 font-light">{borrow.devolution ?? '-'}</td>
                <td className="px-2 py-3 font-light">{borrow.employee?.name}</td>
                <td className="px-2 py-3 font-light">{borrow.user?.name}</td>
                <td className="px-2 py-3 font-light">
                    <div className="flex flex-wrap gap-1">
                        {borrow.received.map((rec) => (
                            <div key={`rec-user-${rec.id}`}>{rec.user?.name}</div>
                        ))}
                        {borrow.received.length === 0 && '-'}
                    </div>
                </td>
                <td className="px-2 py-3 font-light">
                    <div className="flex flex-wrap gap-1">
                        {borrow.received.map((rec) => (
                            <div key={`rec-receiver-${rec.id}`}>{rec.receiver}</div>
                        ))}
                        {borrow.received.length === 0 && '-'}
                    </div>
                </td>
                <td className="px-2 py-3 font-light">
                    <div className="flex flex-wrap gap-1">
                        {borrow.keys.map((key) => (
                            <span key={`key-${key.id}`} className="px-1.5 py-0.5 text-xs font-semibold text-white rounded bg-sky-400">
                                {key.number}
                            </span>
                        ))}
                    </div>
                </td>
                <td className="px-2 py-3 font-light">{situationBadge(borrow.situation)}</td>
            </tr>
        );
    });

    return (
        <AuthenticatedLayout titleChildren="Relatório de Empréstimos" breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }, { label: 'Empréstimos', url: route('reports.borrows') }]}>
            <Panel>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por data</div>
                            <div className="flex gap-2">
                                <div className="flex-1">
                                    <label htmlFor="start" className="text-xs font-light">Início</label>
                                    <Input value={data.start} type="date" name="start" handleChange={onHandleChange} />
                                    <InputError message={errors.start} />
                                </div>
                                <div className="flex-1">
                                    <label htmlFor="end" className="text-xs font-light">Final</label>
                                    <Input value={data.end} type="date" name="end" handleChange={onHandleChange} />
                                    <InputError message={errors.end} />
                                </div>
                            </div>
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por Mutuário</div>
                            <SelectEmployee value={data.employee} data={employees} onChange={(id) => setData('employee', id)} error={errors.employee} />
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Entregue por</div>
                            <label htmlFor="user" className="text-xs font-light">Usuário</label>
                            <Select value={data.user} name="user" handleChange={onHandleChange}>
                                <option value="">Selecione um Usuário</option>
                                {users.map((user) => (
                                    <option value={user.id} key={`user-${user.id}`}>{user.name}</option>
                                ))}
                            </Select>
                            <InputError message={errors.user} />
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por Situação</div>
                            <label htmlFor="situation" className="text-xs font-light">Situação</label>
                            <Select value={data.situation} name="situation" handleChange={onHandleChange}>
                                <option value="">Selecione uma Situação</option>
                                <option value="1">Devolvido</option>
                                <option value="2">Aberto</option>
                                <option value="3">Atrasado</option>
                            </Select>
                            <InputError message={errors.situation} />
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por Bloco</div>
                            <label htmlFor="block" className="text-xs font-light">Bloco</label>
                            <Select value={data.block} name="block" handleChange={onHandleChange}>
                                <option value="">Selecione um Bloco</option>
                                {blocks.map((block) => (
                                    <option value={block.id} key={`block-${block.id}`}>{block.description}</option>
                                ))}
                            </Select>
                            <InputError message={errors.block} />
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por Sala</div>
                            <label htmlFor="room" className="text-xs font-light">Sala</label>
                            <Select value={data.room} name="room" handleChange={onHandleChange}>
                                <option value="">Selecione uma Sala</option>
                                {rooms.map((room) => (
                                    <option value={room.id} key={`room-${room.id}`}>{room.description}</option>
                                ))}
                            </Select>
                            <InputError message={errors.room} />
                        </div>

                        <div className="p-3 border border-neutral-300 rounded-lg">
                            <div className="text-sm font-medium text-neutral-600 mb-2">Por Chave</div>
                            <label htmlFor="key" className="text-xs font-light">Chave</label>
                            <Select value={data.key} name="key" handleChange={onHandleChange}>
                                <option value="">Selecione uma Chave</option>
                                {keys.map((key) => (
                                    <option value={key.id} key={`key-${key.id}`}>{key.number}</option>
                                ))}
                            </Select>
                            <InputError message={errors.key} />
                        </div>

                        <div className="flex items-end gap-2">
                            {filter && (
                                <Button href={route('reports.borrows')} className="gap-2 py-2.5" processing={processing} color="red">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fillRule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                    Limpar
                                </Button>
                            )}
                            <Button type="submit" className="gap-2 py-2.5" processing={processing}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                                Buscar
                            </Button>
                        </div>
                    </div>
                </form>
            </Panel>

            <div className="flex items-center justify-between print:hidden">
                <ReportSummary summary={summary} />
                <ExportButtons routeName="reports.borrows" params={data} />
            </div>

            <Panel>
                <table className="w-full table-auto text-neutral-600">
                    <thead>
                        <tr>
                            <th colSpan={2} className="px-2 pt-3 font-semibold text-center border-b border-neutral-300">Datas</th>
                            <th></th>
                            <th colSpan={2} className="px-2 pt-3 font-semibold text-center border-b border-neutral-300">Usuários</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th className="px-2 pt-3 font-semibold text-left">Entrega</th>
                            <th className="px-2 pt-3 font-semibold text-left">Recebida</th>
                            <th rowSpan={2} className="px-2 pt-3 font-semibold text-left">Mutuário</th>
                            <th className="px-2 pt-3 font-semibold text-left">Entregue por</th>
                            <th className="px-2 pt-3 font-semibold text-left">Recebido por</th>
                            <th className="px-2 pt-3 font-semibold text-left">Devolvida por</th>
                            <th className="px-2 pt-3 font-semibold text-left">Chaves</th>
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
