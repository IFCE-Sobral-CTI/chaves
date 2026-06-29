import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Legend } from 'recharts';

const COLORS = ['#0ea5e9', '#f59e0b', '#10b981', '#6366f1'];

export default function EmployeeTypePie({ data }) {
    if (!data || data.length === 0 || data.every(d => d.value === 0)) {
        return (
            <div className="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                Sem dados disponíveis
            </div>
        );
    }

    const filtered = data.filter(d => d.value > 0);

    return (
        <PieChart>
            <Pie
                data={filtered}
                cx="50%"
                cy="50%"
                innerRadius="60%"
                outerRadius="80%"
                paddingAngle={3}
                dataKey="value"
                nameKey="label"
            >
                {filtered.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
            </Pie>
            <Tooltip formatter={(value, name) => [value, name]} />
            <Legend verticalAlign="bottom" height={36} />
        </PieChart>
    );
}
