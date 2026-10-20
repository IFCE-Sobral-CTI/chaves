import InputError from "@/Components/InputError";
import React, { useCallback, useEffect, useRef, useState } from "react";

function SelectMulti({ data, onChange, value, error, label, name, descriptionId }) {
    const [toggle, setToggle] = useState(false);
    const [term, setTerm] = useState('');
    const [list, setList] = useState([]);
    const [selected, setSelected] = useState([]);
    const ref = useRef(null);

    const inputRef = useCallback((inputElement) => {
        if (inputElement && toggle) {
            inputElement.focus();
        }
    }, [toggle]);

    useEffect(() => {
        if (value) {
            let itemSelected = data.filter(item => value.includes(item.id));
            setSelected(itemSelected);
        }
    }, []);

    useEffect(() => {
        onChange({
            target: {
                name,
                value: selected.map(item => item.id)
            }
        });
    }, [selected]);

    useEffect(() => {
        const debounce = setTimeout(() => {
            setList(data.filter((item) => {
                return item.name.toLowerCase().includes(term.toLowerCase());
            }));
        }, 300);

        return () => clearTimeout(debounce);
    }, [term, data]);

    useEffect(() => {
        document.addEventListener('click', handleClickOutside, true);
        return () => document.removeEventListener('click', handleClickOutside, true);
    }, []);

    const handleSelect = (event) => {
        let value = list.filter(item => String(item.id) === event.target.value);
        setSelected([...selected, value.pop()]);
    }

    const handleUnselect = (event, value) => {
        event.stopPropagation();
        setSelected(selected => selected.filter(item => item.id !== value));
        toggleHandle();
    }

    const availableItems = list.filter(item => !selected.some(selectedItem => selectedItem.id === item.id));
    const items = availableItems.map(item => (
        <li key={item.id}>
            <button type="button" className="w-full rounded-lg px-2 py-1 text-left font-light transition hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-emerald-600" onClick={handleSelect} value={item.id}>{item.name}</button>
        </li>
    ));

    const toggleHandle = () => {
        setToggle(open => !open);
    };

    const handleClickOutside = (event) => {
        if (ref.current && !ref.current.contains(event.target)) {
            setToggle(false);
        }
        setTerm('');
    }

    const inputId = name ? `select-multi-${name}` : undefined;
    const errorId = inputId ? `${inputId}-error` : undefined;
    const describedBy = [descriptionId, error ? errorId : null].filter(Boolean).join(' ');

    return (
        <div className="relative mb-4 w-full" ref={ref}>
            <span className="font-light">{label}</span>
            <div className="flex min-h-11 items-center gap-2 rounded-lg border border-neutral-400 p-2" onClick={() => setToggle(true)}>
                <div className="flex flex-1 gap-2">
                    {selected.length > 0
                    ?selected.map((item) => {
                        return (
                            <div className="inline-flex items-center justify-between gap-2 text-sm font-light rounded-md text-neutral-700 bg-neutral-200" key={item.id}>
                                <span className="pl-2">{item.name}</span>
                                <button type="button" onClick={(event) => handleUnselect(event, item.id)} aria-label={`Remover ${item.name}`} className="rounded-r-md border-l border-neutral-400 px-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-4 h-4" viewBox="0 0 16 16">
                                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                            </div>
                        );
                    })
                    :<span className="text-neutral-500">Nenhuma chave autorizada selecionada</span>}
                </div>
                <button type="button" onClick={(event) => { event.stopPropagation(); toggleHandle(); }} aria-expanded={toggle} aria-controls={`${inputId}-options`} aria-describedby={describedBy} className="flex shrink-0 items-center justify-center rounded p-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600" aria-label={toggle ? 'Fechar opções de chaves autorizadas' : `Adicionar ${label.toLowerCase()}`}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path fillRule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
            </div>
            <InputError message={error} id={errorId} />
            <div className={'absolute z-50 w-full transition ' + (toggle? 'block': 'hidden')} onKeyDown={(event) => {
                if (event.key === 'Escape') {
                    setToggle(false);
                    setTerm('');
                }
            }}>
                <div className="">
                    <input id={inputId} type="text" value={term} ref={inputRef} onChange={e => setTerm(e.target.value)} className="mt-0.5 w-full rounded-t-lg border-neutral-400 focus:border-neutral-500 focus:ring-0" placeholder="Digite para pesquisar chaves" aria-label={`Pesquisar ${label.toLowerCase()}`} aria-invalid={Boolean(error)} aria-describedby={describedBy} />
                </div>
                <div id={`${inputId}-options`} className="max-h-32 overflow-auto rounded-b-lg border-x border-b border-neutral-400 bg-white p-1 shadow-md scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-ful">
                    <ul>
                        {items}
                        {availableItems.length === 0 && <li className="px-2 py-1 text-sm text-neutral-500">Nenhuma chave encontrada.</li>}
                    </ul>
                </div>
            </div>
        </div>
    );
}

export default SelectMulti;
