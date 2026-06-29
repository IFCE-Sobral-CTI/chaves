import React from 'react';
import { Link } from '@inertiajs/react';

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

export default function BorrowsTable({ title, rows, canView, emptyMessage }) {
    if (!rows || rows.length === 0) {
        return (
            <div className="bg-white shadow-md rounded-lg p-4">
                <h2 className="text-sm font-semibold text-gray-700 mb-2">{title}</h2>
                <p className="text-gray-500 text-sm">{emptyMessage}</p>
            </div>
        );
    }

    const fallbackUrl = route('borrows.index');

    return (
        <div className="bg-white shadow-md rounded-lg p-4 overflow-x-auto">
            <h2 className="text-sm font-semibold text-gray-700 mb-2">{title}</h2>
            <table className="w-full table-auto text-sm">
                <thead>
                    <tr className="border-b border-gray-200 text-left">
                        <th className="pb-2 pr-2 font-semibold">Data</th>
                        <th className="pb-2 pr-2 font-semibold">Mutuário</th>
                        <th className="pb-2 pr-2 font-semibold">Chaves</th>
                        <th className="pb-2 pr-2 font-semibold">Situação</th>
                        <th className="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((item) => {
                        const href = canView ? route('borrows.show', item.id) : fallbackUrl;
                        return (
                            <tr key={item.id} className="border-t border-gray-100 hover:bg-neutral-50">
                                <td className="py-2 pr-2">
                                    <Link href={href} className="text-gray-700">{item.created_at}</Link>
                                </td>
                                <td className="py-2 pr-2">
                                    <Link href={href} className="text-gray-700">{item.employee?.name}</Link>
                                </td>
                                <td className="py-2 pr-2 text-gray-600">{item.keys_count}</td>
                                <td className="py-2 pr-2">
                                    <span className={`inline-block px-2 py-0.5 text-xs text-white rounded ${situationClasses[item.situation] || 'bg-gray-400'}`}>
                                        {situationLabels[item.situation] || item.situation}
                                    </span>
                                </td>
                                <td className="py-2 text-right">
                                    <Link href={href} className="text-neutral-400 hover:text-neutral-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5 inline" viewBox="0 0 16 16">
                                            <path fillRule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
