import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Button from "@/Components/Form/Button";
import DeleteModal from "@/Components/Dashboard/DeleteModal";

function Show({ employee, can, keys }) {
    const employeeType = (type) => {
        let name = '';

        switch (type) {
            case 1:
                name = 'Servidor';
                break;
            case 2:
                name = 'Colaborador';
                break;
            case 3:
                name = 'Discente';
                break;
            case 4:
                name = 'Externo';
                break;
        };

        return (
            <span>{name}</span>
        );
    };

    return (
        <>
            <Head title="Detalhes da Mutuário" />
            <AuthenticatedLayout titleChildren={'Detalhes da Mutuário'} breadcrumbs={[{ label: 'Mutuários', url: route('employees.index') }, { label: employee.description, url: route('employees.show', employee.id) }]}>
                <Panel className={'flex flex-col gap-4'}>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Vínculo</div>
                        <div className="">{employeeType(employee.type)}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Matricula</div>
                        <div className="">{employee.registry}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Nome</div>
                        <div className="">{employee.name}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">E-mail</div>
                        <div className="">{employee.email}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">E-mail Alternativa</div>
                        <div className="">{employee.alternative_email ?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Telefone</div>
                        <div className="">{employee.tel ?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Observações</div>
                        <div className="">{employee.observation?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Cadastro válido até</div>
                        <div className="">{employee.valid_until?? '-'}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Chaves autorizadas</div>
                        <div className="">
                            {employee.borrowable_keys.map((it, i) => {
                                return <span className="px-1 mr-1 text-sm font-normal text-white rounded-md bg-sky-500" title={it.room.description} key={i}>{it.number}</span>
                            })}
                        </div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Criado em</div>
                        <div className="">{employee.created_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Atualizado em</div>
                        <div className="">{employee.updated_at}</div>
                    </div>
                </Panel>
                <Panel className={'flex flex-wrap items-center justify-center gap-1 md:gap-4'}>
                    <Button href={route('employees.index')} className={'gap-2'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                        <span>Voltar</span>
                    </Button>
                    {can.update && <Button href={route('employees.edit', employee.id)} className={'gap-2'} color={'yellow'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                        </svg>
                        <span>Editar</span>
                    </Button>}
                    {can.delete && <DeleteModal url={route('employees.destroy', employee.id)} />}
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Show;

