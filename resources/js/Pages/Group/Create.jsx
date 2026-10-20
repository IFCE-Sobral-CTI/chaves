import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Create() {
    const { data, setData, post, processing, errors } = useForm({
        description: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('groups.store'), {data});
    };

    return (
        <>
            <Head title="Nova Grupo de pagina" />
            <AuthenticatedLayout titleChildren={'Cadastro de nova Página'} breadcrumbs={[{ label: 'Páginas', url: route('groups.index') }, { label: 'Nova', url: route('groups.create') }]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} processing={processing} errors={errors} backUrl={route('groups.index')} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

