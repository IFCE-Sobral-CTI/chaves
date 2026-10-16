import React, { useEffect, useRef, useState } from "react";
import Input from "@/Components/Form/Input";
import InputError from "@/Components/InputError";
import SearchItem from "@/Pages/Keys/Borrow/Components/SearchItem";

export default function Search({ data, lastPage: initialLastPage = 1, endpoint, employeeId, onChange, values = [], error = '' }) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState(data);
    const [known, setKnown] = useState(data);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(initialLastPage);
    const moreController = useRef(null);

    useEffect(() => {
        setKnown(items => [...items, ...data].filter((item, index, all) => all.findIndex(other => other.id === item.id) === index));
        setResults(data);
    }, [data]);

    useEffect(() => {
        if (!endpoint || !employeeId) return;
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ term, page: '1', employee_id: employeeId });
                const response = await fetch(`${endpoint}?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    setResults([]);
                    return;
                }
                const result = await response.json();
                if (controller.signal.aborted) return;
                setResults(result.data);
                setKnown(items => [...items, ...result.data].filter((item, index, all) => all.findIndex(other => other.id === item.id) === index));
                setPage(result.current_page);
                setLastPage(result.last_page);
            } catch (error) {
                if (error.name !== 'AbortError') setResults([]);
            }
        }, 250);
        return () => { clearTimeout(timer); controller.abort(); moreController.current?.abort(); };
    }, [term, endpoint, employeeId]);

    const loadMore = async () => {
        const params = new URLSearchParams({ term, page: String(page + 1), employee_id: employeeId });
        moreController.current?.abort();
        const controller = new AbortController();
        moreController.current = controller;
        try {
            const response = await fetch(`${endpoint}?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const result = await response.json();
            if (controller.signal.aborted) return;
            setResults(items => [...items, ...result.data]);
            setKnown(items => [...items, ...result.data].filter((item, index, all) => all.findIndex(other => other.id === item.id) === index));
            setPage(result.current_page);
            setLastPage(result.last_page);
        } catch (error) {
            if (error.name !== 'AbortError') setResults([]);
        }
    };

    const list = results.filter(item => !values.some(id => String(id) === String(item.id)));

    const onHandleChange = (event) => {
        setTerm(event.target.value);
    };

    const toggleItemHandler = (id) => {
        onChange(values.includes(id) ? values.filter(item => item !== id) : [id, ...values]);
    };

    const items = list.map(item => (
        <SearchItem item={item} toggleItemHandler={toggleItemHandler} key={item.id} icon="add" />
    ));

    const itemsAdd = known.filter(item => values.some(id => String(id) === String(item.id))).map(item => (
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
                        {page < lastPage && <button type="button" className="w-full p-2 text-sm text-blue-700" onClick={loadMore}>Carregar mais chaves</button>}
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
