import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Button from "@/Components/Form/Button";
import DeleteModal from "@/Components/Dashboard/DeleteModal";
import Receive from "./Components/Receive";

function Show({ borrow, can }) {
    return (
        <>
            <Head title="Detalhes da Sala" />
            <AuthenticatedLayout titleChildren={'Detalhes da Sala'} breadcrumbs={[{ label: 'Salas', url: route('borrows.index') }, { label: borrow.description, url: route('borrows.show', borrow.id) }]}>
                <Panel className={'flex flex-col gap-4'}>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Servidor</div>
                        <div className="">{borrow.employee.name}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Devolução</div>
                        <div className="">{borrow.devolution?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Observações</div>
                        <div className="">{borrow.observation}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Criado em</div>
                        <div className="">{borrow.created_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Atualizado em</div>
                        <div className="">{borrow.updated_at}</div>
                    </div>
                </Panel>
                <Panel>
                    <h3 className="text-lg font-semibold">Chaves</h3>
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
                    {(can.receive && !borrow.devolution) && <Receive url={route('borrows.receive', borrow.id)} />}
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Show;

