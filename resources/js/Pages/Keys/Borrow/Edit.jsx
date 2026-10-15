import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import FormEdit from "@/Pages/Keys/Borrow/Components/FormEdit";

function Edit({ borrow, employees, keys, keysLastPage, borrowKeys }) {
    const { data, setData, put, processing, errors } = useForm({
        observation: borrow.observation,
        employee_id: borrow.employee_id,
        keys: borrowKeys,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('borrows.update', borrow.id), {data});
    };

    return (
        <>
            <Head title="Editar Empréstimo" />
            <AuthenticatedLayout
                titleChildren={'Editar Empréstimo'}
                breadcrumbs={[
                    { label: 'Empréstimos', url: route('borrows.index') },
                    { label: borrow.employee.name, url: route('borrows.show', borrow.id)},
                    { label: 'Editar' }
                ]}
            >
                <Panel>
                    <FormEdit data={data} employees={employees} keys={keys} keysLastPage={keysLastPage} employeeSearch={route('borrows.options.employees.edit', borrow.id)} keySearch={route('borrows.options.keys.edit', borrow.id)} setData={setData} errors={errors} processing={processing} handleSubmit={handleSubmit} borrow={borrow} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

