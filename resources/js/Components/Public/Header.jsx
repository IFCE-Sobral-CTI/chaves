import React from "react";

export default function Header({ title, subtitle }) {
    return (
        <>
            <div className="p-2 mb-2 md:w-2/3 md:m-auto">
                <h1 className="text-2xl text-center font-bold md:mb-2">{title}</h1>
                {subtitle && <p className="text-neutral-500 text-center">{subtitle}</p>}
            </div>
        </>
    )
}
