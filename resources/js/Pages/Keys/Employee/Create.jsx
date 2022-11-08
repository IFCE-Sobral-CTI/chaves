import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Textarea from "@/Components/Form/Textarea";
import Select from "@/Components/Form/Select";

function Create({ employeeType }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        registry: "",
        valid_until: "",
        observation: "",
        type: "",
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('employees.store'), {data});
    };

    return (
        <>
            <Head title="Novo Mutuário" />
            <AuthenticatedLayout titleChildren={'Cadastro de novo Mutuário'} breadcrumbs={[{ label: 'Mutuários', url: route('employees.index') }, { label: 'Novo', url: route('employees.create') }]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="mb-4">
                            <label htmlFor="type" className="font-light">Classe do Mutuário</label>
                            <Select value={data.type} name={'type'} handleChange={onHandleChange} required={true}>
                                <option>Selecione uma classe</option>
                                {employeeType.map((item, index) => {
                                    return (
                                        <option value={item.value} key={index}>{item.label}</option>
                                    );
                                })}
                            </Select>
                            <InputError message={errors.type} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="registry" className="font-light">Matricula</label>
                            <Input value={data.registry} type={'number'} name={'registry'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula" />
                            <InputError message={errors.registry} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="name" className="font-light">Nome</label>
                            <Input value={data.name} name={'name'} handleChange={onHandleChange} required={true} placeholder="Digite o nome do mutuário" />
                            <InputError message={errors.name} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="email" className="font-light">E-mail</label>
                            <Input value={data.email} type={'email'} name={'email'} handleChange={onHandleChange} required={true} placeholder="Digite o e-mail do mutuário" />
                            <InputError message={errors.email} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="valid_until" className="font-light">Válido até</label>
                            <Input value={data.valid_until} type={'date'} name={'valid_until'} handleChange={onHandleChange} />
                            <InputError message={errors.valid_until} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="observation" className="font-light">Observações</label>
                            <Textarea value={data.observation} name={'observation'} handleChange={onHandleChange} required={false} placeholder="Observações sobre o mutuário" />
                            <InputError message={errors.observation} />
                        </div>
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                </svg>
                                <span>Enviar</span>
                            </Button>
                            <Button href={route('employees.index')} className={'gap-2'}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
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

