import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Create({ rooms }) {
    const { data, setData, post, processing, errors } = useForm({
        number: "",
        description: "",
        observation: "",
        room_id: "",
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        post(route("keys.store"), { data });
    };

    return (
        <>
            <Head title="Nova Chave" />
            <AuthenticatedLayout
                titleChildren="Cadastro de nova Chave"
                breadcrumbs={[
                    { label: "Salas", url: route("keys.index") },
                    { label: "Nova", url: route("keys.create") },
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
                        backUrl={route("keys.index")}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Create;
