import React from 'react';
import { Routes, Route } from "react-router-dom";
// import Recipes from "./Recipes"
import { Link } from '@inertiajs/react'



export default function Home() {
    const name = "Home Page";
    return (
        <>
            <div className="container">
            <Link href="/auth/create">Auth</Link>
            <h1>{name}</h1>
            </div>
        </>
    );
}
