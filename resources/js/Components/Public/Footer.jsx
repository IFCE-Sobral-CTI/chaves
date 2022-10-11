import { Link } from "@inertiajs/inertia-react";
import React from "react";

export default function Footer() {
    return (
        <>
            <footer className="flex flex-col md:flex-row items-center justify-center md:gap-8 w-full bg-neutral-200 p-3 md:p-2">
                <div className="flex items-center">
                    <div className='flex justify-end md:p-2'>
                        <img src={route().t.url + "/img/logo_vertical_branco.svg"} className="h-16 md:h-20" style={{ filter: 'brightness(50%)' }} title="IFCE - Campus Sobral"/>
                    </div>
                    <section className="md:flex-1 text-neutral-500">
                        <p className='font-normal'>Instituto Federal do Ceará - IFCE</p>
                        <p className='font-normal text-sm'><em>Campus</em> Sobral</p>
                        <p className='font-light text-xs'>Coordenadoria de Tecnologia da Informação</p>
                    </section>
                </div>
                <section className="font-light flex md:flex-col justify-between md:gap-0 gap-4 mt-2">
                    <span className="hidden md:inline-block font-normal">Links úteis:</span>
                    <Link className="underline pr-4 md:ml-2 border-r md:border-0 border-neutral-400">Fale conosco</Link>
                    <Link className="underline pr-4 md:ml-2 border-r md:border-0 border-neutral-400">FAQ</Link>
                    <Link className="underline md:ml-2">Reportar erro</Link>
                </section>
            </footer>
        </>
    )
}
