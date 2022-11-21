import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Form from "@/Pages/Keys/Block/Components/Form";

function Edit({ block }) {
    const { data, setData, put, processing, errors } = useForm({
        description: block.description,
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('blocks.update', block.id), {data});
    };

    return (
        <>
            <Head title="Editar Bloco" />
            <AuthenticatedLayout titleChildren={'Editar Bloco'} breadcrumbs={[{ label: 'Blocos', url: route('blocks.index') }, { label: block.description, url: route('blocks.show', block.id) }, { label: 'Editar'}]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} errors={errors} processing={processing} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

