import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Edit({ room, blocks, employeeOptions }) {
    const { data, setData, put, processing, errors } = useForm({
        description: room.description,
        observation: room.observation,
        block_id: room.block_id,
        employees: room.employees.map((item) => item.id),
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        put(route("rooms.update", room.id), { data });
    };

    return (
        <>
            <Head title="Editar Sala" />
            <AuthenticatedLayout
                titleChildren="Editar Sala"
                breadcrumbs={[
                    { label: "Salas", url: route("rooms.index") },
                    { label: room.description, url: route("rooms.show", room.id) },
                    { label: "Editar" },
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
                        backUrl={route("rooms.show", room.id)}
                        selectedEmployees={room.employees}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Edit;
