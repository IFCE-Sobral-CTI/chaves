import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/inertia-react";
import Panel from "@/Components/Dashboard/Panel";
import InputError from "@/Components/InputError";
import Button from "@/Components/Form/Button";

function Rules({ permission, groups }) {
    const { data, setData, put, processing, errors } = useForm({
        rules: permission.rules.map(item => item.id),
    });

    const onHandleChange = (event) => {
        const value = parseInt(event.target.value);
        let [...list] = data.rules;

        if (data.rules.includes(value)) {
            list.splice(list.indexOf(value), 1);
            setData('rules', [...list]);
        }
        else {
            list.push(value);
            setData('rules', list);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('permissions.rules.sync', permission.id), {data});
    };

    const items = groups.map((group, i) => {
        return (
            <div className="p-2 border rounded-lg" key={'g' + i}>
                <p className="mb-2 font-semibold">{group.description}</p>
                {group.rules.map((rule, j) => {
                    return (
                        <div className="mb-2" key={'r' + j}>
                            <div className="flex gap-2">
                                <input type="checkbox" value={rule.id} id={rule.id} onChange={onHandleChange} defaultChecked={data.rules.includes(rule.id)} className="w-5 h-5 bg-gray-100 border rounded-md text-green focus:ring-green-light" />
                                <label className="text-sm" htmlFor={rule.id}>{rule.description}</label>
                                <InputError message={errors.rules} />
                            </div>
                        </div>
                    )
                })}
            </div>
        )
    })

    return (
        <>
            <Head title="Regas" />
            <AuthenticatedLayout titleChildren={'Adicionar regras a permissão'} breadcrumbs={[{ label: 'Permissões', url: route('permissions.index') }, { label: permission.description, url: route('permissions.show', permission.id) }, { label: 'Regras'}]}>
                <Panel>
                    <form onSubmit={handleSubmit} autoComplete="off">
                        <div className="grid grid-cols-3 gap-4">
                            {items}
                        </div>
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                </svg>
                                <span>Enviar</span>
                            </Button>
                            <Button href={route('permissions.show', permission.id)} className={'gap-2'}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                                    <path fillpermission="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
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

export default Rules;

