import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "@/Pages/Keys/Block/Components/Form";

function Create() {
    const { data, setData, post, processing, errors } = useForm({
        description: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('blocks.store'), {data});
    };

    return (
        <>
            <Head title="Novo Bloco" />
            <AuthenticatedLayout titleChildren={'Cadastro de novo Bloco'} breadcrumbs={[{ label: 'Blocos', url: route('blocks.index') }, { label: 'Novo', url: route('blocks.create') }]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} errors={errors} processing={processing} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

