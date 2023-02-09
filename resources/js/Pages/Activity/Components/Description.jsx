import React, { useState, useEffect } from "react";

export default function Description({ title }) {
    const [result, setResult] = useState('');

    useEffect(() => {
        switch(title) {
            case 'created':
                setResult(<span className="px-2 py-0.5 rounded-md text-sm font-light text-white bg-green-500">Criou um registro</span>);
                break;
            case 'updated':
                setResult(<span className="px-2 py-0.5 rounded-md text-sm font-light text-white bg-yellow-500">Atualizou um registro</span>);
                break;
            case 'deleted':
                setResult(<span className="px-2 py-0.5 rounded-md text-sm font-light text-white bg-red-500">Apagou um registro</span>);
                break;
            default:
                setResult(<span className="px-2 py-0.5 rounded-md text-sm font-light bg-cyan-500">Informe</span>);
                break;
        }
    }, []);

    return result;
}
