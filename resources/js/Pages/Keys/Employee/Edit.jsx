import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import Panel from "@/Components/Dashboard/Panel";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";
import Textarea from "@/Components/Form/Textarea";
import moment from "moment";
import Select from "@/Components/Form/Select";
import SelectMulti from "@/Components/Form/SelectMulti";
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
        valid_until: employee.valid_until? moment(employee.valid_until, 'DD/MM/YYYY').format('yyyy-MM-DD'): null,
        type: employee.type,
        borrowable_key_ids: employee.borrowable_keys.map((key) => key.id),
    });

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.name === 'tel' ? formatPhone(event.target.value) : event.target.value);

        if (event.target.name === 'type' && ![3, 4].includes(Number(event.target.value))) {
            setData('borrowable_key_ids', []);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id), {data});
    };

    return (
        <>
            <Head title="Editar Mutuário" />
            <AuthenticatedLayout titleChildren={'Editar Mutuário'} breadcrumbs={[{ label: 'Mutuários', url: route('employees.index') }, { label: employee.description, url: route('employees.show', employee.id) }, { label: 'Editar'}]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label htmlFor="type" className="font-light">Vínculo do Mutuário</label>
                                <Select value={data.type} name={'type'} handleChange={onHandleChange} required={true}>
                                    <option>Selecione um vínculo</option>
                                    {employeeType.map((item, index) => (
                                        <option value={item.value} key={item.value}>{item.label}</option>
                                    ))}
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                            <div>
                                <label htmlFor="occupation" className="font-light">Função/ocupação</label>
                                <Input value={data.occupation} name={'occupation'} handleChange={onHandleChange} required={false} placeholder="Digite a função ou ocupação do mutuário" />
                                <InputError message={errors.occupation} />
                            </div>
                            <div>
                                <label htmlFor="registry" className="font-light">Matricula</label>
                                <Input value={data.registry} type={'number'} name={'registry'} handleChange={onHandleChange} required={true} placeholder="Digite a matricula" />
                                <InputError message={errors.registry} />
                            </div>
                            <div>
                                <label htmlFor="valid_until" className="font-light">Válido até</label>
                                <Input value={data.valid_until} type={'date'} name={'valid_until'} handleChange={onHandleChange} />
                                <InputError message={errors.valid_until} />
                            </div>
                        </div>
                        {[3, 4].includes(Number(data.type)) && (
                            <div className="mt-4">
                                <SelectMulti
                                    data={keys}
                                    value={data.borrowable_key_ids}
                                    onChange={onHandleChange}
                                    error={errors.borrowable_key_ids ?? errors['borrowable_key_ids.0']}
                                    label="Chaves autorizadas"
                                    name="borrowable_key_ids"
                                />
                            </div>
                        )}
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="font-light">Nome</label>
                                <Input value={data.name} name={'name'} handleChange={onHandleChange} required={true} placeholder="Digite o nome do mutuário" />
                                <InputError message={errors.name} />
                            </div>
                            <div>
                                <label htmlFor="usual_name" className="font-light">Nome usual</label>
                                <Input value={data.usual_name} name={'usual_name'} handleChange={onHandleChange} required={false} placeholder="Digite o nome usual do mutuário" />
                                <InputError message={errors.usual_name} />
                            </div>
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label htmlFor="email" className="font-light">E-mail</label>
                                <Input value={data.email} type={'email'} name={'email'} handleChange={onHandleChange} required={true} placeholder="Digite o e-mail do mutuário" />
                                <InputError message={errors.email} />
                            </div>
                            <div>
                                <label htmlFor="alternative_email" className="font-light">E-mail Alternativo</label>
                                <Input value={data.alternative_email} type={'email'} name={'alternative_email'} handleChange={onHandleChange} required={false} placeholder="Digite o e-mail alternativo do mutuário" />
                                <InputError message={errors.alternative_email} />
                            </div>
                            <div>
                                <label htmlFor="tel" className="font-light">Telefone</label>
                                <Input value={data.tel} type={'tel'} name={'tel'} handleChange={onHandleChange} required={false} placeholder="(88) 99999-8888" />
                                <InputError message={errors.tel} />
                            </div>
                        </div>
                        <div className="mt-4">
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
                            <Button href={route('employees.show', employee.id)} className={'gap-2'}>
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

export default Edit;

