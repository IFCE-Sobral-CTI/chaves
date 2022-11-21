import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import FormCreate from "@/Pages/Keys/Borrow/Components/FormCreate";

function Create({ employees, keys }) {
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
            <AuthenticatedLayout titleChildren={'Cadastro de nova Empréstimo'} breadcrumbs={[{ label: 'Empréstimos', url: route('borrows.index') }, { label: 'Novo', url: route('borrows.create') }]}>
                <Panel>
                    <FormCreate data={data} employees={employees} keys={keys} setData={setData} errors={errors} processing={processing} handleSubmit={handleSubmit} />
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default Create;

