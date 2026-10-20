import React from "react";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import Textarea from "@/Components/Form/Textarea";
import Select from "@/Components/Form/Select";
import SelectMulti from "@/Components/Form/SelectMulti";
import FormActions from "@/Components/Form/FormActions";
import { formatPhone } from "../phone";

export function handleEmployeeChange(setData, event) {
    setData(event.target.name, event.target.name === "tel" ? formatPhone(event.target.value) : event.target.value);
    if (event.target.name === "type" && ![3, 4].includes(Number(event.target.value))) {
        setData("borrowable_key_ids", []);
    }
}

export function getBorrowableKeyError(errors) {
    return errors.borrowable_key_ids ?? Object.entries(errors).find(([field]) => field.startsWith("borrowable_key_ids."))?.[1];
}

function Form({ data, setData, processing, errors, employeeType, keys, handleSubmit, backUrl }) {
    const borrowableKeysError = getBorrowableKeyError(errors);
    const onHandleChange = (event) => {
        handleEmployeeChange(setData, event);
    };

    return (
        <form onSubmit={handleSubmit} autoComplete="off">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label htmlFor="type" className="font-light">Vínculo do Mutuário</label>
                    <Select value={data.type} name="type" handleChange={onHandleChange} required>
                        <option value="">Selecione um vínculo</option>
                        {employeeType.map((item) => <option value={item.value} key={item.value}>{item.label}</option>)}
                    </Select>
                    <InputError message={errors.type} />
                </div>
                <div>
                    <label htmlFor="occupation" className="font-light">Função/ocupação</label>
                    <Input value={data.occupation} name="occupation" handleChange={onHandleChange} placeholder="Digite a função ou ocupação do mutuário" />
                    <InputError message={errors.occupation} />
                </div>
                <div>
                    <label htmlFor="registry" className="font-light">Matricula</label>
                    <Input value={data.registry} type="number" name="registry" handleChange={onHandleChange} required placeholder="Digite a matricula" />
                    <InputError message={errors.registry} />
                </div>
                <div>
                    <label htmlFor="valid_until" className="font-light">Válido até</label>
                    <Input value={data.valid_until} type="date" name="valid_until" handleChange={onHandleChange} />
                    <InputError message={errors.valid_until} />
                </div>
            </div>
            {[3, 4].includes(Number(data.type)) && (
                <div className="mt-4">
                    <p className="mb-2 text-sm text-neutral-600" id="borrowable-keys-help">
                        Discentes e externos precisam de validade vigente e de autorização para cada chave antes de fazer empréstimos. Sem chaves autorizadas, não poderão retirar chaves.
                    </p>
                    <SelectMulti
                        data={keys}
                        value={data.borrowable_key_ids}
                        onChange={onHandleChange}
                        error={borrowableKeysError}
                        label="Chaves autorizadas"
                        name="borrowable_key_ids"
                        descriptionId="borrowable-keys-help"
                    />
                </div>
            )}
            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label htmlFor="name" className="font-light">Nome</label>
                    <Input value={data.name} name="name" handleChange={onHandleChange} required placeholder="Digite o nome do mutuário" />
                    <InputError message={errors.name} />
                </div>
                <div>
                    <label htmlFor="usual_name" className="font-light">Nome usual</label>
                    <Input value={data.usual_name} name="usual_name" handleChange={onHandleChange} placeholder="Digite o nome usual do mutuário" />
                    <InputError message={errors.usual_name} />
                </div>
            </div>
            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label htmlFor="email" className="font-light">E-mail</label>
                    <Input value={data.email} type="email" name="email" handleChange={onHandleChange} required placeholder="Digite o e-mail do mutuário" />
                    <InputError message={errors.email} />
                </div>
                <div>
                    <label htmlFor="alternative_email" className="font-light">E-mail Alternativo</label>
                    <Input value={data.alternative_email} type="email" name="alternative_email" handleChange={onHandleChange} placeholder="Digite o e-mail alternativo do mutuário" />
                    <InputError message={errors.alternative_email} />
                </div>
                <div>
                    <label htmlFor="tel" className="font-light">Telefone</label>
                    <Input value={data.tel} type="tel" name="tel" handleChange={onHandleChange} placeholder="(88) 99999-8888" />
                    <InputError message={errors.tel} />
                </div>
            </div>
            <div className="mt-4">
                <label htmlFor="observation" className="font-light">Observações</label>
                <Textarea value={data.observation} name="observation" handleChange={onHandleChange} placeholder="Observações sobre o mutuário" />
                <InputError message={errors.observation} />
            </div>
            <FormActions processing={processing} backUrl={backUrl} />
        </form>
    );
}

export default Form;
