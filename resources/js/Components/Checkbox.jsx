import React from 'react';

export default function Checkbox({ name, value, handleChange }) {
    return (
        <input
            type="checkbox"
            name={name}
            value={value}
            className="rounded border-neutral-400 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring-2 focus:ring-indigo-200/50"
            onChange={(e) => handleChange(e)}
        />
    );
}
