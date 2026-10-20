import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Form from "./Components/Form";

function Create({ employeeType, keys }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        usual_name: "",
        occupation: "",
        email: "",
        alternative_email: "",
        tel: "",
        registry: "",
        valid_until: "",
        observation: "",
        type: "",
        borrowable_key_ids: [],
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        post(route("employees.store"), { data });
    };

    return (
        <>
            <Head title="Novo Mutuário" />
            <AuthenticatedLayout
                titleChildren="Cadastro de novo Mutuário"
                breadcrumbs={[
                    { label: "Mutuários", url: route("employees.index") },
                    { label: "Novo", url: route("employees.create") },
                ]}
            >
                <Panel>
                    <Form
                        data={data}
                        setData={setData}
                        processing={processing}
                        errors={errors}
                        employeeType={employeeType}
                        keys={keys}
                        handleSubmit={handleSubmit}
                        backUrl={route("employees.index")}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Create;
