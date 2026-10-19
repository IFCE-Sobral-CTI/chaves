import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import FormCreate from "@/Pages/Keys/Borrow/Components/FormCreate";

function Create({ employees, keys, keysLastPage }) {
    const { data, setData, post, processing, errors } = useForm({
        observation: "",
        employee_id: "",
        keys: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('borrows.store'), {data});
    };

    return (
        <>
            <Head title="Novo Empréstimos" />
            <AuthenticatedLayout titleChildren={'Cadastro de novo Empréstimo'} breadcrumbs={[{ label: 'Empréstimos', url: route('borrows.index') }, { label: 'Novo', url: route('borrows.create') }]}>
                <Panel>
                    <FormCreate data={data} employees={employees} keys={keys} keysLastPage={keysLastPage} employeeSearch={route('borrows.options.employees.create')} keySearch={route('borrows.options.keys.create')} setData={setData} errors={errors} processing={processing} handleSubmit={handleSubmit} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

