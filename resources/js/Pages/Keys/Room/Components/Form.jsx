import React from "react";
import Input from "@/Components/Form/Input";
import Textarea from "@/Components/Form/Textarea";
import InputError from "@/Components/InputError";
import Search from "./Search";
import LocalSelect from "@/Components/Form/LocalSelect";
import FormActions from "@/Components/Form/FormActions";

function Form({ data, setData, processing, errors, blocks, employeeOptions, handleSubmit, backUrl, selectedEmployees }) {
    const onHandleChange = (event) => setData(event.target.name, event.target.value);

    return (
        <form onSubmit={handleSubmit} autoComplete="off">
            <LocalSelect
                id="block_id"
                label="Bloco"
                emptyName="Nenhum bloco selecionado"
                data={blocks}
                value={data.block_id}
                onChange={(id) => setData("block_id", id)}
                error={errors.block_id}
            />
            <div className="mb-4">
                <label htmlFor="description" className="font-light">Descrição</label>
                <Input value={data.description} name="description" handleChange={onHandleChange} required placeholder="Digite a descrição da página" />
                <InputError message={errors.description} />
            </div>
            <div className="mb-4">
                <label htmlFor="observation" className="font-light">Observações</label>
                <Textarea value={data.observation} name="observation" handleChange={onHandleChange} placeholder="Digite a descrição da página" />
                <InputError message={errors.observation} />
            </div>
            <Search
                endpoint={employeeOptions}
                values={data.employees}
                selectedEmployees={selectedEmployees}
                onChange={(employees) => setData("employees", employees)}
            />
            <FormActions processing={processing} backUrl={backUrl} />
        </form>
    );
}

export default Form;
