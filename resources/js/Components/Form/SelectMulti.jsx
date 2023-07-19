import InputError from "@/Components/InputError";
import React, { useCallback, useEffect, useRef, useState } from "react";

function SelectMulti({ data, onChange, value, error, label, name }) {
    const [toggle, setToggle] = useState(false);
    const [term, setTerm] = useState('');
    const [list, setList] = useState([]);
    const [selected, setSelected] = useState([]);
    const ref = useRef(null);

    const inputRef = useCallback((inputElement) => {
        if (inputElement) {
            inputElement.focus();
        }
    });

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

        const debounce = setTimeout(() => {
            setList(data.filter((item) => {
                return item.name.toLowerCase().includes(term.toLowerCase());
            }, term));
        }, 300);

        document.addEventListener('click', handleClickOutside, true);

        return () => {
            clearTimeout(debounce)
            document.removeEventListener('click', handleClickOutside, true);
        };
    }, [term, selected]);

    const handleSelect = (event) => {
        let value = list.filter(item => item.id === event.target.value);
        setSelected([...selected, value.pop()]);
    }

    const handleUnselect = (value) => {
        setSelected(selected => selected.filter(item => item.id !== value));
        toggleHandle();
    }

    const items = list.map((item, index) => {
        let sel = selected.map(item => item.id);

        if (!sel.includes(item.id))
            return (
                <li className="px-2 py-1 font-light transition rounded-lg cursor-pointer hover:bg-neutral-100" key={index} onClick={handleSelect} value={item.id}>{item.name}</li>
            );
    });

    const toggleHandle = () => {
        setToggle(!toggle);
    };

    const handleClickOutside = (event) => {
        if (ref.current && !ref.current.contains(event.target)) {
            setToggle(false);
        }
        setTerm('');
    }

    return (
        <div className="relative w-full mb-4">
            <label className="font-light">{label}</label>
            <div className="flex p-2 border rounded-lg border-neutral-400" onClick={toggleHandle}>
                <div className="flex flex-1 gap-2">
                    {selected.length > 0
                    ?selected.map((item, i) => {
                        return (
                            <div className="inline-flex items-center justify-between gap-2 text-sm font-light rounded-md text-neutral-700 bg-neutral-200" key={i}>
                                <span className="pl-2">{item.name}</span>
                                <span onClick={() => handleUnselect(item.id)} className="pr-1 border-l border-neutral-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-4 h-4" viewBox="0 0 16 16">
                                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </span>
                            </div>
                        );
                    })
                    :<span className="text-neutral-500">Nenhum {label.toLowerCase()} selecionado</span>}
                </div>
                <div className="flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="w-5 h-5" viewBox="0 0 16 16">
                        <path fillRule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
            <InputError message={error} />
            <div className={'absolute z-50 w-full transition ' + (toggle? 'block': 'hidden')} ref={ref}>
                <div className="">
                    <input type="text" value={term} ref={inputRef} onChange={e => setTerm(e.target.value)} className="w-full rounded-t-lg mt-0.5 border-neutral-400 focus:ring-0 focus:border-neutral-500" placeholder="Digite sua pesquisa" />
                </div>
                <div className="p-1 overflow-auto bg-white border-b border-l border-r rounded-b-lg shadow-md max-h-32 border-neutral-400 scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-ful">
                    <ul>
                        {items}
                    </ul>
                </div>
            </div>
        </div>
    );
}

export default SelectMulti;
