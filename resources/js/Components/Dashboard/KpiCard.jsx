import React from 'react';

const variants = {
    emerald: 'bg-emerald-500 hover:bg-emerald-600',
    indigo: 'bg-indigo-400 hover:bg-indigo-500',
    teal: 'bg-teal-500 hover:bg-teal-600',
    amber: 'bg-amber-400 hover:bg-amber-500',
    sky: 'bg-sky-500 hover:bg-sky-600',
    red: 'bg-red-500 hover:bg-red-600',
};

export default function KpiCard({ title, value, icon: Icon, variant = 'emerald' }) {
    return (
        <div className={`flex items-center gap-4 p-4 text-white transition rounded-lg shadow-md ${variants[variant] || variants.emerald}`}>
            {Icon && (
                <div className="shrink-0 ml-2">
                    <Icon className="w-12 h-12 opacity-90" />
                </div>
            )}
            <div className="flex-1 flex flex-col justify-center items-center min-w-0">
                <div className="text-xs font-bold tracking-widest uppercase">{title}</div>
                <div className="text-4xl font-bold mt-1">{value}</div>
            </div>
        </div>
    );
}
