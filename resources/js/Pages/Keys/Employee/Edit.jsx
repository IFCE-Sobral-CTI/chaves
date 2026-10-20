import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import moment from "moment";
import Form from "./Components/Form";
import { formatPhone } from "./phone";

function Edit({ employee, employeeType, keys }) {
    const { data, setData, put, processing, errors } = useForm({
        name: employee.name,
        usual_name: employee.usual_name ?? "",
        occupation: employee.occupation ?? "",
        email: employee.email,
        alternative_email: employee.alternative_email,
        tel: formatPhone(employee.tel),
        registry: employee.registry,
        observation: employee.observation,
        valid_until: employee.valid_until ? moment(employee.valid_until, "DD/MM/YYYY").format("yyyy-MM-DD") : null,
        type: employee.type,
        borrowable_key_ids: employee.borrowable_keys.map((key) => key.id),
    });
    const handleSubmit = (event) => {
        event.preventDefault();
        put(route("employees.update", employee.id), { data });
    };

    return (
        <>
            <Head title="Editar Mutuário" />
            <AuthenticatedLayout
                titleChildren="Editar Mutuário"
                breadcrumbs={[
                    { label: "Mutuários", url: route("employees.index") },
                    { label: employee.description, url: route("employees.show", employee.id) },
                    { label: "Editar" },
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
                        backUrl={route("employees.show", employee.id)}
                    />
                </Panel>
            </AuthenticatedLayout>
        </>
    );
}

export default Edit;
