import { Link } from "@inertiajs/inertia-react";
import React from "react";

export default function Navbar() {
    return (
        <>
            <nav className="relative w-full flex flex-wrap items-center justify-between py-0.5 md:py-1 bg-green text-white shadow-lg transition">
                <div className="w-full flex flex-wrap items-center justify-between ">
                    <div className="">
                        <Link className="text-xl" href="">
                            <img src={route().t.url + "/img/logo_branco.svg"} className="h-14" alt="IFCE - Campus Sobral" />
                        </Link>
                    </div>
                </div>
            </nav>
        </>
    )
}
