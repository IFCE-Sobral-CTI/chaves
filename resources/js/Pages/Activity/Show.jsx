import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Button from "@/Components/Form/Button";
import DeleteModal from "@/Components/Dashboard/DeleteModal";
import Description from "./Components/Description";
import Properties from "./Components/Properties";

function Show({ activity, can }) {
    return (
        <>
            <Head title="Detalhes da Página" />
            <AuthenticatedLayout titleChildren={'Detalhes do Log'} breadcrumbs={[{ label: 'Logs', url: route('activities.index') }, { label: activity.description, url: route('activities.show', activity.id) }]}>
                <Panel className={'flex flex-col gap-4'}>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Descrição</div>
                        <div className=""><Description title={activity.description} /></div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Módulo</div>
                        <div className="">{activity.subject_type}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Usuário</div>
                        <div className="">{activity.causer?.name}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Propriedades</div>
                        <div className=""><Properties properties={activity.properties} /></div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Criado em</div>
                        <div className="">{activity.created_at}</div>
                    </div>
                    <div className="flex flex-col">
                        <div className="text-sm font-light">Atualizado em</div>
                        <div className="">{activity.updated_at}</div>
                    </div>
                </Panel>
                <Panel className={'flex flex-wrap items-center justify-center gap-1 md:gap-4'}>
                    <Button href={route('activities.index')} className={'gap-2'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                            <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                        <span>Voltar</span>
                    </Button>
                    {can.delete && <DeleteModal url={route('activities.destroy', activity.id)} />}
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Show;

