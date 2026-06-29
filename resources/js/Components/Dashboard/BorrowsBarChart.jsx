import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

export default function BorrowsBarChart({ data }) {
    if (!data || data.length === 0) return null;

    return (
        <BarChart data={data} margin={{ top: 5, right: 5, left: -20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" vertical={false} />
            <XAxis dataKey="label" tick={{ fontSize: 12 }} />
            <YAxis tick={{ fontSize: 12 }} allowDecimals={false} />
            <Tooltip formatter={(value) => [value, 'Empréstimos']} />
            <Bar dataKey="value" fill="#0ea5e9" radius={[4, 4, 0, 0]} />
        </BarChart>
    );
}
