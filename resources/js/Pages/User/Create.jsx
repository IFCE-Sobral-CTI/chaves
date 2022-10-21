import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Select from "@/Components/Form/Select";

function Create({ permissions }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        status: "0",
        registry: "",
        permission_id: "",
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('users.store'), {data});
    };

    return (
        <>
            <Head title="Novo Usuário" />
            <AuthenticatedLayout titleChildren={'Cadastro de Novo Usuário'} breadcrumbs={[{ label: 'Usuários', url: route('users.index') }, { label: 'Novo', url: route('users.create') }]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="mb-4">
                            <label htmlFor="status" className="font-light">Status</label>
                            <Select value={data.status} name={'status'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="permission_id" className="font-light">Permissão de acesso</label>
                            <Select value={data.permission_id} name={'permission_id'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula">
                                {permissions.map((item, index) => {
                                    return (
                                        <option value={item.id} key={index}>{item.description}</option>
                                    );
                                })}
                            </Select>
                            <InputError message={errors.permission_id} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="name" className="font-light">Nome</label>
                            <Input value={data.name} name={'name'} handleChange={onHandleChange} required={true} placeholder="Digite o nome completo" />
                            <InputError message={errors.name} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="email" className="font-light">E-mail</label>
                            <Input type={'email'} value={data.email} name={'email'} handleChange={onHandleChange} required={true} placeholder="Digite um e-mail válido" />
                            <InputError message={errors.email} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="registry" className="font-light">Matricula</label>
                            <Input type={'number'} value={data.registry} name={'registry'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula" />
                            <InputError message={errors.registry} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="password" className="font-light">Senha</label>
                            <Input type={'password'} value={data.password} name={'password'} handleChange={onHandleChange} required={true} placeholder="Digite a senha" />
                            <InputError message={errors.password} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="password_confirmation" className="font-light">Confirmação de senha</label>
                            <Input type={'password'} value={data.password_confirmation} name={'password_confirmation'} handleChange={onHandleChange} required={true} placeholder="Digite a confirmação de senha" />
                            <InputError message={errors.password_confirmation} />
                        </div>
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                </svg>
                                <span>Enviar</span>
                            </Button>
                            <Button href={route('users.index')} className={'gap-2'}>
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

export default Create;

