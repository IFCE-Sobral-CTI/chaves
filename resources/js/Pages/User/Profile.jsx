import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Button from "@/Components/Form/Button";

function Profile({ user }) {
    return (
        <>
            <Head title="Detalhes do usuário" />
            <AuthenticatedLayout titleChildren={'Detalhes do Usuário'} breadcrumbs={[{ label: user.name, url: route('profile') }, { label: 'Perfil'}]}>
                <Panel className={'flex flex-col gap-4'}>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Status</div>
                        <div>
                            {user.status == "1"
                            ?<div className={"inline-flex bg-green rounded-lg py-1.5 px-4 text-white text-sm"}>Ativo</div>
                            :<div className={"inline-flex bg-red-500 rounded-lg py-1.5 px-4 text-white text-sm"}>Inativo</div>}
                        </div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Permissão de acesso</div>
                        <div className="">{user.permission.description}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Matricula</div>
                        <div className="">{user.registry}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Nome</div>
                        <div className="">{user.name}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">E-mail</div>
                        <div className="">{user.email}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Validado em</div>
                        <div className="">{user.email_verified_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Criado em</div>
                        <div className="">{user.created_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Atualizado em</div>
                        <div className="">{user.updated_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="font-semibold">Para alterar a senha, usar a opção na página de login.</div>
                    </div>
                </Panel>
                <Panel className={'flex flex-wrap items-center justify-center gap-1 md:gap-4'}>
                    <Button href={route('admin')} className={'gap-2'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                        <span>Voltar</span>
                    </Button>
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Profile;

