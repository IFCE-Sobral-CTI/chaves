const situationClasses = {
    aberto: 'bg-yellow-500',
    atrasado: 'bg-red-500',
    devolvido: 'bg-green-500',
};

const situationLabels = {
    aberto: 'Aberto',
    atrasado: 'Atrasado',
    devolvido: 'Devolvido',
};

export default function BorrowSituationBadge({ situation, className = '' }) {
    return (
        <span className={`inline-block px-2 py-0.5 text-xs text-white rounded ${situationClasses[situation] ?? 'bg-neutral-400'} ${className}`}>
            {situationLabels[situation] ?? situation ?? '—'}
        </span>
    );
}
