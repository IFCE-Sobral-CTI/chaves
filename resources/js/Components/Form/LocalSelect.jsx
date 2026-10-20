import React, { useEffect, useRef, useState } from "react";
import InputError from "@/Components/InputError";

export function filterLocalSelectOptions(data, term) {
    return data.filter((item) => item.description.toLowerCase().includes(term.toLowerCase()));
}

function LocalSelect({ id, label, emptyName, data, value, error, onChange }) {
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState("");
    const wrapperRef = useRef(null);
    const triggerRef = useRef(null);
    const searchRef = useRef(null);
    const selected = data.find((item) => String(item.id) === String(value));
    const errorId = `${id}-error`;

    useEffect(() => {
        if (open) searchRef.current?.focus();
    }, [open]);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
                setOpen(false);
                setTerm("");
            }
        };
        document.addEventListener("click", handleClickOutside, true);
        return () => document.removeEventListener("click", handleClickOutside, true);
    }, []);

    const close = (returnFocus = false) => {
        setOpen(false);
        setTerm("");
        if (returnFocus) triggerRef.current?.focus();
    };

    return (
        <div className="relative mb-4 w-full" ref={wrapperRef}>
            <label htmlFor={`${id}-trigger`} className="font-light">{label}</label>
            <button
                id={`${id}-trigger`}
                type="button"
                ref={triggerRef}
                aria-expanded={open}
                aria-controls={`${id}-options`}
                aria-describedby={error ? errorId : undefined}
                onKeyDown={(event) => {
                    if (event.key === "Escape" && open) {
                        event.preventDefault();
                        close(true);
                    }
                }}
                onClick={() => {
                    if (open) {
                        close();
                    } else {
                        setOpen(true);
                    }
                }}
                className="flex w-full items-center rounded-lg border border-neutral-400 p-2 text-left focus-visible:outline-2 focus-visible:outline-emerald-600"
            >
                <span className={`flex-1 ${selected ? "text-neutral-700" : "text-neutral-500"}`}>
                    {selected?.description ?? emptyName}
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16" aria-hidden="true">
                    <path fillRule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                </svg>
            </button>
            <InputError message={error} id={errorId} />
            <div className={`absolute z-50 w-full ${open ? "block" : "hidden"}`} onKeyDown={(event) => {
                if (event.key === "Escape") {
                    event.preventDefault();
                    close(true);
                }
            }}>
                <input
                    id={id}
                    type="text"
                    value={term}
                    ref={searchRef}
                    onChange={(event) => setTerm(event.target.value)}
                    className="mt-0.5 w-full rounded-t-lg border-neutral-400 focus:border-neutral-500 focus:ring-0"
                    placeholder="Digite sua pesquisa"
                    aria-label={`Pesquisar ${label.toLowerCase()}`}
                />
                <div id={`${id}-options`} className="max-h-32 overflow-auto rounded-b-lg border-x border-b border-neutral-400 bg-white p-1 shadow-md scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-ful">
                    <ul>
                        {filterLocalSelectOptions(data, term).filter((item) => String(item.id) !== String(value)).map((item) => (
                            <li key={item.id}>
                                <button type="button" className="w-full rounded-lg px-2 py-1 text-left font-light transition hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-emerald-600" onClick={() => { onChange(item.id); close(true); }}>{item.description}</button>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
}

export default LocalSelect;
