import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

export default function TopRoomsBarChart({ data }) {
    if (!data || data.length === 0) return null;

    return (
        <BarChart data={data} layout="vertical" margin={{ top: 5, right: 5, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" horizontal={false} />
            <XAxis type="number" tick={{ fontSize: 12 }} allowDecimals={false} />
            <YAxis dataKey="label" type="category" tick={{ fontSize: 12 }} width={100} />
            <Tooltip formatter={(value) => [value, 'Empréstimos']} />
            <Bar dataKey="value" fill="#6366f1" radius={[0, 4, 4, 0]} />
        </BarChart>
    );
}
