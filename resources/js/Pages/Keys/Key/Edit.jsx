import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Edit({ _key, rooms }) {
    const { data, setData, put, processing, errors } = useForm({
        number: _key.number,
        description: _key.description,
        observation: _key.observation,
        room_id: _key.room_id,
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        put(route("keys.update", _key.id), { data });
    };

    return (
        <>
            <Head title="Editar Chave" />
            <AuthenticatedLayout
                titleChildren="Editar Chave"
                breadcrumbs={[
                    { label: "Chaves", url: route("keys.index") },
                    { label: _key.description, url: route("keys.show", _key.id) },
                    { label: "Editar" },
                ]}
            >
                <Panel>
                    <Form
                        data={data}
                        setData={setData}
                        processing={processing}
                        errors={errors}
                        rooms={rooms}
                        handleSubmit={handleSubmit}
                        backUrl={route("keys.show", _key.id)}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Edit;
