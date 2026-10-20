import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Edit({ permission }) {
    const { data, setData, put, processing, errors } = useForm({
        description: permission.description,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('permissions.update', permission.id), {data});
    };

    return (
        <>
            <Head title="Editar Permissão" />
            <AuthenticatedLayout titleChildren={'Editar Permissão'} breadcrumbs={[{ label: 'Permissões', url: route('permissions.index') }, { label: permission.description, url: route('permissions.show', permission.id) }, { label: 'Editar'}]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} processing={processing} errors={errors} backUrl={route('permissions.show', permission.id)} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

