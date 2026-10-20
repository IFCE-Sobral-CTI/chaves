import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import FormActions from "@/Components/Form/FormActions";
import Select from "@/Components/Form/Select";

export default function Form({ data, setData, handleSubmit, processing, errors, backUrl, groups }) {
    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    return (
        <form onSubmit={handleSubmit} autoComplete="off">
            <div className="mb-4">
                <label htmlFor="description" className="font-light">Descrição</label>
                <Input value={data.description} name={'description'} handleChange={onHandleChange} required={true} placeholder="Digite a descrição da regra" />
                <InputError message={errors.description} />
            </div>
            <div className="mb-4">
                <label htmlFor="group_id" className="font-light">Grupo de páginas</label>
                <Select value={data.group_id} name={'group_id'} handleChange={onHandleChange} required={true}>
                    {groups.map((item, i) => {
                        return (
                            <option value={item.id} key={item.id}>{item.description}</option>
                        );
                    })}
                </Select>
                <InputError message={errors.group_id} />
            </div>
            <div className="mb-4">
                <label htmlFor="control" className="font-light">Controle</label>
                <Input value={data.control} name={'control'} handleChange={onHandleChange} required={true} placeholder="Digite um controle para a regra" />
                <InputError message={errors.control} />
            </div>
            <FormActions processing={processing} backUrl={backUrl} />
        </form>
    );
}
