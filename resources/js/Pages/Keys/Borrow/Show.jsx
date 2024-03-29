import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Button from "@/Components/Form/Button";
import DeleteModal from "@/Components/Dashboard/DeleteModal";
import Receive from "./Components/Receive";

function Show({ borrow, received, can }) {

    const button = (
        <button
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#delete-modal"
            className="inline-flex items-center gap-2 p-1 text-sm tracking-widest text-white transition duration-150 ease-in-out bg-red-500 border border-transparent rounded-md active:bg-red-700 hover:bg-red-600"
            title="Apagar"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" role="img" aria-hidden="true" viewBox="0 0 16 16">
                <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/>
            </svg>
        </button>
    );

    const table = borrow.received.map((item, index) => {
        return (
            <tr className={"border-t transition hover:bg-neutral-100 " + (index % 2 == 0? 'bg-neutral-50': '')} key={index}>
                <td className="px-1 py-3 font-light">{item.created_at}</td>
                <td className="px-1 py-3 font-light">{item.receiver}</td>
                <td className="px-1 py-3 font-light">{item.user.name}</td>
                <td className="px-1 py-3 font-light">
                    <div className="flex gap-1">
                        {item.keys.map((item, i) => {
                            return (
                                <span
                                    key={i}
                                    className="px-1 ml-1 text-sm text-white rounded-md py-0.5 bg-green-light font-normal"
                                    title={item.room.description}
                                >
                                    {item.number}
                                </span>
                            )
                        })}
                    </div>
                </td>
                {(can.receive && (!borrow.devolution)) && <td>
                    <DeleteModal url={route('borrows.receive.destroy', {borrow: borrow.id, received: item.id})} button={button} forTable />
                </td>}
            </tr>
    )});

    return (
        <>
            <Head title="Detalhes da Sala" />
            <AuthenticatedLayout titleChildren={'Detalhes do Empréstimo'} breadcrumbs={[{ label: 'Empréstimo', url: route('borrows.index') }, { label: borrow.employee.name, url: route('borrows.show', borrow.id) }]}>
                <Panel className={'flex flex-col gap-4'}>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Mutuário</div>
                        <div className="">{borrow.employee.name}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Observações</div>
                        <div className="">{borrow.observation?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Entregue por</div>
                        <div className="">{borrow.user.name?? '-'}</div>
                    </div>
                    <div className="flex gap-4">
                        <div className="flex flex-col flex-1">
                            <div className="text-sm font-light">Devolvido em</div>
                            <div className="">{borrow.devolution?? '-'}</div>
                        </div>
                        <div className="flex flex-col flex-1">
                            <div className="text-sm font-light">Criado em</div>
                            <div className="">{borrow.created_at}</div>
                        </div>
                        <div className="flex flex-col flex-1">
                            <div className="text-sm font-light">Atualizado em</div>
                            <div className="">{borrow.updated_at}</div>
                        </div>
                    </div>
                </Panel>
                <Panel>
                    <h3 className="mb-2 text-lg font-semibold text-gray-600 border-b border-gray-400">Chaves Emprestadas</h3>
                    <div className="flex flex-wrap gap-4">
                        {borrow.keys.map((item, i) => {
                            return (
                                <div className="flex gap-2 px-4 py-2 transition rounded-lg bg-neutral-200" key={i}>
                                    <div className="">
                                        <span className="text-xs font-light">Número</span>
                                        <p>{item.number}</p>
                                    </div>
                                    <div className="pl-2 border-l border-neutral-700">
                                        <span className="text-xs font-light">Sala</span>
                                        <p>{item.room.description}</p>
                                    </div>
                                </div>
                            )
                        })}
                    </div>
                </Panel>
                <Panel>
                    <h3 className="text-lg font-semibold text-gray-600 border-b border-gray-400">Chaves Devolvidas</h3>
                    <div className="flex flex-wrap gap-4">
                    <table className="w-full table-auto text-neutral-600">
                        <thead>
                            <tr className="border-b">
                                <th className="px-1 pt-3 font-semibold text-left">Data</th>
                                <th className="hidden px-1 pt-3 font-semibold text-left md:table-cell">Devolvido por</th>
                                <th className="px-1 pt-3 font-semibold text-left">Usuário</th>
                                <th className="hidden px-1 pt-3 font-semibold text-left md:table-cell">Chaves</th>
                                {(can.receive && (!borrow.devolution)) && <th></th>}
                            </tr>
                        </thead>
                        <tbody>
                            {table}
                        </tbody>
                    </table>
                    </div>
                </Panel>
                <Panel className={'flex flex-wrap items-center justify-center gap-1 md:gap-4'}>
                    <Button href={route('borrows.index')} className={'gap-2'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                        <span>Voltar</span>
                    </Button>
                    {can.update && <Button href={route('borrows.edit', borrow.id)} className={'gap-2'} color={'yellow'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                        </svg>
                        <span>Editar</span>
                    </Button>}
                    {can.delete && <DeleteModal url={route('borrows.destroy', borrow.id)} />}
                    {(can.receive && (!borrow.devolution)) && <Receive keys={borrow.keys} borrow={borrow} received={received} />}
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Show;
