import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";

export default function DateRangeFields({ data, onChange, errors = {} }) {
    return (
        <>
            <div className="flex-1">
                <label htmlFor="start" className="text-xs font-light">Início</label>
                <Input value={data.start} type="date" name="start" handleChange={onChange} />
                <InputError message={errors.start} />
            </div>
            <div className="flex-1">
                <label htmlFor="end" className="text-xs font-light">Final</label>
                <Input value={data.end} type="date" name="end" handleChange={onChange} />
                <InputError message={errors.end} />
            </div>
        </>
    );
}
