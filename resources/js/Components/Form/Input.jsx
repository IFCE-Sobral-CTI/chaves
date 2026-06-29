import React, { useEffect, useRef, useState } from "react";

function Input({
    type = 'text',
    name,
    value,
    className = '',
    isFocused,
    handleChange,
    required,
    autoComplete,
    placeholder = '',
}) {

    const input = useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <>
            <input
                type={type}
                name={name}
                id={name}
                value={value?? ''}
                className={
                    `w-full border-neutral-400 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200/50 rounded-lg shadow-sm ` +
                    className
                }
                ref={input}
                onChange={(e) => handleChange(e)}
                required={required}
                autoComplete={autoComplete}
                placeholder={placeholder}
            />
        </>
    )
}

export default Input;
