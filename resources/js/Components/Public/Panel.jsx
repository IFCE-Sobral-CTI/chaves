import React from "react";

export default function Panel({ children, className }) {
    return (
        <>
            <div className={"md:w-2/3 md:m-auto p-1 md:p-3 bg-white shadow-md rounded-md " + className}>{children}</div>
        </>
    )
}
