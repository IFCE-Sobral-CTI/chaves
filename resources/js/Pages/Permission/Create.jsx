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
        post(route('permissions.store'), {data});
    };

    return (
        <>
            <Head title="Nova Permissão" />
            <AuthenticatedLayout titleChildren={'Cadastro de Nova Permissão'} breadcrumbs={[{ label: 'Permissões', url: route('permissions.index') }, { label: 'Nova', url: route('permissions.create') }]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} processing={processing} errors={errors} backUrl={route('permissions.index')} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

