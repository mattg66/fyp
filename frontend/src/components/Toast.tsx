"use client";
import { useTheme } from "next-themes"
import { ToastContainer } from "react-toastify"

export const Toast = () => {
    const { resolvedTheme } = useTheme()

    return (
        <ToastContainer
            position="top-right"
            autoClose={5000}
            hideProgressBar={false}
            newestOnTop={false}
            closeOnClick
            rtl={false}
            pauseOnFocusLoss
            draggable
            pauseOnHover
            theme={resolvedTheme === 'dark' ? 'dark' : 'light'}
        />
    )
}