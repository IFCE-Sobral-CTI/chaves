import Button from "@/Components/Form/Button";
import DateRangeFields from "./DateRangeFields";

export default function PeriodFilterForm({ data, onChange, onSubmit, processing, errors, filters, clearUrl }) {
    return (
        <form onSubmit={onSubmit} className="flex items-end gap-4">
            <DateRangeFields data={data} onChange={onChange} errors={errors} />
            <div className="flex items-end gap-2">
                {(filters.start || filters.end) && (
                    <Button href={clearUrl} className="gap-2 py-2.5" processing={processing} color="red">Limpar</Button>
                )}
                <Button type="submit" className="gap-2 py-2.5" processing={processing}>Buscar</Button>
            </div>
        </form>
    );
}
