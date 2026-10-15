import React, { useState } from "react";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import SearchItem from "@/Pages/Keys/Borrow/Components/SearchItem";

export default function Search({ data, onChange, values = [], error = '' }) {
    const [term, setTerm] = useState('');
    const list = data.filter(item => !values.includes(item.id) && (
        item.number.toString().includes(term) || item.room.description.toLowerCase().includes(term.toLowerCase())
    ));

    const onHandleChange = (event) => {
        setTerm(event.target.value);
    };

    const toggleItemHandler = (id) => {
        onChange(values.includes(id) ? values.filter(item => item !== id) : [id, ...values]);
    };

    const items = list.map(item => (
        <SearchItem item={item} toggleItemHandler={toggleItemHandler} key={item.id} icon="add" />
    ));

    const itemsAdd = data.filter(item => values.includes(item.id)).map(item => (
        <SearchItem item={item} toggleItemHandler={toggleItemHandler} key={item.id} icon="rem" />
    ));

    return (
        <div className="flex flex-col gap-0">
            <span className="font-light">Chaves</span>
            <div className="flex gap-4">
                <div className="flex flex-col flex-1 gap-2">
                    <label htmlFor="borrow-key-search" className="sr-only">Pesquisar chave por número ou sala</label>
                    <Input
                        name="borrow-key-search"
                        value={term}
                        type={'search'}
                        handleChange={onHandleChange}
                        placeholder={'Pesquise sua chave'}
                    />
                    <div className="p-2 overflow-auto border rounded-lg max-h-96 border-neutral-400 scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table w-full table-auto">
                            <thead>
                                <tr className="">
                                    <th className="px-1 pt-2 font-normal text-left">Número</th>
                                    <th className="px-1 pt-2 font-normal text-left">Sala</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody className="font-light">
                                {items}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div className="flex-1 border rounded-lg border-neutral-400">
                    <div className="mx-2 mt-2 font-light border-b border-neutral-400">Itens selecionados</div>
                    <div className="ml-2 overflow-auto max-h-96 scrollbar-thumb-gray-400 scrollbar-track-gray-100 scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
                        <table className="table w-full table-auto">
                            <thead className="">
                                <tr className="">
                                    <th className="px-1 pt-2 font-normal text-left">Número</th>
                                    <th className="px-1 pt-2 font-normal text-left">Sala</th>
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
            <InputError message={error} />
        </div>
    )
}
