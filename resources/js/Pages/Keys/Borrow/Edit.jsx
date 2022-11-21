import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Textarea from "@/Components/Form/Textarea";
import Select from "@/Components/Form/Select";
import Search from "./Components/Search";
import Input from "@/Components/Form/Input";
import moment from "moment";
import SelectEmployee from "./Components/SelectEmployee";
import FormEdit from "@/Pages/Keys/Borrow/Components/FormEdit";

function Edit({ borrow, employees, keys, borrowKeys }) {
    const { data, setData, put, processing, errors } = useForm({
        observation: borrow.observation,
        employee_id: borrow.employee_id,
        devolution: borrow.devolution? moment(borrow.devolution, 'DD/MM/YYYY hh:mm:ss').format('yyyy-MM-DDThh:mm'): null,
        keys: borrowKeys,
        returned_by: borrow.returned_by,
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
                    <FormEdit data={data} employees={employees} keys={keys} setData={setData} errors={errors} processing={processing} handleSubmit={handleSubmit} borrow={borrow} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Edit;

