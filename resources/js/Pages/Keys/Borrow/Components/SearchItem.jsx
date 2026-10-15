import React from "react";

export default function SearchItem({ item, toggleItemHandler, icon }) {
    return (
        <tr className="border-t border-neutral-300 cursor-pointer" onClick={() => toggleItemHandler(item.id)}>
            <td className="w-1/6 p-2">{item.number}</td>
            <td className="w-4/6 p-2">{item.room.description}</td>
            <td className="w-1/6 p-2">
                <div className="flex justify-end">
                    <button type="button" className="rounded focus-visible:outline-2 focus-visible:outline-green" aria-label={`${icon === 'rem' ? 'Remover' : 'Adicionar'} chave ${item.number} - ${item.room.description}`}>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" className="w-5 h-5" fill="currentColor" aria-hidden="true">
                            {icon === 'rem'
                                ? <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.147 5.146a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                                : <path fillRule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/>}
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    )
}
