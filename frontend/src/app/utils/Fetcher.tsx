import { toast } from "react-toastify"

export const fetcher = async (url: string) => {
    const res = await fetch(url)

    if (!res.ok) {
        if (res.status === 401) {
            toast.warn('You are not authorized to perform this action')
            return { status: false, json: [] }
        } else if (res.status === 404) {
            toast.warn('Resource not found')
            return { status: false, json: [] }
        } else {
            try {
                const json = await res.json()
                toast.warn(json.message)
                return { status: false, json: [] }
            } catch ($e) {
                toast.warn('Server did not respond with JSON')
                return { status: false, json: [] }
            }
        }
    } else {
        const json = await res.json()
        return { status: true, json: json }
    }
}