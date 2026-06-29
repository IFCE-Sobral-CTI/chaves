import React from 'react';

export default function ExpiringList({ employees }) {
    if (!employees || employees.length === 0) {
        return (
            <div className="bg-white shadow-md rounded-lg p-4">
                <h2 className="text-sm font-semibold text-gray-700 mb-2">Permissões vencendo</h2>
                <p className="text-gray-500 text-sm">Nenhuma permissão vencendo nos próximos 30 dias.</p>
            </div>
        );
    }

    return (
        <div className="bg-white shadow-md rounded-lg p-4">
            <h2 className="text-sm font-semibold text-gray-700 mb-2">Permissões vencendo</h2>
            <ul className="space-y-2">
                {employees.map((employee) => (
                    <li key={employee.id} className="flex items-center justify-between text-sm border-b border-gray-100 pb-2 last:border-0">
                        <div>
                            <div className="font-medium text-gray-800">{employee.name}</div>
                            <div className="text-xs text-gray-500">{employee.type}</div>
                        </div>
                        <div className="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">
                            {employee.valid_until}
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}
