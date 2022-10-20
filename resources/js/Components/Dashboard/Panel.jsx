import React from "react";

function Panel({ className, children }) {
    return (
        <>
            <div className={"p-2 md:p-4 bg-white shadow-md rounded-lg " + className}>
                {children}
            </div>
        </>
    )
}

export default Panel;
