import React, { useEffect, useState } from "react";
import Input from "@/Components/Form/Input";

export default function Search({ data, onChange, values = [] }) {
    const [list, setList] = useState(data);
    const [term, setTerm] = useState('');
    const [add, setAdd] = useState(values);

    useEffect(() => {
        onChange(add);
        const debounce = setTimeout(() => {
            setList(data.filter((item) => {
                return item.number.toString().includes(term) || item.room.description.toLowerCase().includes(term.toLowerCase());
            }, term));
        }, 300);

        return () => clearTimeout(debounce);
    }, [add, term]);

    const onHandleChange = (event) => {
        setTerm(event.target.value);
    };

    const toggleItemHandler = (id) => {
        if (add.includes(id))
            setAdd(add.filter((item) => item !== id));
        else
            setAdd([id, ...add]);
    };

    const items = list.map((item, i) => {
        if (!add.includes(item.id))
            return (
                <tr className="border-t cursor-pointer" onClick={() => toggleItemHandler(item.id)} key={i}>
                    <td className="p-2 w-1/6">{item.number}</td>
                    <td className="p-2 w-4/6">{item.room.description}</td>
                    <td className="p-2 w-1/6">
                        <div className="flex justify-end">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                            </svg>
                        </div>
                    </td>
                </tr>
            )
    });

    const itemsAdd = data.map((item, i) => {
        if (add.includes(item.id))
            return (
                <tr className="border-t cursor-pointer" onClick={() => toggleItemHandler(item.id)} key={i}>
                    <td className="p-2 w-1/6">{item.number}</td>
                    <td className="p-2 w-4/6">{item.room.description}</td>
                    <td className="p-2 w-1/6">
                        <div className="flex justify-end">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="h-5 w-5" viewBox="0 0 16 16">
                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                            </svg>
                        </div>
                    </td>
                </tr>
            )
    });

    return (
        <div className="flex flex-col gap-0">
            <span className="font-light">Chaves</span>
            <div className="flex gap-4">
                <div className="flex-1 flex flex-col gap-2">
                    <Input
                        value={term}
                        type={'search'}
                        handleChange={onHandleChange}
                        placeholder={'Pesquise sua chave'}
                    />
                    <div className="max-h-96 border border-neutral-400 rounded-lg p-2 overflow-auto scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table table-auto w-full">
                            <thead>
                                <tr className="">
                                    <th className="font-normal text-left px-1 pt-2">Número</th>
                                    <th className="font-normal text-left px-1 pt-2">Sala</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody className="font-light">
                                {items}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div className="flex-1 border border-neutral-400 rounded-lg">
                    <div className="font-light border-b border-neutral-400 mt-2 mx-2">Item selecionados</div>
                    <div className="max-h-96 ml-2 overflow-auto scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table table-auto w-full">
                            <thead className="">
                                <tr className="">
                                    <th className="font-normal text-left px-1 pt-2">Número</th>
                                    <th className="font-normal text-left px-1 pt-2">Sala</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody className="font-light">
                                {itemsAdd}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    )
}
