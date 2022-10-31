import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Textarea from "@/Components/Form/Textarea";
import Select from "@/Components/Form/Select";
import Search from "./Components/Search";

function Create({ blocks, employees }) {
    const { data, setData, post, processing, errors } = useForm({
        description: "",
        observation: "",
        block_id: "",
        employees: []
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('rooms.store'), {data});
    };

    const onChangeEmployees = (value) => {
        setData("employees", value);
    }

    return (
        <>
            <Head title="Nova Sala" />
            <AuthenticatedLayout titleChildren={'Cadastro de nova Sala'} breadcrumbs={[{ label: 'Salas', url: route('rooms.index') }, { label: 'Nova', url: route('rooms.create') }]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="mb-4">
                            <label htmlFor="block_id" className="font-light">Sala</label>
                            <Select value={data.block_id} name={'block_id'} handleChange={onHandleChange} required={true}>
                                <option>Selecione um bloco</option>
                                {blocks.map((block, index) => {
                                    return (
                                        <option value={block.id} key={index}>{block.description}</option>
                                    );
                                })}
                            </Select>
                            <InputError message={errors.room_id} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="description" className="font-light">Descrição</label>
                            <Input value={data.description} name={'description'} handleChange={onHandleChange} required={true} placeholder="Digite a descrição da página" />
                            <InputError message={errors.description} />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="observation" className="font-light">Observações</label>
                            <Textarea value={data.observation} name={'observation'} handleChange={onHandleChange} required={false} placeholder="Digite a descrição da página" />
                            <InputError message={errors.observation} />
                        </div>
                        <Search data={employees} onChange={onChangeEmployees} />
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                </svg>
                                <span>Enviar</span>
                            </Button>
                            <Button href={route('rooms.index')} className={'gap-2'}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path fillroom="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
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

