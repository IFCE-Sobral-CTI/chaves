import React from 'react';
import SelectEmployee from "@/Pages/Keys/Borrow/Components/SelectEmployee";
import Textarea from "@/Components/Form/Textarea";
import InputError from "@/Components/InputError";
import Input from "@/Components/Form/Input";
import Search from "@/Pages/Keys/Borrow/Components/Search";
import Button from "@/Components/Form/Button";

export default function FormEdit({ data, employees, keys, setData, handleSubmit, errors, processing, borrow}) {
    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const onChangeKeys = (value) => {
        setData("keys", value);
    }

    return (
        <form onSubmit={handleSubmit} autoComplete="off">
            <SelectEmployee value={data.employee_id} data={employees} onChange={(id) => setData('employee_id', id)}  error={errors.employee_id} />
            <div className="mb-4">
                <label htmlFor="observation" className="font-light">Observações</label>
                <Textarea value={data.observation} name={'observation'} handleChange={onHandleChange} required={false} placeholder="Observações sobre o empréstimo" />
                <InputError message={errors.observation} />
            </div>
            <div className="mb-4">
                <label htmlFor="devolution" className="font-light">Devolução</label>
                <Input value={data.devolution} type={'datetime-local'} name={'devolution'} handleChange={onHandleChange} required={false} />
                <InputError message={errors.devolution} />
            </div>
            <div className="mb-4">
                <label htmlFor="returned_by" className="font-light">Devolvido por</label>
                <Input value={data.returned_by} name={'returned_by'} handleChange={onHandleChange} required={false} placeholder={'Quem devolveu a chave?'} />
                <InputError message={errors.returned_by} />
            </div>
            <Search data={keys} onChange={onChangeKeys} values={data.keys} />
            <div className="flex items-center justify-center gap-4 mt-6">
                <Button type={'submit'} processing={processing} color={'green'} className={"gap-2"}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                    </svg>
                    <span>Enviar</span>
                </Button>
                <Button href={route('borrows.show', borrow.id)} className={'gap-2'}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path fillRule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
                    </svg>
                    <span>Voltar</span>
                </Button>
            </div>
        </form>
    )
}
