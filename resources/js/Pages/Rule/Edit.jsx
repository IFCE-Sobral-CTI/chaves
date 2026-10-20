import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Edit({ rule, groups }) {
    const { data, setData, put, processing, errors } = useForm({
        description: rule.description,
        control: rule.control,
        group_id: rule.group_id,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('rules.update', rule.id), {data});
    };

    return (
        <>
            <Head title="Editar Regra" />
            <AuthenticatedLayout titleChildren={'Editar Regra'} breadcrumbs={[{ label: 'Regras', url: route('rules.index') }, { label: rule.description, url: route('rules.show', rule.id) }, { label: 'Editar'}]}>
                <Panel>
                    <Form data={data} setData={setData} handleSubmit={handleSubmit} processing={processing} errors={errors} backUrl={route('rules.show', rule.id)} groups={groups} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

