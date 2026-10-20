import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Create({ groups }) {
    const { data, setData, post, processing, errors } = useForm({
        description: "",
        control: "",
        group_id: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('rules.store'), {data});
    };

    return (
        <>
            <Head title="Nova Regra" />
            <AuthenticatedLayout titleChildren={'Cadastro de Nova Regra'} breadcrumbs={[{ label: 'Regras', url: route('rules.index') }, { label: 'Nova', url: route('rules.create') }]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} processing={processing} errors={errors} backUrl={route('rules.index')} groups={groups} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

