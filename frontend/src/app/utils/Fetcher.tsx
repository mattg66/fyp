export const fetcher = async (url: string) => {
    const res = await fetch(url)

    if (!res.ok) {
        try {
            const json = await res.json()
            return { status: false, json: json }
        } catch ($e) {
            return { status: false, json: {'message': 'Server did not respond with JSON'} }
        }
    } else {
        const json = await res.json()
        return { status: true, json: json }
    }
}