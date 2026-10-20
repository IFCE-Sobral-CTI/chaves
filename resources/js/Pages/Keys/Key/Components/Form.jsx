import React from "react";
import Input from "@/Components/Form/Input";
import Textarea from "@/Components/Form/Textarea";
import InputError from "@/Components/InputError";
import LocalSelect from "@/Components/Form/LocalSelect";
import FormActions from "@/Components/Form/FormActions";

function Form({ data, setData, processing, errors, rooms, handleSubmit, backUrl }) {
    const onHandleChange = (event) => setData(event.target.name, event.target.value);

    return (
        <form onSubmit={handleSubmit} autoComplete="off">
            <LocalSelect
                id="room_id"
                label="Sala"
                emptyName="Nenhuma sala selecionada"
                data={rooms}
                value={data.room_id}
                onChange={(id) => setData("room_id", id)}
                error={errors.room_id}
            />
            <div className="mb-4">
                <label htmlFor="number" className="font-light">Número</label>
                <Input value={data.number} type="number" name="number" handleChange={onHandleChange} required placeholder="Digite o número da chave" />
                <InputError message={errors.number} />
            </div>
            <div className="mb-4">
                <label htmlFor="description" className="font-light">Descrição</label>
                <Input value={data.description} name="description" handleChange={onHandleChange} placeholder="Digite a descrição da chave" />
                <InputError message={errors.description} />
            </div>
            <div className="mb-4">
                <label htmlFor="observation" className="font-light">Observações</label>
                <Textarea value={data.observation} name="observation" handleChange={onHandleChange} placeholder="Observações sobre a chave" />
                <InputError message={errors.observation} />
            </div>
            <FormActions processing={processing} backUrl={backUrl} />
        </form>
    );
}

export default Form;
