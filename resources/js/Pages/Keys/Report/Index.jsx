import Panel from "@/Components/Dashboard/Panel";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Button from "@/Components/Form/Button";
import { useForm } from "@inertiajs/inertia-react";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import moment from "moment";
import Pagination from "@/Components/Dashboard/Pagination";
import Select from "@/Components/Form/Select";
import SelectEmployee from "./Components/SelectEmployee";

function Index({ errors, borrows, count, filter, filters, users, employees }) {
    const { data, setData, get, processing } = useForm({
        start: filters.start,
        end: filters.end,
        employee: filters.employee,
        user: filters.user,
        situation: filters.situation,
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('reports.index'), {data});
    };

    const status = (created_at, devolution) => {
        let start = moment(created_at, "DD/MM/YYYY hh:mm:ss");
        let end = moment(created_at, "DD/MM/YYYY hh:mm:ss").add(1, 'd');
        let now = moment();

        if (devolution)
            return <span className="px-1 text-sm text-white rounded-md bg-green">Devolvido</span>

        if (now.isBetween(start, end) && !devolution)
            return <span className="px-1 text-sm text-white bg-yellow-500 rounded-md">Aberto</span>

        if (now.isAfter(end) && !devolution)
            return <span className="px-1 text-sm text-white bg-red-500 rounded-md">Atrasado</span>
    }

    const list = borrows.data.map((borrow, i) => {
        return (
            <tr key={i} className={"border-t text-sm transition hover:bg-neutral-100 " + (i % 2 == 0? 'bg-neutral-50': '')}>
                <td className="px-1 py-3 font-light">{borrow.created_at}</td>
                <td className="px-1 py-3 font-light">{borrow.devolution}</td>
                <td className="px-1 py-3 font-light">{borrow.employee.name}</td>
                <td className="px-1 py-3 font-light">{borrow.user.name}</td>
                <td className="px-1 py-3 font-light">
                    <div className="flex flex-wrap gap-2">
                        {borrow.received.map((rec, i) => {
                            return (
                                <div key={'Key-' + i}>{rec.user.name}</div>
                            )
                        })}
                    </div>
                </td>
                <td className="px-1 py-3 font-light">
                    {borrow.received.map((rec, i) => {
                        return (
                            <div key={'Key-' + i}>{rec.receiver}</div>
                        )
                    })}
                </td>
                <td className="px-1 py-3 font-light">
                    <div className="flex flex-wrap gap-2">
                        {borrow.keys.map((key, i) => {
                            return (
                                <span key={'Key-' + i} className="px-1 text-sm font-semibold text-white transition rounded-md bg-sky-400 hover:bg-sky-500">{key.number}</span>
                            )
                        })}
                    </div>
                </td>
                <td className="px-1 py-3 font-light">{status(borrow.created_at, borrow.devolution)}</td>
            </tr>
        )
    });

    return (
        <>
            <AuthenticatedLayout titleChildren={'Relatórios'} breadcrumbs={[{ label: 'Relatórios', url: route('reports.index') }]}>
                <Panel>
                    <form onSubmit={handleSubmit} className="flex items-end justify-between gap-4">
                        <div className="w-1/3 p-2 border rounded-lg">
                            <div className="">Por data</div>
                            <div className="flex gap-2">
                                <div className="flex-1">
                                    <label htmlFor="start" className="font-light">Início</label>
                                    <Input value={data.start} type={'date'} name={'start'} handleChange={onHandleChange} />
                                    <InputError message={errors.start} />
                                </div>
                                <div className="flex-1">
                                    <label htmlFor="end" className="font-light">Final</label>
                                    <Input value={data.end} type={'date'} name={'end'} handleChange={onHandleChange} />
                                    <InputError message={errors.end} />
                                </div>
                            </div>
                        </div>
                        <div className="flex justify-between w-2/3 gap-4">
                            <div className="flex-1 p-2 border rounded-lg">
                                <div className="">Por Mutuário</div>
                                <SelectEmployee value={data.employee} data={employees} onChange={(id) => setData('employee', id)}  error={errors.employee} />
                            </div>
                            <div className="flex-1 p-2 border rounded-lg">
                                <div className="">Entregue por</div>
                                <div className="">
                                    <label htmlFor="user" className="font-light">Usuário</label>
                                    <Select value={data.user} name={'user'} handleChange={onHandleChange}>
                                        <option>Selecione um Usuário</option>
                                        {users.map((user, index) => {
                                            return (
                                                <option value={user.id} key={index}>{user.name}</option>
                                            );
                                        })}
                                    </Select>
                                    <InputError message={errors.user} />
                                </div>
                            </div>
                            <div className="flex-1 p-2 border rounded-lg">
                                <div className="">Por Situação</div>
                                <div className="">
                                    <label htmlFor="situation" className="font-light">Situação</label>
                                    <Select value={data.situation} name={'situation'} handleChange={onHandleChange}>
                                        <option>Selecione um Usuário</option>
                                        <option value={1}>Devolvido</option>
                                        <option value={2}>Aberto</option>
                                        <option value={3}>Atrasado</option>
                                    </Select>
                                    <InputError message={errors.situation} />
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-col gap-2">
                            {filter
                            ?<div className="transition" title="Limpar filtro">
                                <Button href={route('reports.index')} className={'gap-2 py-2.5'} processing={processing} color={'red'}>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fillRule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                    Limpar
                                </Button>
                            </div>
                            :null}
                            <Button type={'submit'} className={'gap-2 py-2.5'} processing={processing}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                                Buscar
                            </Button>
                        </div>
                    </form>
                </Panel>
                <Panel>
                    <table className="w-full table-auto text-neutral-600">
                        <thead>
                            <tr>
                                <th colSpan={2} className="px-1 pt-3 font-semibold text-center border-b">Datas</th>
                                <th></th>
                                <th colSpan={2} className="px-1 pt-3 font-semibold text-center border-b">Usuários</th>
                            </tr>
                            <tr>
                                <th className="px-1 pt-3 font-semibold text-left">Entrega</th>
                                <th className="px-1 pt-3 font-semibold text-left">Recebida</th>
                                <th rowSpan={2} className="px-1 pt-3 font-semibold text-left">Mutuário</th>
                                <th className="px-1 pt-3 font-semibold text-left">Entregue por</th>
                                <th className="px-1 pt-3 font-semibold text-left">Recebido por</th>
                                <th className="px-1 pt-3 font-semibold text-left">Devolvida por</th>
                                <th className="px-1 pt-3 font-semibold text-left">Chaves</th>
                                <th className="px-1 pt-3 font-semibold text-left">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list}
                        </tbody>
                    </table>
                    <Pagination data={borrows} count={count} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Index;
