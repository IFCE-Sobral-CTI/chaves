import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Create({ blocks, employeeOptions }) {
    const { data, setData, post, processing, errors } = useForm({
        description: "",
        observation: "",
        block_id: "",
        employees: [],
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        post(route("rooms.store"), { data });
    };

    return (
        <>
            <Head title="Nova Sala" />
            <AuthenticatedLayout
                titleChildren="Cadastro de nova Sala"
                breadcrumbs={[
                    { label: "Salas", url: route("rooms.index") },
                    { label: "Nova", url: route("rooms.create") },
                ]}
            >
                <Panel>
                    <Form
                        data={data}
                        setData={setData}
                        processing={processing}
                        errors={errors}
                        blocks={blocks}
                        employeeOptions={employeeOptions}
                        handleSubmit={handleSubmit}
                        backUrl={route("rooms.index")}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Create;
