import React from "react";

const situationClasses = {
    returned: 'bg-emerald-500',
    open: 'bg-sky-500',
    overdue: 'bg-red-500',
};

export default function ReportSummary({ summary }) {
    if (!summary) return null;

    const items = [
        { label: 'Total', value: summary.total, variant: 'neutral' },
        { label: 'Devolvidos', value: summary.returned, variant: 'returned' },
        { label: 'Abertos', value: summary.open, variant: 'open' },
        { label: 'Atrasados', value: summary.overdue, variant: 'overdue' },
        { label: 'Chaves movimentadas', value: summary.keysMoved, variant: 'neutral' },
    ];

    return (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
            {items.map((item) => (
                <div key={item.label} className={`flex flex-col items-center justify-center p-3 text-white transition rounded-lg shadow-sm ${situationClasses[item.variant] ?? 'bg-neutral-500'}`}>
                    <span className="text-xs font-bold tracking-wider uppercase">{item.label}</span>
                    <span className="text-2xl font-bold">{item.value}</span>
                </div>
            ))}
        </div>
    );
}
