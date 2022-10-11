import React from "react";

function Button({ children, type, processing, className, onClick, color }) {
    const handleClick = (event) => {
        if ((typeof onClick) == 'function' )
            onClick(event)
    }

    const colorize = color => {
        switch(color) {
            case 'green':
                return 'bg-green hover:bg-green-dark focus:ring-green-300 ';
            case 'blue':
                return 'bg-blue-500 hover:bg-blue-600 focus:ring-blue-300 ';
            case 'fuchsia':
                return 'bg-fuchsia-500 hover:bg-fuchsia-600 focus:ring-fuchsia-300 ';
            case 'violet':
                return 'bg-violet-500 hover:bg-violet-600 focus:ring-violet-300 ';
            case 'sky':
                return 'bg-sky-500 hover:bg-sky-600 focus:ring-sky-300 ';
            case 'yellow':
                return 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-300 ';
            case 'red':
                return 'bg-red-500 hover:bg-red-600 focus:ring-red-300 ';
            default:
                return 'bg-neutral-500 hover:bg-neutral-600 focus:ring-neutral-300 ';
        }
    }

    return (
        <>
            <button
                type={type?? 'button'}
                className={`inline-flex items-center px-4 py-2 border border-transparent tracking-widest text-sm rounded-lg text-white transition ease-in-out duration-150 focus:ring-2 ${
                    processing && 'opacity-25 '
                } ${colorize(color)}` + className}
                disabled={processing}
                onClick={handleClick}
            >
                {children}
            </button>
        </>
    )
}

export default Button;
