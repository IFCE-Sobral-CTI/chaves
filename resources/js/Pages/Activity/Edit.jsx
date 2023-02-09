import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Edit({ group }) {
    const { data, setData, put, processing, errors } = useForm({
        description: group.description,
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('groups.update', group.id), {data});
    };

    return (
        <>
            <Head title="Editar Página" />
            <AuthenticatedLayout titleChildren={'Editar Página'} breadcrumbs={[{ label: 'Páginas', url: route('groups.index') }, { label: group.description, url: route('groups.show', group.id) }, { label: 'Editar'}]}>
                <Panel>
                    <Form data={data} errors={errors} processing={processing} onHandleChange={onHandleChange} handleSubmit={handleSubmit} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

