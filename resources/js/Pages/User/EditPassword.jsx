import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";

function EditPassword({ user }) {
    const { data, setData, put, processing, errors } = useForm({
        password: "",
        confirm_password: "",
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('users.update.password', user.id), {data});
    };

    return (
        <>
            <Head title="Novo Usuário" />
            <AuthenticatedLayout titleChildren={'Mudar senha do Usuário'} breadcrumbs={[{ label: 'Usuários', url: route('users.index') }, { label: 'Novo', url: route('users.edit.password', user.id) }]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="mb-4">
                            <label htmlFor="password" className="font-light">Senha</label>
                            <Input type={'password'} value={data.password} name={'password'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula" />
                            <InputError message={errors.password} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="confirm_password" className="font-light">Confirmação de senha</label>
                            <Input type={'password'} value={data.confirm_password} name={'confirm_password'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula" />
                            <InputError message={errors.confirm_password} />
                        </div>
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                </svg>
                                <span>Enviar</span>
                            </Button>
                            <Button href={route('users.show', user.id)} className={'gap-2'}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                    <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                                </svg>
                                <span>Voltar</span>
                            </Button>
                        </div>
                    </form>
                </Panel>
            </AuthenticatedLayout>
        </>
    )
}

export default EditPassword;

