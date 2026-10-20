import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import FormActions from "@/Components/Form/FormActions";

export default function Form({ data, setData, handleSubmit, processing, errors, backUrl }) {
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
            <FormActions processing={processing} backUrl={backUrl} />
        </form>
    );
}
